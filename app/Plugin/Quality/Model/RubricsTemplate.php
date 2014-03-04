<?php

/*
  @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

  OpenEMIS
  Open Education Management Information System

  Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by the Free Software Foundation
  , either version 3 of the License, or any later version.  This program is distributed in the hope
  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
  have received a copy of the GNU General Public License along with this program.  If not, see
  <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
 */

class RubricsTemplate extends QualityAppModel {

    //public $useTable = 'rubrics';
    //public $belongsTo = array('RubricsTemplateHeader');
    public $actsAs = array('ControllerAction', 'Quality.RubricsSetup');
    public $belongsTo = array(
        //'Student',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    public $hasMany = array(
        'RubricsTemplateHeader' => array(
            'foreignKey' => 'rubric_template_id',
        ),
        'RubricsTemplateColumnInfo' => array(
            'foreignKey' => 'rubric_template_id',
        )
    );
    public $validate = array(
        'name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Name.'
            )
        ),
        'pass_mark' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Pass Mark.'
            )
        )
    );
    public $weighthingsOptions = array(1 => 'Points', 2 => 'Percentage');

    public function rubricsTemplates($controller, $params) {
        $controller->Navigation->addCrumb('Rubrics');
        $controller->set('subheader', 'Quality - Rubrics');
        $controller->set('modelName', $this->name);

        $this->recursive = -1;
        $data = $this->find('all');

        $controller->set('data', $data);
    }

    public function rubricsTemplatesView($controller, $params) {
        $controller->Navigation->addCrumb('Rubric Infomations');
        $controller->set('subheader', 'Quality - Rubric Infomations');
        $controller->set('modelName', $this->name);

        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id), 'recursive' => 0));
        //pr($data);
        if (empty($data)) {
            $controller->redirect(array('action' => 'rubricsTemplates'));
        }
        $controller->Session->write('RubricsTemplate.id', $id);
        $controller->set('data', $data);
        $controller->set('weighthingsOptions', $this->weighthingsOptions);
    }

    public function rubricsTemplatesAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Rubric');
        $controller->set('subheader', 'Quality - Add Rubric');
        $controller->set('modelName', $this->name);

        $this->setupRubricsTemplate($controller, $params, 'add');
    }

    public function rubricsTemplatesEdit($controller, $params) {
        $this->render = 'add';

        $controller->Navigation->addCrumb('Edit Rubric');
        $controller->set('subheader', 'Quality - Edit Rubric');
        $controller->set('modelName', $this->name);

        $this->setupRubricsTemplate($controller, $params, 'edit');
    }

    public function setupRubricsTemplate($controller, $params, $type) {
        $institutionId = $controller->Session->read('InstitutionId');
        $controller->set('weighthingsOptions', $this->weighthingsOptions);
        
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $controller->set('id', $id);
        
        if ($controller->request->is('get')) {
            //pr($controller->request->data);
            $this->recursive = -1;
            $data = $this->findById($id);
            if (!empty($data)) {
                $controller->request->data = $data;
            } else {
                $controller->request->data[$this->name]['institution_id'] = $institutionId;
            }
        } else {//post
            // pr($controller->request->data);
            if ($this->saveAll($controller->request->data)) {

                if ($type == 'add') {
                    $controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
                    return $controller->redirect(array('action' => 'rubricsTemplates'));
                } else {
                    $controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
                    return $controller->redirect(array('action' => 'rubricsTemplatesView', $this->id));
                }
                //pr($controller->request->data);
            }
        }
    }

    public function rubricsTemplatesDelete($controller, $params) {
        if ($controller->Session->check('RubricsTemplate.id')) {
            $id = $controller->Session->read('RubricsTemplate.id');

            $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));


            $name = $data[$this->name]['name'];

            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
            $controller->Session->delete('RubricsTemplate.id');
            $controller->redirect(array('action' => 'rubricsTemplates'));
        }
    }

    //SQL Function 
    /* public function getRubricsTemplateList($id, $mode = 'list') {
      $data = $this->find($mode, array('recursive' => -1, 'conditions' => array('institution_id' => $id)));
      return $data;
      } */

    public function getRubricOptions($orderBy = 'name', $status = false) {
        $options['order'] = array('RubricsTemplate.' . $orderBy);
        $options['recursive'] = -1;

        $data = $this->find('list', $options);
//pr($data);die;
        return $data;
    }

    public function getRubricHeader($institutionSiteId, $year) {
        $options['order'] = array('RubricsTemplate.id');
        $options['group'] = array('RubricsTemplate.id');
        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'institution_site_classes',
                'alias' => 'InstitutionSiteClass',
                'conditions' => array('InstitutionSiteClass.institution_site_id =' . $institutionSiteId)
            ),
            array(
                'table' => 'school_years',
                'alias' => 'SchoolYear',
                'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
            ),
            array(
                'table' => 'quality_statuses',
                'alias' => 'QualityStatus',
                //  'type' => 'LEFT',
                'conditions' => array('QualityStatus.year = SchoolYear.name',
                    'QualityStatus.year =' . $year,
                    'RubricsTemplate.id = QualityStatus.rubric_template_id')
            ),
        );
        //$options['conditions'] = array('RubricTemplate.id' => 'QualityStatus.rubric_template_id');
        $data = $this->find('list', $options);
