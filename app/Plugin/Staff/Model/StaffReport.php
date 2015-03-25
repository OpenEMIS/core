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

class StaffReport extends StaffAppModel {

    public $useTable = false;
    public $actsAs = array('ControllerAction');
    public $staffId = '';
    public $institutionSiteId = '';
    public $academicPeriod = '';
    private $ReportData = array(); //param 1 name ; param2 type
    private $reportMapping = array(
        'QA Report' => array(
            'Model' => 'InstitutionSite',
            'fields' => array(
                'AcademicPeriod' => array(
                    'name AS AcademicPeriod' => ''
                ),
                'InstitutionSite' => array(
                    //'name AS InstitutionSiteName' => '',
                    //'code AS InstitutionSiteCode' => '',
                    'id AS InstitutionSiteId' => ''
                ),
                'InstitutionSiteClass' => array(
                    'name AS Class' => '',
                    'id AS ClassId' => ''
                ),
                 'EducationGrade' => array(
                    'name AS Grade' => ''
                ),
                'RubricTemplate' => array(
                    'name AS RubricName' => '',
                    'id AS RubricId' => ''
                ),
                'RubricTemplateHeader' => array(
                    'title AS RubricHeader' => ''
                ),
                'RubricTemplateColumnInfo' => array(
                    'COALESCE(SUM(weighting),0) AS rubric_score' => ''
                ),
            ),
            'FileName' => 'Report_Staff_Quality_Assurance'
        ),
        'Visit Report' => array(
            'Model' => 'InstitutionSiteQualityVisit',
            'fields' => array(
                'AcademicPeriod' => array(
                    'name' => 'Academic Period'
                ),
                'InstitutionSite' => array(
                    'name' => 'Institution Name',
                    'code' => 'Institution Code'
                ),
                'InstitutionSiteClass' => array(
                    'name' => 'Class',
                ),
                'EducationGrade' => array(
                    'name' => 'Grade'
                ),
                'QualityVisitTypes' => array(
                    'name' => 'Quality Type'
                ),
                'InstitutionSiteQualityVisit' => array(
                    'date' => 'Visit Date',
                    'comment' => 'Comment',
					'staff_full_name' => 'Staff Name',
					'evaluator_full_name' => 'Evaluator Name',
                ),
              /*  'Staff' => array(
                    'first_name' => 'Staff First Name',
                    'middle_name' => 'Staff Middle Name',
                    'last_name' => 'Staff Last Name'
                ),
                'SecurityUser' => array(
                    'first_name' => 'Evaluator First Name',
                    'last_name' => 'Evaluator Last Name'
                )*/
            ),
            'FileName' => 'Report_Staff_Quality_Visit'
        )
    );

    public function report($controller, $params) {
        $controller->Navigation->addCrumb('Reports - Quality');
		$header = __('Reports - Quality');
        $data = array('Reports - Quality' => array(
                array('name' => 'QA Report', 'types' => array('CSV')),
                array('name' => 'Visit Report', 'types' => array('CSV'))/* ,
              array('name' => 'More', 'types' => array('CSV')) */
        ));
        $controller->set(compact('data', 'header'));
    }

    // public function reportGen($name, $type) { /
    public function reportGen($controller, $params) { //$this->genReport('Site Details','CSV');
        //  $this->autoRender = false;
        $this->staffId = $controller->Session->read('Staff.id');

        $name = $params['pass'][0];
        $type = $params['pass'][1];
        $this->render = false;
        $this->ReportData['name'] = $name;
        $this->ReportData['type'] = $type;

        //  /* if (method_exists($this, 'gen' . $type)) {
        if ($type == 'CSV') {
            $data = $this->getReportData($this->ReportData['name']);
            $this->genCSV($data, $this->ReportData['name']);
        }
        // }*/
    }

