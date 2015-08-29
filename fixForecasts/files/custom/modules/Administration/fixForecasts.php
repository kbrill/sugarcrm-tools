<?php
/**
 * The output is still a little weak, but it seems to do the job.  The only thing I cant figure out how to do
 * is to update the managers forecast screen at the end.  Right now when a manager goes to their forecast screen
 * after the repair is run they see the old totals, they have to click on the commit button to get everything right
 * again and I'd like to avoid that.
 */
$ffmws = new fixForecasts();
$ffmws->repairReportsToStructure();
$ffmws->debugPrint($ffmws->results);

class fixForecasts
{

    public $results = array();
    private $userData = array();
    private $currentTimePeriod = array();

    public function __construct()
    {
        $this->getUserData();
        $this->currentTimePeriod = $this->getCurrentTimePeriod();

//        $this->debugPrint($this->userData);
//        $this->debugPrint($this->currentTimePeriod);

    }

    /**
     * Collect up the user data
     *
     * @throws SugarQueryException
     */
    private function getUserData()
    {
        $users = BeanFactory::getBean('Users');
        $query = new SugarQuery();
        $query->select(array('id', 'user_name', 'reports_to_id', 'status', 'deleted'));
        $query->from($users);
        $rows = $query->execute();
        foreach ($rows as $row) {
            $this->userData[$row['id']] = $row;
        }
    }

    /**
     * Collect up the timeperiod data
     *
     * @return array
     * @throws SugarQueryException
     */
    private function getCurrentTimePeriod()
    {
        $admin = BeanFactory::getBean('Administration');
        $settings = $admin->getConfigForModule('Forecasts', 'base');
        $forward = $settings['timeperiod_shown_forward'];
        $backward = $settings['timeperiod_shown_backward'];
        $type = $settings['timeperiod_interval'];
        $leafType = $settings['timeperiod_leaf_interval'];
        $timeDate = TimeDate::getInstance();
        $timePeriods = array();
        $current = TimePeriod::getCurrentTimePeriod($type);

        //If the current TimePeriod cannot be found for the type, just create one using the current date as a reference point
        if (empty($current)) {
            $current = TimePeriod::getByType($type);
            $current->setStartDate($timeDate->getNow()->asDbDate());
        }
        $startDate = $timeDate->fromDbDate($current->start_date);

        //Move back for the number of backward TimePeriod(s)
        while ($backward-- > 0) {
            $startDate->modify($current->previous_date_modifier);
        }

        $endDate = $timeDate->fromDbDate($current->end_date);

        //Increment for the number of forward TimePeriod(s)
        while ($forward-- > 0) {
            $endDate->modify($current->next_date_modifier);
        }
        $db = DBManagerFactory::getInstance();
        $sq = new SugarQuery();
        $sq->from(BeanFactory::getBean('TimePeriods'));
        $sq->select(array('id', 'name'));
        $sq->where()
            ->notNull('parent_id')
            ->gte('start_date', $startDate->asDbDate())
            ->lte('start_date', $endDate->asDbDate())
            ->addRaw("coalesce({$db->convert('type', 'length')},0) > 0");
        $sq->orderBy('start_date', 'ASC');
        $beans = $sq->execute();

        //I am gather all of these as I might have to update more than one time period in the future
        foreach ($beans as $row) {
            $timePeriods['list'][$row['id']] = $row;
        }
        //the one is the current time period
        $current = TimePeriod::getCurrentTimePeriod();
        $timePeriods['current'] = $current->id;
        return $timePeriods;
    }

    /**
     * Do the repair
     *
     * @throws SugarQueryException
     */
    public function repairReportsToStructure()
    {
        $mgr_worksheet = BeanFactory::getBean('ForecastManagerWorksheets');
        $db = DBManagerFactory::getInstance();

        //Iterate through the list of users
        foreach ($this->userData as $id => $data) {
            $reports_to_id = $data['reports_to_id'];
            $status = $data['status'];
            $deleted = $data['deleted'];

            //Get all the worksheets for this user for this timeperiod
            $query = new SugarQuery();
            $query->select(array('id', 'name', 'user_id', 'assigned_user_id', 'timeperiod_id'));
            $query->from($mgr_worksheet);
            $query->where()->equals('timeperiod_id', $this->currentTimePeriod['current']);
            $query->where()->equals('user_id', $id);
            $query->where()->equals('deleted', 0);
            $query->orderBy('date_modified', 'DESC');
            $rows = $query->execute();

            //Only the first worksheet (by date_modified DESC) is kept
            $firstOne = true;
            $timePeriodName = $this->currentTimePeriod['list'][$this->currentTimePeriod['current']]['name'];

            //now iterate through the list of worksheets and check the assigned_user_id against the reports_to_id
            foreach ($rows as $row) {
                if ($firstOne) {
                    $firstOne = false;
                    if ($row['assigned_user_id'] != $reports_to_id) {
                        //This record needs updating
                        $mgr_worksheet->retrieve($row['id']);
                        $mgr_worksheet->assigned_user_id = $reports_to_id;
                        if ($deleted == 1 || $status != 'Active' || empty($reports_to_id)) {
                            //If the user has been deleted or marked as Inactive or has no reports_to_id then mark the sheets as deleted
                            $mgr_worksheet->deleted = 1;
                            $mgr_worksheet->save();

                            if ($deleted == 1 || $status != 'Active') {
                                $reason = "as the user is no longer active";
                            } elseif (empty($reports_to_id)) {
                                $reason = "as the user has no reports_to_is set";
                            }
                            $this->results[$row['id']] = "Worksheet in TimePeriod '{$timePeriodName}' for {$row['name']} was DELETED {$reason}";
                        } else {
                            //It just needs to be reassigned to the new manager
                            $mgr_worksheet->save();
                            $timePeriodName = $this->currentTimePeriod['list'][$this->currentTimePeriod['current']]['name'];
                            $from = $this->getUserName($row['assigned_user_id']);
                            $to = $this->getUserName($reports_to_id);
                            $this->results[$row['id']] = "Worksheet in TimePeriod '{$timePeriodName}' for {$row['name']} was reassigned from '{$from}' to '{$to}'";
                        }
                    } else {
                        //Nothing needs to be changed
                        $this->results[$row['id']] = "Worksheet in TimePeriod '{$timePeriodName}' for {$row['name']} correct and not changed";
                    }
                } else {
                    //This is a duplicate commit (IT SEEMS, maybe, possibly, more than likely, NOT SURE OF THIS ONE YET)
                    $mgr_worksheet->retrieve($row['id']);
                    $mgr_worksheet->deleted = 1;
                    $mgr_worksheet->save();
                    $this->results[$row['id']] = "Worksheet in TimePeriod '{$timePeriodName}' for {$row['name']} was DELETED as a DUPLICATE";
                }
            }
        }
    }

    /**
     * Get the users name by ID
     *
     * @param STRING $userID
     * @return STRING
     */
    private function getUserName($userID)
    {
        $users = BeanFactory::getBean('Users', $userID);
        return $users->name;
    }

    /**
     * Pretty print
     *
     * @param STRING $thingToPrint
     */
    public function debugPrint($thingToPrint)
    {
        echo "------------------------------------------------------<br><pre>";
        print_r($thingToPrint);
        echo "</pre><br>------------------------------------------------------";
    }
}