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

    // SQL Statements that need to be made in the batch_reports

    public function generateLocalSchool() {
        App::import('Model', 'InstitutionSite');
        App::import('Model', 'Quality.QualityBatchReport');

        $qbr = new QualityBatchReport();
        $InstitutionSite = new InstitutionSite();

        $fields = $qbr->getLocalSchoolDisplayFieldTable();
        $joins = $qbr->getLocalSchoolJoinTableData();


        $dbo = $InstitutionSite->getDataSource();
        $queryFinal = $dbo->buildStatement(array(
            'fields' => $fields,
            'table' => $dbo->fullTableName($InstitutionSite),
            'alias' => $InstitutionSite->alias,
            'limit' => null,
            'offset' => null,
            'joins' => $joins,
            'conditions' => null,
            'group' => array('InstitutionSiteClass.id', 'RubricTemplate.id', 'RubricTemplateHeader.id'),
            'order' => array('SchoolYear.name DESC',/* 'Institution.name',*/ 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id', 'RubricTemplateHeader.order')
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
            'joins' => $joins,
            'conditions' => null,
            'group' => array('InstitutionSiteClass.id', 'RubricTemplate.id'),
            'order' => array(/*'Institution.name',*/ 'InstitutionSite.name', 'SchoolYear.name DESC', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id')
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
            'group' => array('Year', 'RubricId', 'InstitutionSiteId', 'GradeId'),
            'order' => array('InstitutionName', 'Year DESC', 'Grade', 'Class')
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

    public function generateRubricNotCompleted() {
        App::import('Model', 'InstitutionSite');
        App::import('Model', 'Quality.QualityBatchReport');
        $qbr = new QualityBatchReport();
        $InstitutionSite = new InstitutionSite();

        $fields = $qbr->getNotCompleteDisplayFieldTable('base');
        $joins = $qbr->getNotCompleteJoinTableData();

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
            'group' => array('InstitutionSiteClass.id', 'RubricTemplate.id', 'RubricTemplateSubheader.id'),
            'order' => array('SchoolYear.name DESC', 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id', 'RubricTemplateHeader.order')
                ), $InstitutionSite);

        $query = '(' . $query . ')';

        $fields2 = $qbr->getNotCompleteDisplayFieldTable('search');

        $queryFinal = $dbo->buildStatement(array(
            'fields' => $fields2,
            'table' => $query,
            'alias' => $InstitutionSite->alias . 'Fliter',
            'limit' => null,
            'offset' => null,
            'joins' => array(),
            'conditions' => null,
            'group' => array('ClassId', 'RubricId HAVING TotalQuestions != TotalAnswered'),
            'order' => null
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
    }

    //QA Local Schools Report function 
    public function getLocalSchoolDisplayFieldTable() {
        $fields = array(
            'SchoolYear.name AS Year',
            'Area.name AS Area',
            'Area.parent_id AS AreaParentId',
            'InstitutionSiteLocality.name AS Locality',
            //'Institution.name AS InstitutionName',
            'InstitutionSite.name AS InstitutionName',
            'InstitutionSite.code AS InstitutionCode',
            'InstitutionSite.id AS InstitutionSiteId',
            'InstitutionSiteClass.name AS Class',
            'InstitutionSiteClass.id AS ClassId',
            'EducationGrade.name AS Grade',
            'RubricTemplate.name AS RubricName',
            'RubricTemplate.id AS RubricId',
            'RubricTemplateHeader.title AS RubricHeader',
            'COALESCE(SUM(RubricTemplateColumnInfo.weighting),0)'
        );

        return $fields;
    }

    public function getLocalSchoolJoinTableData() {
        $joins = array(
           /* array(
                'table' => 'institutions',
                'alias' => 'Institution',
                'conditions' => array('InstitutionSite.institution_id = Institution.id')
            ),*/
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
        );

        return $joins;
    }

    //QA Results Query Function

    public function getResultDisplayFieldTable($type) {

        if ($type == 'base') {
            $fields = array(
                'SchoolYear.name AS Year',
                //'Institution.name AS InstitutionName',
                'InstitutionSite.name AS InstitutionName',
                'InstitutionSite.code AS InstitutionCode',
                'InstitutionSite.id AS InstitutionSiteId',
                'InstitutionSiteClass.name AS Class',
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
                //'InstitutionName',
                'InstitutionName',
                'InstitutionCode',
                'InstitutionSiteId',
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
            /*array(
                'table' => 'institutions',
                'alias' => 'Institution',
                'conditions' => array('InstitutionSite.institution_id = Institution.id')
            ),*/
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

    //QA Not Completed Functions

    public function getNotCompleteJoinTableData() {
        $joins = array(
            /*array(
                'table' => 'institutions',
                'alias' => 'Institution',
                'conditions' => array('InstitutionSite.institution_id = Institution.id')
            ),*/
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
                // 'type' => 'LEFT',
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
            )
        );

        return $joins;
    }

    public function getNotCompleteDisplayFieldTable($type) {

        if ($type == 'base') {
            $fields = array(
                'SchoolYear.name AS Year',
              //  'Institution.name AS InstitutionName',
                'InstitutionSite.name AS InstitutionName',
                'InstitutionSite.code AS InstitutionCode',
                'InstitutionSiteClass.name AS Class',
                'InstitutionSiteClass.id AS ClassId',
                'EducationGrade.name AS Grade',
                'RubricTemplate.name AS RubricName',
                'RubricTemplate.id AS RubricId',
                'RubricTemplateHeader.title AS header',
                'RubricTemplateSubheader.id AS subheader',
                'RubricTemplateSubheader.id as subId',
                'RubricTemplateItem.id AS ques',
                'QualityInstitutionRubricAnswer.id AS selected'
            );
        } else {
            $fields = array(
                'Year', /*'InstitutionName',*/ 'InstitutionName', 'Class', 'Grade', 'RubricName', 'COUNT(RubricName) AS TotalQuestions', 'COUNT(selected) AS TotalAnswered'
            );
        }

        return $fields;
    }

    //Batch function methods

    public function generateQASchoolsReport($data) {
        $header = array(array('Year'), array('Country'), array('Area'), array('Locality'),/* array('Institution Name'),*/ array('Institution Name'), array('Institution Code'), array('Class'), array('Grade'));
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

    public function processSchoolDataToCSVFormat($data) {//pr($data);die;
        $tempArray = array();
        $classId = '';
        $rubricName = '';
        $rubricId = '';
        $rubricItemCounter = 0;
        $rubricHeaderCounter = 0;

        $dataCount = count($data);

        $numOfRubricPerSch = 0;
        $tempRubricTotal = 0;
        $rubricTotal = 0;
        $rubricsGrandTotal = 0;

        $rubricTemplateWeightingInfo = $this->getRubricTemplateWeightingInfo();

        foreach ($data AS $num => $row) {
			//pr('-------------->>>>>>> '.$rubricTotal);
            $currentClassId = $row['InstitutionSiteClass']['ClassId'];
            $currentRubricName = $row['RubricTemplate']['RubricName'];
            $currentRubricId = $row['RubricTemplate']['RubricId'];
//pr($row);
            // pr($classId. " || ".$currentClassId );
            //pr($currentRubricName. " || ".$rubricName );
            if (!empty($classId) && !empty($rubricName) && $classId == $currentClassId /* && $rubricName == $currentRubricName */) {

                foreach ($row as $key => $value) {
                    if ($key == 'RubricTemplate') {
                        if ($rubricName != $currentRubricName) {
                           /* pr('1 --> ['.$rubricName.']');
                            pr('1 --> ['.$rubricId.'] || '.$currentRubricId);
                            pr('prev rubricTotal = '.$rubricTotal);*/
                            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                            $passFail = 'Fail';
                            $currentRubricScore = $rubricTotal;
                            $rubricTotalPercent = ($rubricTotal == 0)? 0:round(($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
							
							/*pr($currentRubricScore);
					pr($rubricTotalPercent);
					pr('1 -->> passmark : '.$selectedWeightingInfo['PassMark']);
                            pr('1 --> TotalWeighting : '.$selectedWeightingInfo['TotalWeighting']);*/
                            if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                                $currentRubricScore = $rubricTotalPercent;
                            }
                            if ($currentRubricScore >= $selectedWeightingInfo['PassMark']) {
                                $passFail = 'Pass';
                            }

							
                            $rubricsGrandTotal += $rubricTotalPercent;

                            $tempArray[$rubricItemCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotalPercent;
                            $tempArray[$rubricItemCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                            $tempArray[$rubricItemCounter - 1][$key . '_' . $rubricHeaderCounter]['name'] = $value['RubricName'];

                            //   pr($rubricId . " || " . $currentRubricName);
                            $rubricName = $currentRubricName;
                            $rubricId = $currentRubricId;
                            $rubricTotal = 0;
                            $numOfRubricPerSch ++;
                            //   pr('not the same name');
                           /* pr('/////////////////////');
                            pr('1 --> current [NEW] = '.$currentRubricName);*/
                        }
                    }
                    /*if ($key === 'RubricTemplateHeader') {
                        pr('1 --> '.$value['RubricHeader']);
                    }
                    else */if ($key == '0') {
                        $this->calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricItemCounter - 1, $rubricTotal, $tempArray);
                       // pr('1-->>> Score = '.$rubricTotal);
                    }
                }
            } else {
                
                $classId = $currentClassId;
                $prevRubricHeaderCounter = $rubricHeaderCounter;
                $rubricHeaderCounter = 0; //Reset value
                
                $insertPrevScores = false;
                if(empty($rubricId)){
                    $rubricId = $currentRubricId;
                }
                foreach ($row as $key => $value) {
					//pr($key);
					//pr($value);
                    if ($key == '0') {
                        $this->calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $currentRubricId, $rubricItemCounter, $tempRubricTotal, $tempArray);
                      //  pr('2 ---- >> Score = '.$tempRubricTotal);
                    } else if ($key == 'InstitutionSiteClass') {
                        $tempArray[$rubricItemCounter][$key]['name'] = $value['Class'];
                    } else if ($key == 'RubricTemplate') {
                        if (!empty($rubricName) && $rubricName != $currentRubricName) {
                            $insertPrevScores = true;
                        } else if (!empty($rubricName)) {
                            $insertPrevScores = true;
                        }
                        $tempArray[$rubricItemCounter][$key . '_' . $rubricHeaderCounter]['name'] = $value['RubricName'];
                    } else if ($key === 'RubricTemplateHeader') {
                       // pr('2 --> '.$value['RubricHeader']);
                    } else if ($key == 'Area') {
                        $tempArray[$rubricItemCounter]['Country']['name'] = $this->getCountryName($value);
                        $tempArray[$rubricItemCounter][$key]['name'] = $value['Area'];
                    } else {
                        $tempArray[$rubricItemCounter][$key] = $value;
                    }
                }

                if ($insertPrevScores) {
                    $insertPrevScores = false;
                    $numOfRubricPerSch ++;
                    
                    /*pr('2 --> prev ['.$rubricName.']');
                    pr('2 --> ['.$rubricId.'] || '.$currentRubricId);
                    pr('prev  rubricTotal = '.$rubricTotal);*/
                  //  pr($rubricTemplateWeightingInfo);
                   // pr('$rubricId = '.$rubricId);
                    $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
                    $passFail = 'Fail';

                    $currentRubricScore = $rubricTotal;
                    $rubricTotalPercent = ($rubricTotal == 0)? 0:round(($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
                   // pr('2 --> TotalWeighting ='.$selectedWeightingInfo['TotalWeighting']);
                    if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                        $currentRubricScore = $rubricTotalPercent;
                    }
					
					/*pr($currentRubricScore);
					pr($rubricTotalPercent);
					pr('passmark : '.$selectedWeightingInfo['PassMark']);*/
                    if ($currentRubricScore >= $selectedWeightingInfo['PassMark']) {
                        $passFail = 'Pass';
                    }
                    $rubricsGrandTotal += $rubricTotalPercent;

                    $rubricsGrandTotal = round($rubricsGrandTotal / $numOfRubricPerSch, 2);

                    $tempArray[$rubricItemCounter - 1]['TotalRubric' . '_' . $prevRubricHeaderCounter]['value'] = $rubricTotalPercent;
                    $tempArray[$rubricItemCounter - 1]['PassFail' . '_' . $prevRubricHeaderCounter]['value'] = $passFail;
                    $tempArray[$rubricItemCounter - 1]['GrandTotal']['value'] = $rubricsGrandTotal;
                  /*  pr($rubricsGrandTotal);
                pr(count($rubricTemplateWeightingInfo));
                pr($numOfRubricPerSch);
                pr('----');*/
                    $rubricTotal = $tempRubricTotal;
                    $tempRubricTotal = 0;
                    $rubricsGrandTotal = 0;
                    $numOfRubricPerSch = 0;
					/*pr($tempRubricTotal);
                    pr('/////////////////////');
                    pr('2 --> current = '.$currentRubricName);*/
                }
                $rubricName = $currentRubricName;
                $rubricId = $currentRubricId;
                $rubricItemCounter = count($tempArray);
            }
            $rubricHeaderCounter ++;

            if ($num == $dataCount - 1) {//pr('last');
                $numOfRubricPerSch ++;
               /* pr('3 --> '.$rubricName);
                pr('3 --> current = '.$currentRubricName);
                pr('rubricTotal = '.$rubricTotal);*/
                $passFail = 'Fail';
                $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];

				/*pr('$currentRubricScore :'.$currentRubricScore);
				pr('$rubricTotal :'.$rubricTotal);
				pr('$selectedWeightingInfo["TotalWeighting"] :'.$selectedWeightingInfo['TotalWeighting']);*/
                $currentRubricScore = $rubricTotal;
                $rubricTotalPercent = ($rubricTotal / $selectedWeightingInfo['TotalWeighting']) * 100;

                if ($selectedWeightingInfo['WeightingType'] == 'percent') {
                    $currentRubricScore = $rubricTotalPercent;
                }
                if ($currentRubricScore >= $selectedWeightingInfo['PassMark']) {
                    $passFail = 'Pass';
                }
				/*pr('before grandtotal : '.$rubricsGrandTotal);
				pr($rubricTotalPercent);*/
                $rubricsGrandTotal += $rubricTotalPercent;
                $rubricsGrandTotal = round($rubricsGrandTotal / $numOfRubricPerSch, 2);
                /*pr('after grandtotal : '.$rubricsGrandTotal);
                pr(count($rubricTemplateWeightingInfo));
                pr($numOfRubricPerSch);
                pr('----');*/
                $tempArray[$rubricItemCounter - 1]['TotalRubric' . '_' . $rubricHeaderCounter]['value'] = $rubricTotalPercent;
                $tempArray[$rubricItemCounter - 1]['PassFail' . '_' . $rubricHeaderCounter]['value'] = $passFail;
                $tempArray[$rubricItemCounter - 1]['GrandTotal']['value'] = $rubricsGrandTotal;
            }
        }
//die;
        return $tempArray;
    }

	public function reorderDataColumns($data, $order){
		$finalData = array();
		//pr($order);
		//pr($data);
		foreach($data as $item){
			
			foreach($item as $key => $obj){
				pr($key);
			}
		}
	}
	
    public function breakReportByYear($data, $autoGenerateFirstHeader = 'no', $header = NULL) {
        $tempArray = array();
        $selectedYear = '';
        foreach ($data as $obj) {

            if ($obj['SchoolYear']['Year'] != $selectedYear) {
				$options = array('year' =>$obj['SchoolYear']['Year'], 'header' => $header);
				if(!empty($obj['EducationGrade']['GradeId'])){
					$options['gradeId'] = $obj['EducationGrade']['GradeId'];
					unset($obj['EducationGrade']['GradeId']);
				}
				
                if (!empty($selectedYear)) {
                    //   pr($obj['SchoolYear']['name']);
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = array();
                    $tempArray[] = $this->getInstitutionQAReportHeader($obj['InstitutionSite']['InstitutionSiteId'], $options);
                } else if (empty($selectedYear) && $autoGenerateFirstHeader == 'yes') {
                    $tempArray[] = $this->getInstitutionQAReportHeader($obj['InstitutionSite']['InstitutionSiteId'], $options);
                }

                $selectedYear = $obj['SchoolYear']['Year'];
            }

            unset($obj['InstitutionSite']['InstitutionSiteId']);
            $tempArray[] = $obj;
        }
		
        return $tempArray;
    }

    /* ===================================================================================
     * Generating Header for report by appending the rubrics info at the end  
     * $header structure
     * e.g $header = array(array('School Year'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
     * =================================================================================== */

    public function getInstitutionQAReportHeader($institutionSiteId,  $options) {
	
		$header = !empty($options['header'])?$options['header'] : array();
		$year = !empty($options['year'])?$options['year'] : NULL;
		$gradeId = !empty($options['gradeId'])?$options['gradeId'] : NULL;
		
        if(!empty($institutionSiteId)){
            $RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
            if (empty($year)) {
				//pr($institutionSiteId);
				$RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
                $rubricYear = $RubricsTemplate->getLatestRubricYear($institutionSiteId);
            } else {
                $rubricYear = $year;
            }

            $rubricOptions = $this->getRubricHeader($institutionSiteId, $rubricYear, $gradeId);

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
                        if(!empty($header)){
                            $header = array_merge($header, $tempArr);
                        }
                        else{
                            $header = $tempArr;
                        }
                    }
                }

                $headerOptions = array();
                $headerOptions[][] = 'Grand Total Weighting(%)';
                $header = array_merge($header, $headerOptions);
            }
            return $header;
        }
        else{
            return array();
        }
    }

    private function getRubricTemplateWeightingInfo() {
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');

        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'rubrics_template_headers',
                'alias' => 'RubricTemplateHeader',
                'type' => 'LEFT',
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
        $options['fields'] = array('RubricsTemplate.id', 'RubricsTemplate.weighting', 'RubricsTemplate.pass_mark', 'Count(RubricTemplateItem.id) AS totalQuestion');
        $options['group'] = array('RubricsTemplate.id');
      
        $data = $RubricsTemplate->find('all', $options);
        
        $RubricTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
        $weightings = $RubricTemplateColumnInfo->getMaxWeighting();

        $list = array();
        foreach ($data AS $obj) {
            if (!empty($weightings[$obj['RubricsTemplate']['id']])) {
                $weighting = $weightings[$obj['RubricsTemplate']['id']];
            }

            $list[$obj['RubricsTemplate']['id']]['WeightingType'] = ($obj['RubricsTemplate']['weighting'] == 1) ? 'point' : 'percent';
            $list[$obj['RubricsTemplate']['id']]['PassMark'] = $obj['RubricsTemplate']['pass_mark'];
            $list[$obj['RubricsTemplate']['id']]['TotalWeighting'] = $weighting * $obj[0]['totalQuestion'];
        }

        return $list;
    }

    private function calculateRubricScore($value, $rubricTemplateWeightingInfo, $rubricHeaderCounter, $rubricId, $rubricItemCounter, &$rubricTotal, &$tempArray) {
		/*pr('========================================================');
        pr($value);
        pr($rubricTemplateWeightingInfo);*/
        foreach ($value as $sumValue) {
			/*pr('before : '.$rubricTotal);
			pr('sum value : '.$sumValue);*/
            $_sumValue = (empty($sumValue) ? 0 : $sumValue);
            //pr('process Score = '.$_sumValue);
            $rubricTotal += $_sumValue;
			//pr('after $rubricTotal = '.$rubricTotal);
            $selectedWeightingInfo = $rubricTemplateWeightingInfo[$rubricId];
            /*pr($rubricTemplateWeightingInfo);
            pr('$rubricId ====>> '.$rubricId);*/
            // if ($selectedWeightingInfo['WeightingType'] == 'percent') {
            $_sumValue = ($_sumValue == 0)?0:round(($_sumValue / $selectedWeightingInfo['TotalWeighting']) * 100, 2);
            //}
            $tempArray[$rubricItemCounter]['total_' . $rubricHeaderCounter]['value'] = $_sumValue;
        }
       // pr('----------');
		//pr('========================================================');
    }

    public function getRubricHeader($institutionSiteId, $year, $gradeId) {
        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');

        $options['order'] = array('RubricsTemplate.id');
        $options['group'] = array('RubricsTemplate.id');
        $options['recursive'] = -1;
        $options['joins'] = array(
			array(
                'table' => 'quality_institution_rubrics',
                'alias' => 'QualityInstitutionRubrics',
                'conditions' => array(
					'QualityInstitutionRubrics.rubric_template_id = RubricsTemplate.id'
				)
            ),
            array(
                'table' => 'institution_site_classes',
                'alias' => 'InstitutionSiteClass',
                'conditions' => array(
					//'InstitutionSiteClass.institution_site_id =' . $institutionSiteId,
					'InstitutionSiteClass.id = QualityInstitutionRubrics.institution_site_class_id'
				)
            ),
			array(
				'table' => 'institution_site_class_grades',
				'alias' => 'InstitutionSiteClassGrade',
				'conditions' => array(
					'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
				//	'InstitutionSiteClassGrade.education_grade_id = '.$gradeId
					)
			),
           /* array(
                'table' => 'school_years',
                'alias' => 'SchoolYear',
                'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
            ),*/
            array(
                'table' => 'quality_statuses',
                'alias' => 'QualityStatus',
                //  'type' => 'LEFT',
                'conditions' => array(//'QualityStatus.year = SchoolYear.name',
                  //  'QualityStatus.year = ' . $year,
                    'QualityStatus.rubric_template_id = RubricsTemplate.id')
            ),
        );
        $options['conditions'] = array('InstitutionSiteClass.institution_site_id' => $institutionSiteId, 'InstitutionSiteClassGrade.education_grade_id' => $gradeId, 'QualityStatus.year' => $year);
        $data = $RubricsTemplate->find('list', $options);

        return $data;
    }

    private function getCountryName($areaData = NULL) {

        $countryName = '';
        if (!empty($areaData)) {
            if (array_key_exists('AreaParentId', $areaData)) {
                if ($areaData['AreaParentId'] != -1) {
                    $Area = ClassRegistry::init('Area');
                    $Area->recursive = -1;
                    $data = $Area->findById($areaData['AreaParentId']); //pr($data);
                    if (array_key_exists('parent_id', $data['Area'])) {
                        if ($data['Area']['parent_id'] != -1) {
                            $tempData['AreaParentId'] = $data['Area']['parent_id'];
                            $tempData['Area'] = $data['Area']['name'];

                            $countryName = $this->getCountryName($tempData);
                        } else {
                            $countryName = $data['Area']['name'];
                        }
                    }
                } else if ($areaData['AreaParentId'] == -1) {
                    if (array_key_exists('Area', $areaData)) {
                        $countryName = $areaData['Area'];
                    }
                }
            }
        }


        return $countryName;
    }
}