    private function getReportData($name) {
        if (array_key_exists($name, $this->reportMapping)) {
            $modal = ClassRegistry::init('Quality.' . $this->reportMapping[$name]['Model']);

            $whereKey = ($this->reportMapping[$name]['Model'] == 'Staff') ? 'id' : 'staff_id';
            $cond = array($this->reportMapping[$name]['Model'] . "." . $whereKey => $this->staffId);
            $options = array('fields' => $this->getFieldNames($name)/* , 'conditions' => $cond */);

            if ($this->reportMapping[$name]['Model'] == 'QualityInstitutionVisit') {

                $options['recursive'] = -1;
                $options['conditions'] = $cond;
                $options['joins'] = array(
                    array(
                        'table' => 'academic_periods',
                        'alias' => 'AcademicPeriod',
                        'conditions' => array('QualityInstitutionVisit.academic_period_id = AcademicPeriod.id')
                    ),
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array('QualityInstitutionVisit.institution_site_id = InstitutionSite.id')
                    ),
                    array(
                        'table' => 'institution_site_classes',
                        'alias' => 'InstitutionSiteClass',
                        'conditions' => array(
                            'QualityInstitutionVisit.institution_site_class_id = InstitutionSiteClass.id',
                        )
                    ),
                    array(
                        'table' => 'institution_site_section_grades',
                        'alias' => 'InstitutionSiteSectionGrade',
                        'conditions' => array(
                            'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteClass.institution_site_section_id',
							'InstitutionSiteSectionGrade.status = 1'
                        )
                    ),
                    array(
                        'table' => 'education_grades',
                        'alias' => 'EducationGrade',
                        'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
                    ),
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array(
                            'Staff.id = QualityInstitutionVisit.staff_id',
                            //'Staff.id = '.$this->staffId
                            )
                    ),
                    array(
                        'table' => 'security_users',
                        'alias' => 'SecurityUser',
                        'conditions' => array('SecurityUser.id = QualityInstitutionVisit.created_user_id')
                    ),
                    array(
                        'table' => 'field_option_values',
                        'alias' => 'QualityVisitTypes',
                        'conditions' => array('QualityVisitTypes.id = QualityInstitutionVisit.quality_visit_type_id')
                    )
                );
				
				$modal->virtualFields['staff_full_name'] = "CONCAT(Staff.first_name,' ',Staff.middle_name,' ',Staff.third_name,' ',Staff.last_name)";
				$modal->virtualFields['evaluator_full_name'] = "CONCAT(SecurityUser.first_name,' ',SecurityUser.last_name)";
            } else if ($this->reportMapping[$name]['Model'] == 'InstitutionSite') {

                $options['recursive'] = -1;

                $options['joins'] = array(
                    array(
                        'table' => 'quality_institution_rubrics',
                        'alias' => 'QualityInstitutionRubric',
                        'conditions' => array(
                            'QualityInstitutionRubric.institution_site_id = InstitutionSite.id',
                            'QualityInstitutionRubric.staff_id = '.$this->staffId
                        )
                    ),
                    array(
                        'table' => 'academic_periods',
                        'alias' => 'AcademicPeriod',
                      //  'type' => 'LEFT',
                        'conditions' => array('AcademicPeriod.id = QualityInstitutionRubric.academic_period_id',)
                    ),
                  array(
                        'table' => 'institution_site_classes',
                        'alias' => 'InstitutionSiteClass',
                        'conditions' => array('QualityInstitutionRubric.institution_site_class_id = InstitutionSiteClass.id',)
                    ),
                    array(
                        'table' => 'institution_site_section_grades',
                        'alias' => 'InstitutionSiteSectionGrade',
                        'conditions' => array(
                            'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteClass.institution_site_section_id',
							'InstitutionSiteSectionGrade.status = 1'
                        )
                    ),
                    array(
                        'table' => 'institution_site_class_staff',
                        'alias' => 'InstitutionSiteClassStaff',
                        'conditions' => array(
                            'InstitutionSiteClassStaff.institution_site_class_id = InstitutionSiteClass.id',
                            'InstitutionSiteClassStaff.staff_id = QualityInstitutionRubric.staff_id',
                            )
                    ),
                    
                     array(
                        'table' => 'education_grades',
                        'alias' => 'EducationGrade',
                        'conditions' => array('EducationGrade.id = InstitutionSiteSectionGrade.education_grade_id')
                    ),
                    
                    array(
                        'table' => 'quality_statuses',
                        'alias' => 'QualityStatus',
                        'conditions' => array('QualityStatus.year = AcademicPeriod.name')
                    ),
                    
                     array(
                        'table' => 'rubrics_templates',
                        'alias' => 'RubricTemplate',
                        'type' => 'LEFT',
                        'conditions' => array('RubricTemplate.id = QualityStatus.rubric_template_id')
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
                    ),
                );
                $options['order'] = array('AcademicPeriod.name DESC', 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id', 'RubricTemplateHeader.order');
                $options['group'] = array('InstitutionSiteClass.id', 'RubricTemplate.id', 'RubricTemplateHeader.id');
                
            }
            //   pr($this->reportMapping[$name]['Model']); pr($options); die;
            $data = $modal->find('all', $options);
            
         // pr($data);die;
        }
        return $data;
    }

    private function formatCSVData($data, $name) {
        $newData = array();
        $dateFormat = 'd F, Y';

        if ($name == 'QA Report') {
            $header = array(array('Academic Period'), array('Institution Name'), array('Institution Code'), array('Class'), array('Grade'));
            $QualityBatchReport = ClassRegistry::init('Quality.QualityBatchReport');
            $newData = $QualityBatchReport->processSchoolDataToCSVFormat($data);
            $newData = $QualityBatchReport->breakReportByYear($newData, 'no', $header);  //pr($newData);die;
            if(!empty($data)){
                $this->institutionSiteId = $data[0]['InstitutionSite']['InstitutionSiteId'];
                $this->academicPeriod = $data[0]['AcademicPeriod']['AcademicPeriod'];
            }
        }

        if (!empty($newData)) {
            return $newData;
        } else {
            return $data;
        }
    }

    public function genCSV($data, $name) {
        //  $this->autoRender = false;
        $this->render = false;

        $arrData = $this->formatCSVData($data, $name);

        if (array_key_exists($name, $this->reportMapping)) {
            $fileName = array_key_exists('FileName', $this->reportMapping[$name]) ? $this->reportMapping[$name]['FileName'] : "export_" . date("Y.m.d");
        } else {
            $fileName = "export_" . date("Y.m.d");
        }
        $downloadedFile = $fileName . '.csv';

        ini_set('max_execution_time', 600); //increase max_execution_time to 10 min if data set is very large
        //create a file

       $csv_file = fopen('php://output', 'w');
        header('Content-type: application/csv' );
        header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');
        $header_row = $this->getHeader($this->ReportData['name']);
//pr($header_row);
        fputcsv($csv_file, $header_row, ',', '"');
        // Each iteration of this while loop will be a row in your .csv file where each field corresponds to the heading of the column
        foreach ($arrData as $arrSingleResult) {
            $row = array();
            foreach ($arrSingleResult as $table => $arrFields) {

                foreach ($arrFields as $col) {
                    $row[] = $col;
                     // pr($col);
                }
            }
            // pr('---------');
            fputcsv($csv_file, $row, ',', '"');
        }
        
        $this->addReportDate($csv_file);
        fclose($csv_file);
    }

    public function addReportDate($csv_file){
        $footer = array(__("Report Generated").": " . date("Y-m-d H:i:s"));
        fputcsv($csv_file, array(), ',', '"');
        fputcsv($csv_file, $footer, ',', '"');
    }
    
    private function getFieldNames($name) {
        if (array_key_exists($name, $this->reportMapping)) {
            $header = $this->reportMapping[$name]['fields'];
        }
        $new = array();
        foreach ($header as $model => &$arrcols) {

            foreach ($arrcols as $col => $value) {
                if (strpos(substr($col, 0, 4), 'SUM(') !== false) {
                    $new[] = substr($col, 0, 4) . $model . "." . substr($col, 4);
                } else if (strpos(substr($col, 0, 13), 'COALESCE(SUM(') !== false) {
                    $new[] = substr($col, 0, 13) . $model . "." . substr($col, 13);
                } else {
                    $new[] = $model . "." . $col;
                }
            }
        }

        //  pr($new);die;
        return $new;
    }

    private function getHeader($name, $humanize = false) {

        if (array_key_exists($name, $this->reportMapping)) {
            $header = $this->reportMapping[$name]['fields'];
            if ($name == 'QA Report') {
				$header = $this->reportsGetHeader();
				return $header;
            }
        }
        $new = array();
        foreach ($header as $model => &$arrcols) {
            foreach ($arrcols as $col => $value) {
                if (empty($value)) {
                    $new[] = __(Inflector::humanize(Inflector::underscore($model)) . ' ' . Inflector::humanize($col));
                } else {
                    $new[] = __($value);
                }
            }
        }
        //   pr($new);die;
        return $new;
    }

	public $reportDefaultHeader = array();
	public function reportsGetHeader() {
		$this->reportDefaultHeader = array(array(__('Year')), array(__('Class')), array(__('Grade')));
		
		$institutionSiteId = $this->institutionSiteId;
		//$index = $args[1];
		$QualityInstitutionRubric = ClassRegistry::init('Quality.QualityInstitutionRubric');
		
		$QualityInstitutionRubric->unbindModel(array('belongsTo' => array('CreatedUser', 'ModifiedUser','RubricsTemplate' ,'InstitutionSiteClass')));
		$data = $QualityInstitutionRubric->find('first', array(
			//'fields' => array('AcademicPeriod.name', 'QualityInstitutionRubric.rubric_template_id'),
			'order' => array('AcademicPeriod.name DESC', ),
			'fields' => array('AcademicPeriod.*', 'QualityInstitutionRubric.*', 'InstitutionSiteClass.*','InstitutionSiteSectionGrade.*'),
			'group' => array('AcademicPeriod.name', 'QualityInstitutionRubric.rubric_template_id'),
			'conditions' => array('QualityInstitutionRubric.institution_site_id' => $institutionSiteId, 'Staff.id' => $this->staffId),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array('InstitutionSiteClass.id = QualityInstitutionRubric.institution_site_class_id')
				),
				array(
					'table' => 'institution_site_section_grades',
					'alias' => 'InstitutionSiteSectionGrade',
					'conditions' => array(
                        'InstitutionSiteSectionGrade.institution_site_section_id = InstitutionSiteClass.institution_site_section_id',
                        'InstitutionSiteSectionGrade.status = 1'
                    )
				)
			)
		));

		$year = !empty($data['AcademicPeriod']['name'])?$data['AcademicPeriod']['name'] : NULL;
		$gradeId = !empty($data['InstitutionSiteSectionGrade']['education_grade_id'])?$data['InstitutionSiteSectionGrade']['education_grade_id'] : NULL;

		$QualityBatchReport = ClassRegistry::init('Quality.QualityBatchReport');
		$headerOptions = array('year' => $year, 'gradeId' => $gradeId, 'header' => $this->reportDefaultHeader);
		$headers = $QualityBatchReport->getInstitutionQAReportHeader($institutionSiteId, $headerOptions);
	//	pr($headerOptions);
		
		return $QualityInstitutionRubric->getCSVHeader($headers);
	}
}
