<?php

//ToDo: all users to global team
//ToDo: Find broken TeamSets (TeamSets with missing teams

if (!defined('sugarEntry')) define('sugarEntry', true);
ini_set('max_execution_time', 900);
set_time_limit(900);
require_once('include/entryPoint.php');
require_once('include/utils/array_utils.php');
require_once('modules/Teams/TeamSet.php');

if (empty($current_language)) {
    $current_language = $sugar_config['default_language'];
}

$GLOBALS['app_list_strings'] = return_app_list_strings_language($current_language);
$GLOBALS['app_strings'] = return_application_language($current_language);

global $current_user;
$current_user = new User();
$current_user->getSystemUser();

$ft = new sweetFixTeams();
$ft->run();

class sweetFixTeams
{
    public $team_sets = array();
    private $tableData = array();
    private $ts;

    public function __construct()
    {
        $this->ts = new TeamSet();
    }

    public function run()
    {
        echo "Gathering team_sets<br>";
        flush();
        $this->gatherTeamSets();
        echo "Marking used team_sets<br>";
        flush();
        $this->markUsedTeamSets();
        echo "Fixing team_sets<br>";
        flush();
        $this->fixTeamSets();
        echo "Removing duplicate team_sets<br>";
        flush();
        $this->removeDuplicateTeamSets();
    }

    private function removeDuplicateTeamSets()
    {
        $query = "SELECT team_md5, count(*) as cnt FROM team_sets WHERE deleted=0 GROUP BY team_md5 HAVING COUNT(*) > 1";
        $result = $GLOBALS['db']->query($query);
        while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
            echo "{$hash['team_md5']} -> {$hash['cnt']}<br>";
            $query_md5 = "SELECT id FROM team_sets WHERE team_md5='{$hash['team_md5']}'";
            $result_md5 = $GLOBALS['db']->query($query_md5);
            $duplicate_teamSets = array();
            $mergedID = "";
            while ($hash_md5 = $GLOBALS['db']->fetchByAssoc($result_md5)) {
                if (empty($mergedID)) {
                    //This is the anointed team_set_id
                    $mergedID = $hash_md5['id'];
                } else {
                    //These are all the duplicates that we will replace with the anointed one above.
                    $duplicate_teamSets[] = $hash_md5['id'];
                }
            }
            //Now we need to replace the team_set_id with the mergedID from above
            // where ever the duplicate team_sets appear, so we have to scan all tables again
            //But at least this time the table data is cached
            $replaceString = "'" . implode("','", $duplicate_teamSets) . "'";
            foreach ($this->tableData as $tableName => $columnData) {
                if (array_key_exists('team_set_id', $columnData)) {
                    //this table does have a team_set field
                    $query = "UPDATE {$tableName} SET team_set_id='$mergedID' WHERE team_set_id IN ({$replaceString})";
                    $result = $GLOBALS['db']->query($query);
                }
            }

            //OK, last step, we now delete the duplicate team_sets from both tables
            $query = "UPDATE team_sets SET deleted=1 WHERE id IN ({$replaceString})";
            $result1 = $GLOBALS['db']->query($query);
            $query = "UPDATE team_sets_teams SET deleted=1 WHERE team_set_id IN ({$replaceString})";
            $result2 = $GLOBALS['db']->query($query);
        }
    }

    private function fixTeamSets()
    {
        foreach ($this->team_sets as $id => $data) {
            if ($data['unused']) {
                //We can delete these as they are never used in any table
                $query = "UPDATE team_sets SET deleted=1 WHERE id={$id}";
                $GLOBALS['db']->query($query);
            } else {
                $teamStats = $this->getTeamSetStats($id);

                //If the data is right then we dont need to update the DB
                if ($data['team_md5'] != $teamStats['team_md5'] ||
                    $data['team_count'] != $teamStats['team_count']
                ) {
                    $dateModified = $GLOBALS['timedate']->nowDb();
                    $query = "UPDATE team_sets SET team_md5='{$teamStats['team_md5']}', team_count={$teamStats['team_count']},
                                             date_modified='{$dateModified}'
                                         WHERE id='{$id}'";
                    $GLOBALS['db']->query($query);
                }
            }
        }
    }

    private function getTeamSetStats($team_set_id)
    {
        $teamStats = $this->getStatistics($this->ts->getTeamIds($team_set_id));
        return $teamStats;
    }

    private function gatherTeamSets()
    {
        $query = "SELECT * FROM team_sets WHERE deleted=0";
        $result = $GLOBALS['db']->query($query);
        while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->team_sets[$hash['id']] = array(
                'used' => 1,
                'team_count' => $hash['team_count'],
                'team_md5' => $hash['team_md5']
            );
        }
    }

    private function markUsedTeamSets()
    {
        $allTables = $GLOBALS['db']->getTablesArray();
        foreach ($allTables as $tableName) {
            if ($tableName != 'team_sets_teams' && $tableName != 'team_sets_modules') {
                $columnData = $GLOBALS['db']->get_columns($tableName);
                //cache it for later
                $this->tableData[$tableName] = $columnData;
                if (array_key_exists('team_set_id', $columnData)) {
                    //this table does have a team_set field
                    if (array_key_exists('deleted', $columnData)) {
                        $query = "SELECT team_set_id FROM {$tableName} WHERE deleted=0";
                    } else {
                        $query = "SELECT team_set_id FROM {$tableName}";
                    }
                    $result = $GLOBALS['db']->query($query);
                    while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
                        if (array_key_exists($hash['team_set_id'], $this->team_sets)) {
                            $this->team_sets[$hash['team_set_id']]['used'] = 0;
                        } else {
                            //this team set either doesnt exist or has been deleted
                            $query_anomaly = "UPDATE {$tableName} SET team_set_id='1' WHERE team_set_id='{$hash['team_set_id']}'";
                            $result2 = $GLOBALS['db']->query($query_anomaly);
                        }
                    }
                }
            }
        }
    }

    /**
     * Compute generic statistics we need when saving a team set.
     *
     * @param array $team_ids
     * @return array
     */
    protected function getStatistics($team_ids)
    {
        $team_md5 = '';
        sort($team_ids, SORT_STRING);
        $primary_team_id = '';
        //remove any dups
        $teams = array();

        $team_count = count($team_ids);
        if ($team_count == 1) {
            $team_md5 = md5($team_ids[0]);
            $teams[] = $team_ids[0];
            if (empty($this->primary_team_id)) {
                $primary_team_id = $team_ids[0];
            }
        } else {
            for ($i = 0; $i < $team_count; $i++) {

                $team_id = $team_ids[$i];

                if (!array_key_exists("$team_id", $teams)) {
                    $team_md5 .= $team_id;
                    if (empty($this->primary_team_id)) {
                        $primary_team_id = $team_id;
                    }
                    $teams["$team_id"] = $team_id;
                }
            }
            $team_md5 = md5($team_md5);
        }
        return array('team_ids' => $team_ids, 'team_md5' => $team_md5, 'primary_team_id' => $primary_team_id, 'team_count' => $team_count);
    }

}