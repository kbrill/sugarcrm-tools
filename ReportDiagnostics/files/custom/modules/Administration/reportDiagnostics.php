<?php
/*********************************************************************************
 * ReportDiagnostics is a program developed by
 * Kenneth Brill (kbrill@sugarcrm.com)
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY KENNETH BRILL, KENNETH BRILL DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 *
 * @category   Main Program File
 * @package    ReportDiagnostics
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2011-2015 Kenneth Brill
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    1.0
 * @link       http://www.sugarforge.org/reviews/?group_id=1300
 */

//ToDo: Add checks for schedule reports
//ToDo: Finish the last relationship check at the bottom

if (!defined('sugarEntry')) define('sugarEntry', true);
require_once("data/BeanFactory.php");
require_once('modules/Reports/Report.php');

ini_set("display_errors", 1);
echo "Start...<br>";
$reportTest = new reportTest();
$reportTest->runTest();
foreach ($reportTest->reportResults as $id => $data) {
    if ($data['result'] == 'Passed') {
        echo "<span style=\"color:green\">";
    } else {
        echo "<span style=\"color:red\">";
    }
    echo $data['name'] . " --->" . $data['result'] . "<br>";
    if (count($data['reason']) > 0) {
        foreach ($data['reason'] as $line) {
            echo "--> " . $line . "<br>";
        }
    }
    echo "</span>";
}
echo "End...<br>";

class reportTest
{

    private $reportArray = array();
    public $reportResults = array();

    public function __construct()
    {
        $this->getReports();
    }

    public function runTest()
    {
        $jsonObj = getJSONobj();
        foreach ($this->reportArray as $reportID => $report) {
            $savedReport = BeanFactory::getBean('Reports', $reportID);
            $savedReportContent = $jsonObj->decode(html_entity_decode($savedReport->content));
            $this->reportResults[$savedReport->id] = array('name' => $savedReport->name, 'result' => 'Passed', 'reason' => array());
            //echo "<pre>{$savedReport->name} ({$reportID})<br>\n";
            if (array_key_exists($savedReport->module, $GLOBALS['beanList'])) {
                $this->checkFields($savedReportContent['module'], $savedReportContent, $savedReport->id);
            } else {
                $this->reportResults[$reportID]['result'] = 'Failed';
                $this->reportResults[$reportID]['reason'][] = "The module '{$savedReport->module}' does not exist.";
            }
            //print_r($savedReportContent);
            //echo "<hr></pre>\n";
        }
    }

