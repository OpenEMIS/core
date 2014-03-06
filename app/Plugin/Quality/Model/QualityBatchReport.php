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

class QualityBatchReport extends QualityAppModel {

    public $useTable = false;

    //   public $actsAs = array('ControllerAction');
    //App::import('Model', 'Quality.QualityBatchReport');
    // SQL Statements that need to be made in the batch_reports

    public function generateLocalSchool() {
        App::import('Model', 'InstitutionSite');

        $InstitutionSite = new InstitutionSite();
        $data = $InstitutionSite->find('all', array(
            //$data = $this->InstitutionSite->find('all', array(
            'fields' => array(
                'SchoolYear.name',
                'Area.name',
                'Area.parent_id',
                'InstitutionSiteLocality.name',
                'InstitutionSite.name',
                'InstitutionSite.code',
                'InstitutionSite.id',
                'InstitutionSiteClass.name',
                'InstitutionSiteClass.id',
                'EducationGrade.name',
                'RubricTemplate.name',
                'RubricTemplate.id',
                'RubricTemplateHeader.title',
                'COALESCE(SUM(RubricTemplateColumnInfo.weighting),0)'
            ),
            'order' => array('SchoolYear.name DESC', 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id', 'RubricTemplateHeader.order'),
            'group' => array('InstitutionSiteClass.id', 'RubricTemplate.id', 'RubricTemplateHeader.id'),
            'joins' => array(
                array(
                    'table' => 'areas',
                    'alias' => 'Area',
                    'conditions' => array('Area.id = InstitutionSite.area_id')
                ),
                array(
                    'table' => 'institution_site_localities',
                    'alias' => 'InstitutionSiteLocality',
                    'conditions' => array('InstitutionSiteLocality.id = InstitutionSite.institution_site_locality_id')
                ),
                array(
                    'table' => 'institution_site_classes',
                    'alias' => 'InstitutionSiteClass',
                    'conditions' => array('InstitutionSiteClass.institution_site_id = InstitutionSite.id')
                ),
                array(
                    'table' => 'institution_site_class_grades',
                    'alias' => 'InstitutionSiteClassGrade',
                    'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id')
                ),
                array(
                    'table' => 'education_grades',
                    'alias' => 'EducationGrade',
                    'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
                ),
                array(
                    'table' => 'school_years',
                    'alias' => 'SchoolYear',
                    'type' => 'LEFT',
                    'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
                ),
                array(
                    'table' => 'quality_statuses',
                    'alias' => 'QualityStatus',
                    'conditions' => array('QualityStatus.year = SchoolYear.name')
                ),
                array(
                    'table' => 'rubrics_templates',
                    'alias' => 'RubricTemplate',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplate.id = QualityStatus.rubric_template_id')
                ),
                array(
                    'table' => 'quality_institution_rubrics',
                    'alias' => 'QualityInstitutionRubric',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'QualityInstitutionRubric.institution_site_class_id = InstitutionSiteClass.id',
                        'RubricTemplate.id = QualityInstitutionRubric.rubric_template_id',
                        'SchoolYear.id = QualityInstitutionRubric.school_year_id'
                    )
                ),
                array(
                    'table' => 'rubrics_template_headers',
                    'alias' => 'RubricTemplateHeader',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplate.id = RubricTemplateHeader.rubric_template_id')
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
                ),
                array(
                    'table' => 'rubrics_template_answers',
                    'alias' => 'RubricTemplateAnswer',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplateAnswer.rubric_template_item_id = RubricTemplateItem.id')
                ),
                array(
                    'table' => 'quality_institution_rubrics_answers',
                    'alias' => 'QualityInstitutionRubricAnswer',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'QualityInstitutionRubricAnswer.quality_institution_rubric_id = QualityInstitutionRubric.id',
                        'QualityInstitutionRubricAnswer.rubric_template_header_id = RubricTemplateHeader.id',
                        'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
                        'QualityInstitutionRubricAnswer.rubric_template_answer_id = RubricTemplateAnswer.id',
                        'InstitutionSiteClass.id = QualityInstitutionRubric.institution_site_class_id'
                    )
                ),
                array(
                    'table' => 'rubrics_template_column_infos',
                    'alias' => 'RubricTemplateColumnInfo',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'RubricTemplateAnswer.rubrics_template_column_info_id = RubricTemplateColumnInfo.id',
                        'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
                    ),
                )
            ),
            'recursive' => -1
                //   , {cond}
        ));

        App::import('Model', 'Quality.QualityBatchReport');
        $qbr = new QualityBatchReport();
        $data = $qbr->generateQASchoolsReport($data);

        $settings['custom3LayerFormat'] = true;
    }

    public function generateResultsSchool() {
        App::import('Model', 'InstitutionSite');
        App::import('Model', 'Quality.QualityBatchReport');
        $qbr = new QualityBatchReport();
        $InstitutionSite = new InstitutionSite();

        $fields = $qbr->getResultDisplayFieldTable('base');
        $joins = $qbr->getResultJoinTableData();

        $dbo = $InstitutionSite->getDataSource();
        $query = $dbo->buildStatement(array(
            'fields' => $fields,
            'table' => $dbo->fullTableName($InstitutionSite),
            'alias' => $InstitutionSite->alias,
            'limit' => null,
            'offset' => null,
            //{cond},
            'joins' => $joins,
            'conditions' => null,
            'group' => array('InstitutionSiteClass.id', 'RubricTemplate.id'),
            'order' => array('InstitutionSite.name', 'SchoolYear.name DESC', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id')
                ), $InstitutionSite);

        $query = '(' . $query . ')';

        $fields2 = $qbr->getResultDisplayFieldTable('search');

        $queryFinal = $dbo->buildStatement(array(
            'fields' => $fields2,
            'table' => $query,
            'alias' => $InstitutionSite->alias . 'Fliter',
            'limit' => null,
            'offset' => null,
            'joins' => array(),
            'conditions' => null,
            'group' => array('Year', 'RubricId', 'SiteId', 'GradeId'),
            'order' => array('SiteName', 'Year DESC', 'Grade', 'ClassName')
                ), $InstitutionSite);


        $queryCount = $dbo->buildStatement(array(
            'fields' => array('COUNT(*) as TotalCount'),
            'table' => '(' . $queryFinal . ')',
            'alias' => $InstitutionSite->alias . 'Fliter',
            'limit' => null,
            'offset' => null,
            'joins' => array(),
            'conditions' => null,
            'group' => null,
            'order' => null
                ), $InstitutionSite);

        $data = $dbo->fetchAll($queryFinal);

        $data = $qbr->generateQAResultReport($data);
    }

    //QA Results Query Function
    public function getResultDisplayFieldTable($type) {

        if ($type == 'base') {
            $fields = array(
                'SchoolYear.name AS Year',
                'InstitutionSite.name AS SiteName',
                'InstitutionSite.code AS SiteCode',
                'InstitutionSite.id AS SiteId',
                'InstitutionSiteClass.name AS ClassName',
                'InstitutionSiteClass.id AS ClassId',
                'EducationGrade.name AS Grade',
                'EducationGrade.id AS GradeId',
                'RubricTemplate.name AS RubricName',
                'RubricTemplate.id AS RubricId',
                //    'RubricTemplateHeader.title',
                'COALESCE(SUM(RubricTemplateColumnInfo.weighting),0) AS total'
            );
        } else {
            $fields = array(
                'Year',
                'SiteName',
                'SiteCode',
                'SiteId',
                'Grade',
                'GradeId',
                'RubricName',
                'RubricId',
                'COUNT(ClassId) AS TotalClasses',
                'MAX(total) AS Maximum',
                'MIN(total) AS Minimum',
                'AVG(total) AS Average'
            );
        }

        return $fields;
    }

    public function getResultJoinTableData() {
        $joins = array(
            array(
                'table' => 'institution_site_classes',
                'alias' => 'InstitutionSiteClass',
                'conditions' => array('InstitutionSiteClass.institution_site_id = InstitutionSite.id')
            ),
            array(
                'table' => 'institution_site_class_grades',
                'alias' => 'InstitutionSiteClassGrade',
                'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id')
            ),
            array(
                'table' => 'education_grades',
                'alias' => 'EducationGrade',
                'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
            ),
            array(
                'table' => 'school_years',
                'alias' => 'SchoolYear',
                'type' => 'LEFT',
                'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
            ),
            array(
                'table' => 'quality_statuses',
                'alias' => 'QualityStatus',
                'conditions' => array('QualityStatus.year = SchoolYear.name')
            ),
            array(
                'table' => 'rubrics_templates',
                'alias' => 'RubricTemplate',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplate.id = QualityStatus.rubric_template_id')
            ),
            array(
                'table' => 'quality_institution_rubrics',
                'alias' => 'QualityInstitutionRubric',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualityInstitutionRubric.institution_site_class_id = InstitutionSiteClass.id',
                    'RubricTemplate.id = QualityInstitutionRubric.rubric_template_id',
                    'SchoolYear.id = QualityInstitutionRubric.school_year_id'
                )
            ),
            array(
                'table' => 'rubrics_template_headers',
                'alias' => 'RubricTemplateHeader',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplate.id = RubricTemplateHeader.rubric_template_id')
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
            ),
            array(
                'table' => 'rubrics_template_answers',
                'alias' => 'RubricTemplateAnswer',
                'type' => 'LEFT',
                'conditions' => array('RubricTemplateAnswer.rubric_template_item_id = RubricTemplateItem.id')
            ),
            array(
                'table' => 'quality_institution_rubrics_answers',
                'alias' => 'QualityInstitutionRubricAnswer',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualityInstitutionRubricAnswer.quality_institution_rubric_id = QualityInstitutionRubric.id',
                    'QualityInstitutionRubricAnswer.rubric_template_header_id = RubricTemplateHeader.id',
                    'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
                    'QualityInstitutionRubricAnswer.rubric_template_answer_id = RubricTemplateAnswer.id',
                    'InstitutionSiteClass.id = QualityInstitutionRubric.institution_site_class_id'
                )
            ),
            array(
                'table' => 'rubrics_template_column_infos',
                'alias' => 'RubricTemplateColumnInfo',
                'type' => 'LEFT',
                'conditions' => array(
                    'RubricTemplateAnswer.rubrics_template_column_info_id = RubricTemplateColumnInfo.id',
                    'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
                ),
            )
        );

        return $joins;
    }

    //Batch function methods

    public function generateQASchoolsReport($data) {
        $header = array(array('School Year'), array('Country'), array('Area'), array('Locality'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
        $rubricData = $this->processSchoolDataToCSVFormat($data);
        $processRubricData = $this->breakReportByYear($rubricData, 'yes', $header); // pr($tempArray);die;
        // pr($processRubricData);
        return $processRubricData;
    }

    public function generateQAResultReport($data) {
        $processRubricData = $this->processResultDataToCSVFormat($data);

        return $processRubricData;
    }

    //CSV data processing

    private function processResultDataToCSVFormat($data) {
        $tempArray = array();

        $rubricTemplateWeightingInfo = $this->getRubricTemplateWeightingInfo();
        foreach ($data AS $num => $row) {
            $mergeData = array_merge($row['InstitutionSiteFliter'], $row[0]);
            $rubricId = $mergeData['RubricId'];
            foreach ($mergeData AS $key => $value) {
                if (substr($key, -2) !== 'Id') {
                    if ($key != 'Maximum' && $key != 'Minimum' && $key != 'Average') {
                        $tempArray[$num][$key] = $value;
                    } else {
                        $tempArray[$num][$key] = round(($value / $rubricTemplateWeightingInfo[$rubricId]['TotalWeighting']) * 100, 2);

                        if ($key == 'Average') {
                            $passFail = 'Fail';
                            if ($rubricTemplateWeightingInfo[$rubricId]['WeightingType'] == 'point') {
                                if ($value >= $rubricTemplateWeightingInfo[$rubricId]['PassMark']) {
                                    $passFail = 'Pass';
                                }
                            } else {
                                if ($tempArray[$num][$key] >= $rubricTemplateWeightingInfo[$rubricId]['PassMark']) {
                                    $passFail = 'Pass';
                                }
                            }
                            $tempArray[$num]['Pass/Fail'] = $passFail;
                        }
                    }
                }
            }
        }

        return $tempArray;
    }

    private function processSchoolDataToCSVFormat($data) {
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
                            $insertPrevScores = true;
                        }
                        $tempArray[$rubricCounter][$key . '_' . $rubricHeaderCounter]['name'] = $value['name'];
                    } else if ($key == 'RubricTemplateHeader') {
                        
                    } else if ($key == 'Area') {
                        $tempArray[$rubricCounter]['Country']['name'] = $this->getCountryName($value);
                        $tempArray[$rubricCounter][$key]['name'] = $value['name'];
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


        return $tempArray;
    }

    public function breakReportByYear($data, $autoGenerateFirstHeader, $header = NULL) {
        $tempArray = array();
        $selectedYear = '';
        foreach ($data as $obj) {

            if ($obj['SchoolYear']['name'] != $selectedYear) {
                if (!empty($selectedYear)) {
                    //   pr($obj['SchoolYear']['name']);
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = $this->getInstitutionQAReportHeader($obj['InstitutionSite']['id'], $obj['SchoolYear']['name'], $header);
                } else if (empty($selectedYear) && $autoGenerateFirstHeader == 'yes') {
                    $tempArray[] = $this->getInstitutionQAReportHeader($obj['InstitutionSite']['id'], $obj['SchoolYear']['name'], $header);
                }

                $selectedYear = $obj['SchoolYear']['name'];
            }

            unset($obj['InstitutionSite']['id']);
            $tempArray[] = $obj;
        }
        return $tempArray;
    }

    /* ===================================================================================
     * Generating Header for report by appending the rubrics info at the end  
     * $header structure
     * e.g $header = array(array('School Year'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
     * =================================================================================== */

    public function getInstitutionQAReportHeader($institutionSiteId, $year = NULL, $header = array()) {
        $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
        if (empty($year)) {
            $rubricYear = $this->getLatestRubricYear($institutionSiteId);
        } else {
            $rubricYear = $year;
        }

        $rubricOptions = $this->getRubricHeader($institutionSiteId, $rubricYear);

        if (!empty($rubricOptions)) {
            foreach ($rubricOptions as $key => $item) {
                $headerOptions = $RubricsTemplateHeader->getRubricHeaders($key, 'all');

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
            $header = array_merge($header, $headerOptions);
        }
        return $header;
    }

    private function getRubricTemplateWeightingInfo() {
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');

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
        $data = $RubricsTemplate->find('all', $options);

        $RubricTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $weightings = $RubricTemplateColumnInfo->getMaxWeighting();

        $list = array();
        foreach ($data AS $obj) {
            if (!empty($weightings[$obj['RubricsTemplate']['id']])) {
                $weighting = $weightings[$obj['RubricsTemplate']['id']];
            }

            $list[$obj['RubricsTemplate']['id']]['WeightingType'] = ($obj['RubricsTemplate']['weighthings'] == 1) ? 'point' : 'percent';
            $list[$obj['RubricsTemplate']['id']]['PassMark'] = $obj['RubricsTemplate']['pass_mark'];
            $list[$obj['RubricsTemplate']['id']]['TotalWeighting'] = $weighting * $obj[0]['totalQuestion'];
        }
        return $list;
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

    public function getRubricHeader($institutionSiteId, $year) {
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');

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
        $data = $RubricsTemplate->find('list', $options);
//pr($data);//die;
        return $data;
    }

    private function getCountryName($areaData = NULL) {
        $countryName = '';
        if (!empty($areaData)) {
            if (array_key_exists('parent_id', $areaData)) {
                if ($areaData['parent_id'] != -1) {
                    $Area = ClassRegistry::init('Area');
                    $Area->recursive = -1;
                    $data = $Area->findById($areaData['parent_id']);
                    if (array_key_exists('parent_id', $data['Area'])) {
                        if ($data['Area']['parent_id'] != -1) {
                            $countryName = $this->getCountryName($data['Area']);
                        } else {
                            $countryName = $data['Area']['name'];
                        }
                    }
                } else if ($areaData['parent_id'] == -1) {
                    if (array_key_exists('name', $areaData)) {
                        $countryName = $areaData['name'];
                    }
                }
            }
        }

        return $countryName;
    }

}