//pr($data);//die;
        return $data;
    }

    public function getLatestRubricYear($institutionSiteId) {
        $options['order'] = array('RubricsTemplate.id', 'SchoolYear.name DESC');
        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'institution_site_classes',
                'alias' => 'InstitutionSiteClass',
                'conditions' => array('InstitutionSiteClass.institution_site_id =' . $institutionSiteId)
            ),
            array(
                'table' => 'school_years',
                'alias' => 'SchoolYear',
                'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
            ),
            array(
                'table' => 'quality_statuses',
                'alias' => 'QualityStatus',
                //  'type' => 'LEFT',
                'conditions' => array('QualityStatus.year = SchoolYear.name',
                    'RubricsTemplate.id = QualityStatus.rubric_template_id')
            ),
        );
        $options['fields'] = array('SchoolYear.name');
        //$options['conditions'] = array('RubricTemplate.id' => 'QualityStatus.rubric_template_id');
        $data = $this->find('first', $options);
//pr($data);die;

        if (!empty($data)) {
            return $data['SchoolYear']['name'];
        } else {
            return date('Y');
        }
    }

    public function getEnabledRubricsOptions($year) {
        $options['order'] = array('RubricsTemplate.name');
        $options['recursive'] = -1;

        $options['joins'] = array(
            array(
                'table' => 'quality_statuses',
                'alias' => 'QualityStatus',
                'conditions' => array('RubricsTemplate.id = QualityStatus.rubric_template_id')
            ),
        );

        $options['conditions'] = array('QualityStatus.status' => 1, 'QualityStatus.year' => $year);

        $data = $this->find('list', $options);
//pr($data);die;
        return $data;
    }

    public function getRubric($id) {
        $data = $this->find('first', array('conditions' => array('id' => $id), 'recursive' => -1));

        return $data;
    }

    public function getRubricTemplateWeightingInfo() {

        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'rubrics_template_headers',
                'alias' => 'RubricTemplateHeader',
                'conditions' => array('RubricsTemplate.id = RubricTemplateHeader.rubric_template_id')
            ),
            array(
                'table' => 'rubrics_template_subheaders',
                'alias' => 'RubricTemplateSubheader',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplateSubheader.rubric_template_header_id = RubricTemplateHeader.id')
            ),
            array(
                'table' => 'rubrics_template_items',
                'alias' => 'RubricTemplateItem',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplateItem.rubric_template_subheader_id = RubricTemplateSubheader.id')
            )
        );
        $options['fields'] = array('RubricsTemplate.id', 'RubricsTemplate.weighthings', 'RubricsTemplate.pass_mark', 'Count(RubricTemplateItem.id) AS totalQuestion');
        $options['group'] = array('RubricsTemplate.id');
        $data = $this->find('all', $options);

        

        $RubricTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $weightings = $RubricTemplateColumnInfo->getMaxWeighting();

        $list = array();
        foreach ($data AS $obj) {
            if(!empty($weightings[$obj['RubricsTemplate']['id']])){
                $weighting = $weightings[$obj['RubricsTemplate']['id']];
            }
            
            $list[$obj['RubricsTemplate']['id']]['WeightingType'] = ($obj['RubricsTemplate']['weighthings'] == 1) ? 'point' : 'percent';
            $list[$obj['RubricsTemplate']['id']]['PassMark'] = $obj['RubricsTemplate']['pass_mark'];
            $list[$obj['RubricsTemplate']['id']]['TotalWeighting'] = $weighting * $obj[0]['totalQuestion'];
        }
        return $list;
    }

    public function getInstitutionQAReportHeader($institutionSiteId, $year = NULL, $includeArea = NULL) {
        if($includeArea === 'yes'){
            $header = array(array('School Year'), array('Area Name'), array('Area Code'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
        }
        else{
            $header = array(array('School Year'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
        }

        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        if (empty($year)) {
            $rubricYear = $this->getLatestRubricYear($institutionSiteId);
        } else {
            $rubricYear = $year;
        }

        //   pr($rubricYear);
        $rubricOptions = $this->getRubricHeader($institutionSiteId, $rubricYear);
        // pr($rubricOptions);
        // die;
        if (!empty($rubricOptions)) {
            foreach ($rubricOptions as $key => $item) {
                $headerOptions = $RubricsTemplateHeader->getRubricHeaders($key, 'all');
//pr($headerOptions);

                if (!empty($headerOptions)) {
                    $tempArr = array();
                    $tempArr[][] = 'Rubric Name';
                    foreach ($headerOptions AS $obj) {
                        $tempArr[][] = $obj['RubricsTemplateHeader']['title'];
                    }
                    $tempArr[][] = 'Total Weighting(%)';
                    $tempArr[][] = 'Pass/Fail';
                    $header = array_merge($header, $tempArr);
                }
            }

            $headerOptions = array();
            $headerOptions[][] = 'Grand Total Weighting(%)';
            //  $headerOptions[] = 'Pass/Fail';
            $header = array_merge($header, $headerOptions);
        }
        // pr($header); die;
        return $header;
    }

    public function processDataToCSVFormat($data, $autoGenerateFirstHeader = 'no', $includeArea = 'no') {
        $tempArray = array();
        $classId = '';
        $rubricName = '';
        $rubricId = '';
        $rubricCounter = 0;
        $rubricHeaderCounter = 0;

        $dataCount = count($data);

        $rubricTotal = 0;
        $rubricsGrandTotal = 0;

        $rubricTemplateWeightingInfo = $this->getRubricTemplateWeightingInfo();

        foreach ($data AS $num => $row) {
            $currentClassId = $row['InstitutionSiteClass']['id'];
            $currentRubricName = $row['RubricTemplate']['name'];
            $currentRubricId = $row['RubricTemplate']['id'];

            // pr($classId. " || ".$currentClassId );
            //pr($currentRubricName. " || ".$rubricName );
            if (!empty($classId) && !empty($rubricName) && $classId == $currentClassId /* && $rubricName == $currentRubricName */) {

                foreach ($row as $key => $value) {
                    if ($key == 'RubricTemplate') {
                        if ($rubricName != $currentRubricName) {
                            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                            $passFail = 'Fail';

                            $rubricTotal = round(($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
                            if ($rubricTotal >= $selectedWeightingInfo['PassMark']) {
                                $passFail = 'Pass';
                            }

                            $rubricsGrandTotal += $rubricTotal;

                            $tempArray[$rubricCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotal;
                            $tempArray[$rubricCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                            $tempArray[$rubricCounter - 1][$key . '_' . $rubricHeaderCounter]['name'] = $value['name'];

                            //   pr($rubricId . " || " . $currentRubricName);
                            $rubricName = $currentRubricName;
                            $rubricId = $currentRubricId;
                            $rubricTotal = 0;
                            //   pr('not the same name');
                        } 
                    }

                    if ($key == '0') {
                        $this->calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricCounter - 1, $rubricTotal, $tempArray);
                    }
                }
            } else {
                $classId = $currentClassId;
                $prevRubricHeaderCounter = $rubricHeaderCounter;
                $rubricHeaderCounter = 0; //Reset value

                $insertPrevScores = false;

                $rubricId = $currentRubricId;
                foreach ($row as $key => $value) {
                    if ($key == '0') {
                        $this->calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricCounter, $rubricTotal, $tempArray);
                    } else if ($key == 'InstitutionSiteClass') {
                        $tempArray[$rubricCounter][$key]['name'] = $value['name'];
                    } else if ($key == 'RubricTemplate') {
                        if (!empty($rubricName) && $rubricName != $currentRubricName) {
                            $insertPrevScores = true;
                        } else if (!empty($rubricName)) {
                            //pr('Brand New <<----');
                            $insertPrevScores = true;
                        }
                        $tempArray[$rubricCounter][$key . '_' . $rubricHeaderCounter]['name'] = $value['name'];
                    } else if ($key == 'RubricTemplateHeader') {
                        
                    } else {
                        $tempArray[$rubricCounter][$key] = $value;
                    }
                }

                if ($insertPrevScores) {
                    $insertPrevScores = false;

                    $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                    $passFail = 'Fail';
                    if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                        $rubricTotal = round(($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
                    }
                    if ($rubricTotal >= $selectedWeightingInfo['PassMark']) {
                        $passFail = 'Pass';
                    }
                    $rubricsGrandTotal += $rubricTotal;

                    $rubricsGrandTotal = round($rubricsGrandTotal / count($rubricTemplateWeightingInfo), 2); 
                    
                    $tempArray[$rubricCounter - 1]['TotalRubric' . '_' . $prevRubricHeaderCounter]['value'] = $rubricTotal;
                    $tempArray[$rubricCounter - 1]['PassFail' . '_' . $prevRubricHeaderCounter]['value'] = $passFail;
                    $tempArray[$rubricCounter - 1]['GrandTotal']['value'] = $rubricsGrandTotal;

                    $rubricTotal = 0;
                    $rubricsGrandTotal = 0;
                }


                $rubricName = $currentRubricName;

                $rubricCounter = count($tempArray);
            }
            // pr("==========================================");
            $rubricHeaderCounter ++;

            if ($num == $dataCount - 1) {
                $passFail = 'Fail';
                $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];

                $rubricTotal = ($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100;

                if ($rubricTotal >= $selectedWeightingInfo['PassMark']) {
                    $passFail = 'Pass';
                }

                $rubricsGrandTotal += $rubricTotal;
                $rubricsGrandTotal = round($rubricsGrandTotal / count($rubricTemplateWeightingInfo), 2);

                $tempArray[$rubricCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotal;
                $tempArray[$rubricCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                $tempArray[$rubricCounter - 1]['GrandTotal']['value'] = $rubricsGrandTotal;
            }
        }
        $tempArray = $this->breakReportByYear($tempArray, $autoGenerateFirstHeader, $includeArea); // pr($tempArray);die;

        return $tempArray;
    }

    private function breakReportByYear($data, $autoGenerateFirstHeader, $includeArea) {
        $tempArray = array();
        // pr($autoGenerateFirstHeader);
        $selectedYear = '';
        foreach ($data as $obj) {


            if ($obj['SchoolYear']['name'] != $selectedYear) {
                if (!empty($selectedYear)) {
                    //   pr($obj['SchoolYear']['name']);
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = $this->getInstitutionQAReportHeader($obj['InstitutionSite']['id'], $obj['SchoolYear']['name'], $includeArea);
                } else if (empty($selectedYear) && $autoGenerateFirstHeader == 'yes') {
                    $tempArray[] = $this->getInstitutionQAReportHeader($obj['InstitutionSite']['id'], $obj['SchoolYear']['name'], $includeArea);
                }

                $selectedYear = $obj['SchoolYear']['name'];
            }

            unset($obj['InstitutionSite']['id']);
            $tempArray[] = $obj;
        }
        return $tempArray;
    }

    private function calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricCounter, &$rubricTotal, &$tempArray) {
        foreach ($value as $sumValue) {
            $_sumValue = (empty($sumValue) ? 0 : $sumValue);
            $rubricTotal += $_sumValue;
            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
            // if ($selectedWeightingInfo['WeightingType'] == 'percent') {
            $_sumValue = round(($_sumValue / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
            //}
            $tempArray[$rubricCounter]['total_' . $rubricHeaderCounter]['value'] = $_sumValue;
        }
    }
}
