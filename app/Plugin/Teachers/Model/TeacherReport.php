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

class TeacherReport extends TeachersAppModel {

    public $useTable = false;
    public $actsAs = array('ControllerAction');
    public $teacherId = '';
    private $ReportData = array(); //param 1 name ; param2 type
    private $reportMapping = array(
        'QA Report' => array(
            'Model' => 'QualityInstitutionRubric',
            'fields' => array(
                'SchoolYear' => array(
                    'name' => ''
                ),
                'InstitutionSite' => array(
                    'name' => '',
                    'code' => ''
                ),
                'InstitutionSiteClass' => array(
                    'name' => '',
                    'id' => ''
                ),
                'EducationGrade' => array(
                    'name' => ''
                ),
                'RubricTemplate' => array(
                    'name' => '',
                    'id' => ''
                ),
                'RubricTemplateHeader' => array(
                    'title' => ''
                ),
                'RubricTemplateColumnInfo' => array(
                    'SUM(weighting)' => ''
                ),
            ),
            'FileName' => 'Report_Quality_Assurance'
        ),
        'Visit Report' => array(
            'Model' => 'QualityInstitutionVisit',
            'fields' => array(
               /* 'SchoolYear' => array(
                    'name' => 'School Year'
                ),
               'InstitutionSite' => array(
                    'name' => '',
                    'code' => ''
                ),
                 'InstitutionSiteClass' => array(
                    'name' => 'Class Name',
                ),
                'EducationGrade' => array(
                    'name' => 'Grade'
                ),
                'QualityVisitTypes'=> array(
                    'name' => 'Quality Type'
                ),
                'QualityInstitutionVisit' => array(
                    'date' => 'Visit Date',
                    'comment' => 'Comment'
                ),
                  'Teacher' => array(
                    'first_name' => 'Teacher First Name',
                    'middle_name' => 'Teacher Middle Name',
                    'last_name' => 'Teacher Last Name'
                ),
                'SecurityUser' => array(
                    'first_name' => 'Supervisor First Name',
                    'last_name' => 'Supervisor Last Name'
                )*/
            ),
            'FileName' => 'Report_Quality_Vist'
        )
    );
    public function report($controller, $params) {
        $this->teacherId = $controller->Session->read('TeacherObj.Teacher.id');
        
        $controller->Navigation->addCrumb('Reports - Quality');
        $data = array('Reports - Quality' => array(
                array('name' => 'QA Report', 'types' => array('CSV')),
                array('name' => 'Visit Report', 'types' => array('CSV'))/* ,
              array('name' => 'More', 'types' => array('CSV')) */
        ));
        $controller->set('data', $data);
    }

    public function reportGen($name, $type) { //$this->genReport('Site Details','CSV');
      //  $this->autoRender = false;
        $this->render = false;
        $this->ReportData['name'] = $name;
        $this->ReportData['type'] = $type;
pr($this);
       /* if (method_exists($this, 'gen' . $type)) {
            if ($type == 'CSV') {
                $data = $this->getReportData($this->ReportData['name']);
                $this->genCSV($data, $this->ReportData['name']);
            }
        }*/
    }
    
    private function getReportData($name) {
        // pr($name);die;
        if (array_key_exists($name, $this->reportMapping)) {
            $whereKey = ($this->reportMapping[$name]['Model'] == 'Teachers') ? 'id' : 'teacher_id';
            $cond = array($this->reportMapping[$name]['Model'] . "." . $whereKey => $this->$teacherId);
            $options = array('fields' => $this->getFields($name), 'conditions' => $cond);

            if($this->reportMapping[$name]['Model'] == 'QualityInstitutionVisit'){
                 $options['recursive'] = -1;
          
                 $options['joins'] = array(
                    /*  array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('QualityInstitutionVisit.school_year_id = SchoolYear.id')
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
                            'QualityInstitutionVisit.institution_site_classes_id = InstitutionSiteClass.id',
                        )
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
                        'table' => 'teachers',
                        'alias' => 'Teacher',
                        'conditions' => array('Teacher.id = QualityInstitutionVisit.teacher_id')
                    ),
                     array(
                        'table' => 'security_users',
                        'alias' => 'SecurityUser',
                        'conditions' => array('SecurityUser.id = QualityInstitutionVisit.created_user_id')
                    ),
                     array(
                        'table' => 'quality_visit_types',
                        'alias' => 'QualityVisitTypes',
                        'conditions' => array('QualityVisitTypes.id = QualityInstitutionVisit.quality_type_id')
                    )*/
                 );
               /* $this->{$this->reportMapping[$name]['Model']}->virtualFields = array(
                    'custom_value' => 'IF((InstitutionSiteCustomField.type = 3) OR (InstitutionSiteCustomField.type = 4), InstitutionSiteCustomFieldOption.value, InstitutionSiteCustomValue.value)'
                );*/
            }
            $data = $this->{$this->reportMapping[$name]['Model']}->find('all', $options);
        }

            pr($data); die;
        return $data;
    }

    private function formatCSVData($data, $name) {
        $newData = array();
        $dateFormat = 'd F, Y';

        if ($name == 'QA Report') {
            $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
            $newData = $RubricsTemplate->processDataToCSVFormat($data);
        }

        if (!empty($newData)) {
            return $newData;
        } else {
            return $data;
        }
    }

    public function genCSV($data, $name) {
        $this->autoRender = false;

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
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');
        $header_row = $this->getHeader($this->ReportData['name']);
        fputcsv($csv_file, $header_row, ',', '"');
        // Each iteration of this while loop will be a row in your .csv file where each field corresponds to the heading of the column
        foreach ($arrData as $arrSingleResult) {
            $row = array();
            foreach ($arrSingleResult as $table => $arrFields) {

                foreach ($arrFields as $col) {
                    $row[] = $col;
                }
            }
            // pr($row);
            fputcsv($csv_file, $row, ',', '"');
        }
        fclose($csv_file);
    }

}