    private function checkFields($moduleName, $reportContent, $reportID)
    {
        foreach ($reportContent['display_columns'] as $index => $data) {
            //$GLOBALS['IDE_EVAL_CACHE']['637658c7-5da7-428f-b5be-36c07a62b832']['full_table_list']['self_link_1']['link_def']['relationship_name']
            if ($data['table_key'] == 'self') {
                //field in the module
                $fieldToCheck = $reportContent['display_columns'][$index]['name'];
                $moduleToCheck = BeanFactory::getBean($moduleName);
                if (!$this->fieldExists($fieldToCheck, $moduleToCheck->field_defs)) {
                    $this->reportResults[$reportID]['result'] = 'Failed';
                    $this->reportResults[$reportID]['reason'][] = "[display_columns] The field '{$fieldToCheck}' does not exist in {$moduleToCheck->module_name}";
                }
            } else {
                //related field
                if (isset($reportContent['full_table_list'][$data['table_key']])) {
                    $relName1 = $reportContent['full_table_list'][$data['table_key']]['link_def']['name'];
                    $relName2 = $reportContent['full_table_list'][$data['table_key']]['link_def']['relationship_name'];
                    $module = BeanFactory::getBean($moduleName);
                    $relName = "";
                    if ($module->load_relationship($relName1)) {
                        $relName = $relName1;
                    } elseif ($module->load_relationship($relName2)) {
                        $relName = $relName2;
                    } else {
                        $this->reportResults[$reportID]['result'] = 'Failed';
                        $this->reportResults[$reportID]['reason'][] = "[display_columns] The relationship named '{$relName1}' or '{$relName2}' in {$moduleName} does not exist";
                    }
                    if (!empty($relName)) {
                        $fieldToCheck = $reportContent['display_columns'][$index]['name'];
                        $moduleToCheck = BeanFactory::getBean($module->$relName->def['module']);
                        if (!$this->fieldExists($fieldToCheck, $moduleToCheck->field_defs)) {
                            $this->reportResults[$reportID]['result'] = 'Failed';
                            $this->reportResults[$reportID]['reason'][] = "[display_columns] The field '{$fieldToCheck}' does not exist in '{$moduleToCheck->module_name}'";
                        }
                    }
                }
            }
        }

        foreach ($reportContent['summary_columns'] as $index => $data) {
            if ($data['table_key'] == 'self') {
                //field in the module
                if (isset($data['group_function']) && $data['group_function'] != 'count') {
                    $fieldToCheck = $reportContent['summary_columns'][$index]['name'];
                    $moduleToCheck = BeanFactory::getBean($moduleName);
                    if (!$this->fieldExists($fieldToCheck, $moduleToCheck->field_defs)) {
                        $this->reportResults[$reportID]['result'] = 'Failed';
                        $this->reportResults[$reportID]['reason'][] = "[summary_columns] The field '{$fieldToCheck}' does not exist in '{$moduleToCheck->module_name}'";
                    }
                }
            } else {
                //related field
                if (isset($reportContent['full_table_list'][$data['table_key']])) {
                    $relName1 = $reportContent['full_table_list'][$data['table_key']]['link_def']['name'];
                    $relName2 = $reportContent['full_table_list'][$data['table_key']]['link_def']['relationship_name'];
                    $module = BeanFactory::getBean($moduleName);
                    $relname = "";
                    if ($module->load_relationship($relName1)) {
                        $relName = $relName1;
                    } elseif ($module->load_relationship($relName2)) {
                        $relName = $relName2;
                    } else {
                        $this->reportResults[$reportID]['result'] = 'Failed';
                        $this->reportResults[$reportID]['reason'][] = "[summary_columns] The relationship named '{$relName1}' or '{$relName2}' in $moduleName does not exist";
                    }
                    if (!empty($relName)) {
                        $fieldToCheck = $reportContent['summary_columns'][$index]['name'];
                        $moduleToCheck = BeanFactory::getBean($module->$relName->def['module']);
                        if (!array_key_exists($fieldToCheck, $moduleToCheck->field_defs)) {
                            $this->reportResults[$reportID]['result'] = 'Failed';
                            $this->reportResults[$reportID]['reason'][] = "[summary_columns] The field '{$fieldToCheck}' does not exist in '{$moduleToCheck->module_name}'";
                        }
                    }
                }
            }
        }

        foreach ($reportContent['group_defs'] as $index => $data) {
            if ($data['table_key'] == 'self') {
                //field in the module
                $fieldToCheck = $reportContent['group_defs'][$index]['name'];
                $moduleToCheck = BeanFactory::getBean($moduleName);
                if (!array_key_exists($fieldToCheck, $moduleToCheck->field_defs)) {
                    $this->reportResults[$reportID]['result'] = 'Failed';
                    $this->reportResults[$reportID]['reason'][] = "[group_defs] The field '{$fieldToCheck}' does not exist in '{$moduleToCheck->module_name}'";
                }
            } else {
                //related field
                if (isset($reportContent['full_table_list'][$data['table_key']])) {
                    $relName1 = $reportContent['full_table_list'][$data['table_key']]['link_def']['name'];
                    $relName2 = $reportContent['full_table_list'][$data['table_key']]['link_def']['relationship_name'];
                    $module = BeanFactory::getBean($moduleName);
                    $relName = "";
                    if ($module->load_relationship($relName1)) {
                        $relName = $relName1;
                    } elseif ($module->load_relationship($relName2)) {
                        $relName = $relName2;
                    } else {
                        $this->reportResults[$reportID]['result'] = 'Failed';
                        $this->reportResults[$reportID]['reason'][] = "[group_defs] The relationship named '{$relName1}' or '{$relName2}' in $moduleName does not exist";
                    }
                    if (!empty($relName)) {
                        $fieldToCheck = $reportContent['group_defs'][$index]['name'];
                        $moduleToCheck = BeanFactory::getBean($module->$relName->def['module']);
                        if (!array_key_exists($fieldToCheck, $moduleToCheck->field_defs)) {
                            $this->reportResults[$reportID]['result'] = 'Failed';
                            $this->reportResults[$reportID]['reason'][] = "[group_defs] The field '{$fieldToCheck}' does not exist in '{$moduleToCheck->module_name}'";
                        }
                    }
                }
            }
        }

        //A small hack to reformat filters that only have 1 element so the checks work in the loop below
        if(array_key_exists('filters_def', $reportContent) && array_key_exists(0, $reportContent['filters_def']) && !empty($reportContent['filters_def'])) {
            $relocateFilter = $reportContent['filters_def'][0];
            $reportContent['filters_def']= array(
                'Filter_1'=>array(
                    'operator' => 'expanded',
                    'test' => $relocateFilter
                ),
            );
        }

        foreach ($reportContent['filters_def'] as $filterIndex => $filterData) {
            foreach ($filterData as $index => $data) {
                if ($index != 'operator') {
                    if (isset($data['table_key']) && $data['table_key'] == 'self') {
                        $fieldToCheck = $data['name'];
                        $moduleToCheck = BeanFactory::getBean($reportContent['module']);
                        if(!$this->fieldExists($fieldToCheck,$moduleToCheck->field_defs)) {
                            $this->reportResults[$reportID]['result'] = 'Failed';
                            $this->reportResults[$reportID]['reason'][] = "[filters_def] The field '{$fieldToCheck}' does not exist in '{$moduleToCheck->module_name}'";
                        } else {
                            $fieldType = $moduleToCheck->field_defs[$fieldToCheck]['type'];
                            SugarAutoLoader::requireWithCustom("include/generic/SugarWidgets/SugarWidgetField{$fieldType}.php");
                            $className = "SugarWidgetField{$fieldType}";
                            if(class_exists($className)) {
                                $widget = new $className($this);
                                $methodName = "queryFilter" . $data['qualifier_name'];
                                if (!method_exists($widget, $methodName)) {
                                    $this->reportResults[$reportID]['result'] = 'Failed';
                                    $this->reportResults[$reportID]['reason'][] = "[filters_def] The field '{$fieldToCheck}' uses the filter '{$data['qualifier_name']}' that does not exist for a '{$fieldType}' type field.";
                                }
                            } else {
                                $this->reportResults[$reportID]['result'] = 'Failed';
                                $this->reportResults[$reportID]['reason'][] = "[filters_def] Could not find a widget called 'SugarWidgetField{$fieldType}.php'.";
                            }
                        }
                    } else {
                        //relationship
                        $test = 1;
                    }
                }
            }
        }
    }

    private function fieldExists($fieldToCheck, $field_defs) {
        if(array_key_exists($fieldToCheck, $field_defs)) {
            return true;
        } else {
            return false;
        }
    }

    private function getReports()
    {
        $query = "SELECT id FROM saved_reports WHERE deleted=0";
        $result = $GLOBALS['db']->query($query, true, "Error updating fields_meta_data.");
        while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->reportArray[$hash['id']] = $hash['id'];
        }
    }

    public function getAttribute() {
        return null;
    }

    private function checkScheduledReports($report) {

    }
}