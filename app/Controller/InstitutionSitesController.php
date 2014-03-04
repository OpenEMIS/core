<?php

/*
  @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

  OpenEMIS
  Open Education Management Information System

  Copyright ¬© 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by the Free Software Foundation
  , either version 3 of the License, or any later version.  This program is distributed in the hope
  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
  have received a copy of the GNU General Public License along with this program.  If not, see
  <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
 */

App::uses('AppController', 'Controller');
App::uses('AreaHandlerComponent', 'Controller/Component');

class InstitutionSitesController extends AppController {

    public $institutionSiteId;
    public $institutionSiteObj;
    public $uses = array(
        'Area',
        'AreaLevel',
        'AreaEducation',
        'AreaEducationLevel',
        'Bank',
        'BankBranch',
        'EducationSubject',
        'EducationGrade',
        'EducationGradeSubject',
        'EducationProgramme',
        'EducationFieldOfStudy',
        'EducationCertification',
        'EducationCycle',
        'EducationLevel',
        'EducationSystem',
        'AssessmentItemType',
        'AssessmentItem',
        'AssessmentItemResult',
        'AssessmentResultType',
        'Institution',
        'InstitutionSiteClass',
        'InstitutionSiteClassTeacher',
        'InstitutionSiteClassSubject',
        'InstitutionSiteClassGrade',
        'InstitutionSiteClassGradeStudent',
        'InstitutionSiteCustomField',
        'InstitutionSiteCustomFieldOption',
        'InstitutionSiteCustomValue',
        'InstitutionSite',
        'InstitutionSiteHistory',
        'InstitutionSiteOwnership',
        'InstitutionSiteLocality',
        'InstitutionSiteSector',
        'InstitutionSiteStatus',
        'InstitutionSiteProgramme',
        'InstitutionSiteAttachment',
        'InstitutionSiteBankAccount',
        'InstitutionSiteType',
        'InstitutionSiteStudent',
        'InstitutionSiteTeacher',
        'InstitutionSiteStaff',
        'CensusStudent',
        'SchoolYear',
        'Students.Student',
        'Students.StudentStatus',
        'Students.StudentCategory',
        'Students.StudentBehaviour',
        'Students.StudentBehaviourCategory',
        'Students.StudentAttendance',
        'Students.StudentDetailsCustomField',
        'Students.StudentDetailsCustomFieldOption',
        'Students.StudentDetailsCustomValue',
        'Teachers.Teacher',
        'Teachers.TeacherStatus',
        'Teachers.TeacherAttendance',
        'Teachers.TeacherCategory',
        'Teachers.TeacherPositionTitle',
        'Teachers.TeacherPositionGrade',
        'Teachers.TeacherPositionStep',
        'Teachers.TeacherBehaviour',
        'Teachers.TeacherBehaviourCategory',
        'Teachers.TeacherDetailsCustomField',
        'Teachers.TeacherDetailsCustomFieldOption',
        'Teachers.TeacherDetailsCustomValue',
        'Staff.Staff',
        'Staff.StaffStatus',
        'Staff.StaffAttendance',
        'Staff.StaffCategory',
        'Staff.StaffPositionTitle',
        'Staff.StaffPositionGrade',
        'Staff.StaffPositionStep',
        'Staff.StaffBehaviour',
        'Staff.StaffBehaviourCategory',
        'Staff.StaffDetailsCustomField',
        'Staff.StaffDetailsCustomFieldOption',
        'Staff.StaffDetailsCustomValue',
        'SecurityGroupUser',
        'SecurityGroupArea',
        'CensusStudent',
        'CensusTeacher',
        'CensusTeacherFte',
        'CensusTeacherTraining',
        'CensusStaff',
        'CensusClass',
        'CensusAttendance',
        'CensusBehaviour',
        'CensusFinance',
        'CensusAssessment',
        'CensusTextbook',
        'CensusShift',
        'CensusGraduate',
        'InfrastructureCategory',
        'InfrastructureSanitation',
        'InfrastructureStatus',
        'InfrastructureBuilding',
        'InfrastructureRoom',
        'InfrastructureWater',
        'InfrastructureEnergy',
        'InfrastructureFurniture',
        'InfrastructureResource',
        'CensusSanitation',
        'CensusBuilding',
        'CensusRoom',
        'CensusResource',
        'CensusEnergy',
        'CensusFurniture',
        'CensusWater',
        'CensusGrid',
        'CensusGridValue',
        'CensusCustomField',
        'CensusCustomValue',
        'Quality.QualityInstitutionRubric',
        'Quality.QualityInstitutionVisit',
    );
    public $helpers = array('Paginator');
    public $components = array(
        'Mpdf',
        'Paginator',
        'FileAttachment' => array(
            'model' => 'InstitutionSiteAttachment',
            'foreignKey' => 'institution_site_id'
        ),
        'AreaHandler'
    );
    private $ReportData = array(); //param 1 name ; param2 type
    private $reportMapping = array(
        'Overview' => array(
            'Model' => 'InstitutionSite',
            'fields' => array(
                'Institution' => array(
                    'name' => ''
                ),
                'InstitutionSite' => array(
                    'name' => '',
                    'code' => '',
                    'address' => '',
                    'postal_code' => '',
                    'contact_person' => '',
                    'telephone' => '',
                    'fax' => '',
                    'email' => '',
                    'website' => '',
                    'date_opened' => '',
                    'date_closed' => '',
                    'longitude' => '',
                    'latitude' => ''
                ),
                'InstitutionSiteStatus' => array(
                    'name' => 'Institution Site Status'
                ),
                'InstitutionSiteType' => array(
                    'name' => 'Institution Site Type'
                ),
                'InstitutionSiteOwnership' => array(
                    'name' => 'Institution Site Ownership'
                ),
                'Area' => array(
                    'name' => 'Area'
                ),
                'AreaEducation' => array(
                    'name' => 'Area (Education)'
                )
            ),
            'FileName' => 'Report_General_Overview'
        ),
        'Bank Accounts' => array(
            'Model' => 'InstitutionSiteBankAccount',
            'fields' => array(
                'Bank' => array(
                    'name' => ''
                ),
                'BankBranch' => array(
                    'name' => 'Branch Name'
                ),
                'InstitutionSiteBankAccount' => array(
                    'account_name' => 'Bank Account Name',
                    'account_number' => 'Bank Account Number',
                    'active' => 'Is Active'
                )
            ),
            'FileName' => 'Report_General_Bank_Accounts'
        ),
        'More' => array(
            'Model' => 'InstitutionSiteCustomValue',
            'fields' => array(
                'InstitutionSiteCustomField' => array(
                    'name' => 'Custom Field Name'
                ),
                'InstitutionSiteCustomValue' => array(
                    'custom_value' => 'Custom Field Value'
                )
            ),
            'FileName' => 'Report_General_More'
        ),
        'Classes - Students' => array(
            'Model' => 'InstitutionSiteClass',
            'fields' => array(
                'SchoolYear' => array(
                    'name' => 'School Year'
                ),
                'InstitutionSiteClass' => array(
                    'name' => 'Class Name'
                ),
                'EducationGrade' => array(
                    'name' => 'Grade'
                ),
                'Student' => array(
                    'identification_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name'
                ),
                'StudentCategory' => array(
                    'name' => 'Category'
                )
            ),
            'FileName' => 'Report_Details_Classes_Students'
        ),
        'Programme List' => array(
            'Model' => 'InstitutionSiteProgramme',
            'fields' => array(
                'SchoolYear' => array(
                    'name' => 'School Year'
                ),
                'EducationProgramme' => array(
                    'name' => 'Programme'
                ),
                'InstitutionSiteProgramme' => array(
                    'system_cycle' => 'System - Cycle'
                )
            ),
            'FileName' => 'Report_Programme_List'
        ),
        'Student List' => array(
            'Model' => 'InstitutionSiteStudent',
            'fields' => array(
                'Student' => array(
                    'identification_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name',
                    'preferred_name' => 'Preferred Name'
                ),
                'EducationProgramme' => array(
                    'name' => 'Programme'
                ),
                'StudentStatus' => array(
                    'name' => 'Status'
                )
            ),
            'FileName' => 'Report_Student_List'
        ),
        'Student Result' => array(
            'Model' => 'InstitutionSiteClassGradeStudent',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'SchoolYear' => array(
                    'name' => 'School Year'
                ),
                'InstitutionSiteClass' => array(
                    'name' => 'Class'
                ),
                'EducationGrade' => array(
                    'name' => 'Grade'
                ),
                'AssessmentItemType' => array(
                    'name' => 'Assessment'
                ),
                'Student' => array(
                    'identification_no' => 'Student OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'EducationSubject' => array(
                    'Name' => 'Subject Name',
                    'code' => 'Subject Code'
                ),
                'AssessmentItemResult' => array(
                    'marks' => 'Marks'
                ),
                'AssessmentResultType' => array(
                    'name' => 'Grading'
                )
            ),
            'FileName' => 'Report_Student_Result'
        ),
        'Student Attendance' => array(
            'Model' => 'InstitutionSiteClassGradeStudent',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'SchoolYear' => array(
                    'name' => 'School Year'
                ),
                'InstitutionSiteClass' => array(
                    'name' => 'Class'
                ),
                'EducationGrade' => array(
                    'name' => 'Grade'
                ),
                'Student' => array(
                    'identification_no' => 'Student OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'StudentAttendance' => array(
                    'total_no_attend' => 'Attended',
                    'total_no_absence' => 'Absent'
                ),
                'InstitutionSiteClassGradeStudent' => array(
                    'total' => 'Total'
                )
            ),
            'FileName' => 'Report_Student_Attendance'
        ),
        'Student Behaviour' => array(
            'Model' => 'StudentBehaviour',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Student' => array(
                    'identification_no' => 'Student OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'StudentBehaviourCategory' => array(
                    'name' => 'Category'
                ),
                'StudentBehaviour' => array(
                    'date_of_behaviour' => 'Date',
                    'title' => 'Title',
                    'description' => 'Description',
                    'action' => 'Action'
                )
            ),
            'FileName' => 'Report_Student_Behaviour'
        ),
        'Teacher List' => array(
            'Model' => 'InstitutionSiteTeacher',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Teacher' => array(
                    'identification_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name',
                    'preferred_name' => 'Preferred Name',
                    'gender' => 'Gender',
                    'date_of_birth' => 'Date of Birth'
                )
            ),
            'FileName' => 'Report_Teacher_List'
        ),
        'Teacher Attendance' => array(
            'Model' => 'TeacherAttendance',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Teacher' => array(
                    'identification_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name',
                    'preferred_name' => 'Preferred Name'
                ),
                'SchoolYear' => array(
                    'name' => 'School Year',
                    'school_days' => 'School Days'
                ),
                'TeacherAttendance' => array(
                    'total_no_attend' => 'Total Days Attended',
                    'total_no_absence' => 'Total Days Absent',
                    'total' => 'Total'
                )
            ),
            'FileName' => 'Report_Teacher_Attendance'
        ),
        'Teacher Behaviour' => array(
            'Model' => 'TeacherBehaviour',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Teacher' => array(
                    'identification_no' => 'Teacher OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'TeacherBehaviourCategory' => array(
                    'name' => 'Category'
                ),
                'TeacherBehaviour' => array(
                    'date_of_behaviour' => 'Date',
                    'title' => 'Title',
                    'description' => 'Description',
                    'action' => 'Action'
                )
            ),
            'FileName' => 'Report_Teacher_Behaviour'
        ),
        'Staff List' => array(
            'Model' => 'InstitutionSiteStaff',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Staff' => array(
                    'identification_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name',
                    'preferred_name' => 'Preferred Name',
                    'gender' => 'Gender',
                    'date_of_birth' => 'Date of Birth'
                )
            ),
            'FileName' => 'Report_Staff_List'
        ),
        'Staff Attendance' => array(
            'Model' => 'StaffAttendance',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Staff' => array(
                    'identification_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name',
                    'preferred_name' => 'Preferred Name'
                ),
                'SchoolYear' => array(
                    'name' => 'School Year',
                    'school_days' => 'School Days'
                ),
                'StaffAttendance' => array(
                    'total_no_attend' => 'Total Days Attended',
                    'total_no_absence' => 'Total Days Absent',
                    'total' => 'Total'
                )
            ),
            'FileName' => 'Report_Staff_Attendance'
        ),
        'Staff Behaviour' => array(
            'Model' => 'StaffBehaviour',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'Staff' => array(
                    'identification_no' => 'Staff OpenEMIS ID',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'preferred_name' => ''
                ),
                'StaffBehaviourCategory' => array(
                    'name' => 'Category'
                ),
                'StaffBehaviour' => array(
                    'date_of_behaviour' => 'Date',
                    'title' => 'Title',
                    'description' => 'Description',
                    'action' => 'Action'
                )
            ),
            'FileName' => 'Report_Staff_Behaviour'
        ),
        'Class List' => array(
            'Model' => 'InstitutionSiteClass',
            'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution Site'
                ),
                'SchoolYear' => array(
                    'name' => 'School Year'
                ),
                'InstitutionSiteClass' => array(
                    'name' => 'Class Name',
                    'no_of_seats' => 'Number of Seats',
                    'no_of_shifts' => 'Number of Shifts'
                )
            ),
            'FileName' => 'Report_Class_List'
        ),
        'QA Report' => array(
            'Model' => 'InstitutionSite',
            'fields' => array(
                'SchoolYear' => array(
                    'name' => ''
                ),
                'InstitutionSite' => array(
                    'name' => '',
                    'code' => '',
                    'id' => ''
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
                    'COALESCE(SUM(weighting),0)' => ''
                ),
            ),
            'FileName' => 'Report_Quality_Assurance'
        ),
        'Visit Report' => array(
            'Model' => 'QualityInstitutionVisit',
            'fields' => array(
                'SchoolYear' => array(
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
                'QualityVisitTypes' => array(
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
                )
            ),
            'FileName' => 'Report_Quality_Visit'
        )
    );
    private $reportMappingCensus = array(
        'Students' => array(
            'FileName' => 'Report_Totals_Studensts'
        ),
        'Teachers' => array(
            'FileName' => 'Report_Totals_Teachers'
        ),
        'Staff' => array(
            'FileName' => 'Report_Totals_Staff'
        ),
        'Classes' => array(
            'FileName' => 'Report_Totals_Classes'
        ),
        'Shifts' => array(
            'FileName' => 'Report_Totals_Shifts'
        ),
        'Graduates' => array(
            'FileName' => 'Report_Totals_Graduates'
        ),
        'Attendance' => array(
            'FileName' => 'Report_Totals_Attendance'
        ),
        'Results' => array(
            'FileName' => 'Report_Totals_Results'
        ),
        'Behaviour' => array(
            'FileName' => 'Report_Totals_Behaviour'
        ),
        'Textbooks' => array(
            'FileName' => 'Report_Totals_Textbooks'
        ),
        'Infrastructure' => array(
            'FileName' => 'Report_Totals_Infrastructure'
        ),
        'Finances' => array(
            'FileName' => 'Report_Totals_Finances'
        ),
        'More' => array(
            'FileName' => 'Report_Totals_More'
        ),
    );
    private $reportMappingAcademic = array(
        'Student Academic' => array(
            'FileName' => 'Report_Student_Academic'
        ),
        'Teacher Academic' => array(
            'FileName' => 'Report_Teacher_Academic'
        ),
        'Staff Academic' => array(
            'FileName' => 'Report_Staff_Academic'
        )
    );
    private $reportCensusInfraMapping = array(
        'Rooms' => array(
            'censusModel' => 'CensusRoom',
            'typesModel' => 'InfrastructureRoom',
            'typeForeignKey' => 'infrastructure_room_id'
        ),
        'Water' => array(
            'censusModel' => 'CensusWater',
            'typesModel' => 'InfrastructureWater',
            'typeForeignKey' => 'infrastructure_water_id'
        ),
        'Resources' => array(
            'censusModel' => 'CensusResource',
            'typesModel' => 'InfrastructureResource',
            'typeForeignKey' => 'infrastructure_resource_id'
        ),
        'Energy' => array(
            'censusModel' => 'CensusEnergy',
            'typesModel' => 'InfrastructureEnergy',
            'typeForeignKey' => 'infrastructure_energy_id'
        ),
        'Furniture' => array(
            'censusModel' => 'CensusFurniture',
            'typesModel' => 'InfrastructureFurniture',
            'typeForeignKey' => 'infrastructure_furniture_id'
        )
    );

    public function beforeFilter() {
        parent::beforeFilter();

        $this->Auth->allow('viewMap', 'siteProfile');

        if ($this->Session->check('InstitutionId')) {
            $institutionId = $this->Session->read('InstitutionId');
            $institutionName = $this->Institution->field('name', array('Institution.id' => $institutionId));
            $this->Navigation->addCrumb('Institutions', array('controller' => 'Institutions', 'action' => 'index'));
            $this->Navigation->addCrumb($institutionName, array('controller' => 'Institutions', 'action' => 'view'));

            if ($this->action === 'index' || $this->action === 'add') {
                $this->bodyTitle = $institutionName;
            } else {
                if ($this->Session->check('InstitutionSiteId')) {
                    $this->institutionSiteId = $this->Session->read('InstitutionSiteId');
                    $this->institutionSiteObj = $this->Session->read('InstitutionSiteObj');
                    $institutionSiteName = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
                    $this->bodyTitle = $institutionName . ' - ' . $institutionSiteName;
                    $this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view'));
                } else {
                    $this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
                }
            }
        } else {
            if ($this->action == 'siteProfile' || $this->action == 'viewMap') {
                $this->layout = 'profile';
            } else {
                $this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
            }
        }
    }

    public function index() {
        if (isset($this->params['pass'][0])) {
            $id = $this->params['pass'][0];
            $obj = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));

            if ($obj) {
                $this->Session->write('InstitutionSiteId', $id);
                $this->Session->write('InstitutionSiteObj', $obj);
                $this->redirect(array('action' => 'view'));
            } else {
                $this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
            }
        } else {
            $this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
        }
    }

    public function view() {
        $this->Navigation->addCrumb('Overview');

        $levels = $this->AreaLevel->find('list', array('recursive' => 0));
        $adminarealevels = $this->AreaEducationLevel->find('list', array('recursive' => 0));
        $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));

        $areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
        $areaLevel = array_reverse($areaLevel);

        $adminarea = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
        $adminarea = array_reverse($adminarea);

        $this->set('data', $data);
        $this->set('levels', $levels);
        $this->set('adminarealevel', $adminarealevels);

        $this->set('arealevel', $areaLevel);
        $this->set('adminarea', $adminarea);
    }

    public function viewMap($id = false) {

        $this->layout = false;
        if ($id)
            $this->institutionSiteId = $id;
        $string = @file_get_contents('http://www.google.com');
        if ($string) {
            $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
            $this->set('data', $data);
        } else {
            $this->autoRender = false;
        }
    }

    public function edit() {
        $id = $this->Session->read('InstitutionSiteId');

        $this->InstitutionSite->id = $id;
        $this->Navigation->addCrumb('Edit');

        if ($this->request->is('post')) {
            /**
             * need to sort the Area to get the the lowest level
             */
            $last_area_id = 0;
            $last_adminarea_id = 0;
            //this key sort is impt so that the lowest area level will be saved correctly
            ksort($this->request->data['InstitutionSite']);
            foreach ($this->request->data['InstitutionSite'] as $key => $arrValSave) {
                if (stristr($key, 'area_level_') == true && ($arrValSave != '' && $arrValSave != 0)) {
                    $last_area_id = $arrValSave;
                }
                if (stristr($key, 'area_education_level_') == true && ($arrValSave != '' && $arrValSave != 0)) {
                    $last_adminarea_id = $arrValSave;
                }
            }

            if ($last_area_id == 0) {
                $last_area_id = '';
            }
            $this->request->data['InstitutionSite']['area_id'] = $last_area_id;
            $this->request->data['InstitutionSite']['area_education_id'] = $last_adminarea_id;


            $this->InstitutionSite->set($this->request->data);
            if ($this->InstitutionSite->validates()) {
                $this->request->data['InstitutionSite']['latitude'] = trim($this->request->data['InstitutionSite']['latitude']);
                $this->request->data['InstitutionSite']['longitude'] = trim($this->request->data['InstitutionSite']['longitude']);

                $rec = $this->InstitutionSite->save($this->request->data);

                $this->redirect(array('action' => 'view'));
            }

            /**
             * preserve the dropdown values on error
             */
            if ($last_area_id != 0) {
                $areaLevel = $this->AreaHandler->getAreatoParent($last_area_id);

                $areaLevel = array_reverse($areaLevel);
                $areadropdowns = array();
                foreach ($areaLevel as $index => &$arrVals) {
                    $siblings = $this->Area->find('list', array('conditions' => array('Area.parent_id' => $arrVals['parent_id'])));
                    $this->Utility->unshiftArray($siblings, array('0' => '--' . __('Select') . '--'));
                    $areadropdowns['area_level_' . $index]['options'] = $siblings;
                }
                $maxAreaIndex = max(array_keys($areaLevel)); //starts with 0
                $totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
                for ($i = $maxAreaIndex; $i <= $totalAreaLevel; $i++) {
                    $areadropdowns['area_level_' . ($i + 1)]['options'] = array('0' => '--' . __('Select') . '--');
                }
            }

            if ($last_adminarea_id != 0) {
                $adminareaLevel = $this->AreaHandler->getAreatoParent($last_adminarea_id, array('AreaEducation', 'AreaEducationLevel'));

                $adminareaLevel = array_reverse($adminareaLevel);

                $adminareadropdowns = array();
                foreach ($adminareaLevel as $index => &$arrVals) {
                    $siblings = $this->AreaEducation->find('list', array('conditions' => array('AreaEducation.parent_id' => $arrVals['parent_id'])));
                    $this->Utility->unshiftArray($siblings, array('0' => '--' . __('Select') . '--'));
                    $adminareadropdowns['area_education_level_' . $index]['options'] = $siblings;
                }


                $maxAreaIndex = max(array_keys($adminareaLevel)); //starts with 0
                $totalAreaLevel = $this->AreaEducationLevel->find('count'); //starts with 1
                for ($i = $maxAreaIndex; $i <= $totalAreaLevel; $i++) {
                    $adminareadropdowns['area_education_level_' . ($i + 1)]['options'] = array('0' => '--' . __('Select') . '--');
                }
            }
        } else {

            $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));
            $this->set('data', $data);

            $areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
            $areaLevel = array_reverse($areaLevel);

            $adminareaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
            $adminareaLevel = array_reverse($adminareaLevel);

            $areadropdowns = $this->AreaHandler->getAllSiteAreaToParent($data['InstitutionSite']['area_id']);
            //pr($areadropdowns);
            //pr($data['InstitutionSite']);
            if (!is_null($data['InstitutionSite']['area_education_id'])) {
                $adminareadropdowns = $this->AreaHandler->getAllSiteAreaToParent($data['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
            } else {
                $topEdArea = $this->AreaEducation->find('list', array('conditions' => array('parent_id' => -1)));
                $arr[] = '--' . __('Select') . '--';
                foreach ($topEdArea as $k => $v) {
                    $arr[] = array('name' => $v, 'value' => $k);
                }
                $adminareadropdowns = array('area_education_level_0' => array('options' => $arr));
            }
        }

        $topArea = $this->Area->find('list', array('conditions' => array('Area.parent_id' => '-1')));
        $disabledAreas = $this->Area->find('list', array('conditions' => array('Area.visible' => '0')));
        $this->Utility->unshiftArray($topArea, array('0' => '--' . __('Select') . '--'));
        $levels = $this->AreaLevel->find('list');
        $adminlevels = $this->AreaEducationLevel->find('list');
        $visible = true;
        $type = $this->InstitutionSiteType->findList($visible);
        $ownership = $this->InstitutionSiteOwnership->findList($visible);
        $locality = $this->InstitutionSiteLocality->findList($visible);
        $status = $this->InstitutionSiteStatus->findList($visible);
        $this->Utility->unshiftArray($type, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($ownership, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($locality, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($status, array('0' => '--' . __('Select') . '--'));

        // Get security group area
        $groupId = $this->SecurityGroupUser->getGroupIdsByUserId($this->Auth->user('id'));
        $filterArea = $this->SecurityGroupArea->getAreas($groupId);

        $this->set('filterArea', $filterArea);
        $this->set('type_options', $type);
        $this->set('ownership_options', $ownership);
        $this->set('locality_options', $locality);
        $this->set('status_options', $status);
        //$this->set('arealevel',$areaLevel);
        //$this->set('adminarealevel',$adminareaLevel);
        //$this->set('levels',$levels);
        //$this->set('adminlevels',$adminlevels);
        //$this->set('areadropdowns',$areadropdowns);
        //$this->set('adminareadropdowns',$adminareadropdowns);

        $this->set('disabledAreas', $disabledAreas);


        $this->set('highestLevel', $topArea);
    }

    public function add() {

        $this->Navigation->addCrumb('Add New Institution Site');
        $institutionId = $this->Session->read('InstitutionId');
        $areadropdowns = array('0' => '--' . __('Select') . '--');
        $adminareadropdowns = array('0' => '--' . __('Select') . '--');
        $areaLevel = array();
        if ($this->request->is('post')) {

            $last_area_id = 0;
            //this key sort is impt so that the lowest area level will be saved correctly
            ksort($this->request->data['InstitutionSite']);
            foreach ($this->request->data['InstitutionSite'] as $key => $arrValSave) {
                if (stristr($key, 'area_level_') == true && ($arrValSave != '' && $arrValSave != 0)) {
                    $last_area_id = $arrValSave;
                }
                if (stristr($key, 'area_level_') == true) {
                    unset($this->request->data['InstitutionSite'][$key]);
                }
            }
            //pr($this->request->data);die;
            $this->request->data['InstitutionSite']['area_id'] = $last_area_id;
            $this->InstitutionSite->set($this->request->data);

            if ($this->InstitutionSite->validates()) {
                $newInstitutionSiteRec = $this->InstitutionSite->save($this->request->data);

                $institutionSiteId = $newInstitutionSiteRec['InstitutionSite']['id'];

                //** Reinitialize the Site Session by adding the newly added site **/
                $tmp = $this->Session->read('AccessControl.sites');
                array_push($tmp, $institutionSiteId);
                $this->Session->write('AccessControl.sites', $tmp);

                //** Reinitialize the Institution + Site Session by adding the newly added site **/
                $sites = $this->Session->read('AccessControl.institutions');
                $sites[$newInstitutionSiteRec['InstitutionSite']['institution_id']][] = $institutionSiteId;
                $this->Session->write('AccessControl.institutions', $sites);
                $this->Session->write('InstitutionSiteId', $institutionSiteId);

                $this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
            }
            /**
             * preserve the dropdown values on error
             */
            if ($last_area_id != 0) {

                $areaLevel = $this->AreaHandler->getAreatoParent($last_area_id);
                $areaLevel = array_reverse($areaLevel);
                $areadropdowns = array();
                foreach ($areaLevel as $index => &$arrVals) {
                    $siblings = $this->Area->find('list', array('conditions' => array('Area.parent_id' => $arrVals['parent_id'])));
                    $this->Utility->unshiftArray($siblings, array('0' => '--' . __('Select') . '--'));
                    $areadropdowns['area_level_' . $index]['options'] = $siblings;
                }
                $maxAreaIndex = max(array_keys($areaLevel)); //starts with 0
                $totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
                for ($i = $maxAreaIndex; $i <= $totalAreaLevel; $i++) {
                    $areadropdowns['area_level_' . ($i + 1)]['options'] = array('0' => '--' . __('Select') . '--');
                }
            }
        }
        $visible = true;
        $type = $this->InstitutionSiteType->findList($visible);
        $ownership = $this->InstitutionSiteOwnership->findList($visible);
        $locality = $this->InstitutionSiteLocality->findList($visible);
        $status = $this->InstitutionSiteStatus->findList($visible);
        $this->Utility->unshiftArray($type, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($ownership, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($locality, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($status, array('0' => '--' . __('Select') . '--'));

        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list', array('conditions' => array('Area.parent_id' => '-1', 'Area.visible' => 1)));

        $topAdminArea = $this->AreaEducation->find('list', array('conditions' => array('AreaEducation.parent_id' => '-1', 'AreaEducation.visible' => 1)));

        $this->Utility->unshiftArray($topArea, array('0' => '--' . __('Select') . '--'));
        $this->Utility->unshiftArray($topAdminArea, array('0' => '--' . __('Select') . '--'));

        $adminlevels = $this->AreaEducationLevel->find('list');

        // Get security group area
        $groupId = $this->SecurityGroupUser->getGroupIdsByUserId($this->Auth->user('id'));
        $filterArea = $this->SecurityGroupArea->getAreas($groupId);

        $this->set('filterArea', $filterArea);
        $this->set('type_options', $type);
        $this->set('ownership_options', $ownership);
        $this->set('locality_options', $locality);
        $this->set('status_options', $status);
        $this->set('institutionId', $institutionId);
        $this->set('arealevel', $areaLevel);
        $this->set('levels', $levels);

        $this->set('adminarealevel', $areaLevel);
        $this->set('adminlevels', $adminlevels);

        $this->set('areadropdowns', $areadropdowns);
        $this->set('adminareadropdowns', $adminareadropdowns);
        $this->set('highestLevel', $topArea);
        $this->set('highestAdminLevel', $topAdminArea);
    }

    public function delete() {
        $id = $this->Session->read('InstitutionSiteId');
        $name = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $id));
        $this->InstitutionSite->delete($id);
        $this->Utility->alert($name . ' have been deleted successfully.');
        $this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
    }

    public function attachments() {
        $this->Navigation->addCrumb('Attachments');
        $id = $this->Session->read('InstitutionSiteId');
        $data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/view');
    }

    public function attachmentsEdit() {
        $this->Navigation->addCrumb('Edit Attachments');
        $id = $this->Session->read('InstitutionSiteId');

        if ($this->request->is('post')) { // save
            $errors = $this->FileAttachment->saveAll($this->data, $_FILES, $id);
            if (sizeof($errors) == 0) {
                $this->Utility->alert('Files have been saved successfully.');
                $this->redirect(array('action' => 'attachments'));
            } else {
                $this->Utility->alert('Some errors have been encountered while saving files.', array('type' => 'error'));
            }
        }

        $data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/edit');
    }

    public function attachmentsAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->render('/Elements/attachment/add');
    }

    public function attachmentsDelete() {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            if ($this->FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }

            return json_encode($result);
        }
    }

    public function attachmentsDownload($id) {
        $this->FileAttachment->download($id);
    }

    public function additional() {
        $this->Navigation->addCrumb('More');

        $datafields = $this->InstitutionSiteCustomField->find('all', array('conditions' => array('InstitutionSiteCustomField.visible' => 1, 'InstitutionSiteCustomField.institution_site_type_id' => (array($this->institutionSiteObj['InstitutionSite']['institution_site_type_id'], 0))), 'order' => array('InstitutionSiteCustomField.institution_site_type_id', 'InstitutionSiteCustomField.order')));
        $this->InstitutionSiteCustomValue->unbindModel(
                array('belongsTo' => array('InstitutionSite'))
        );
        $datavalues = $this->InstitutionSiteCustomValue->find('all', array('conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $this->institutionSiteId)));
        $tmp = array();
        foreach ($datavalues as $arrV) {
            $tmp[$arrV['InstitutionSiteCustomField']['id']][] = $arrV['InstitutionSiteCustomValue'];
        }
        $datavalues = $tmp;
        //pr($datafields);die;
        $this->set('datafields', $datafields);
        $this->set('datavalues', $tmp);
    }

    public function additionalEdit() {
        $this->Navigation->addCrumb('Edit More');

        if ($this->request->is('post')) {
            //pr($this->data);
            //die();
            $arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea');
            /**
             * Note to Preserve the Primary Key to avoid exhausting the max PK limit
             */
            foreach ($arrFields as $fieldVal) {
                if (!isset($this->request->data['InstitutionsSiteCustomFieldValue'][$fieldVal]))
                    continue;
                foreach ($this->request->data['InstitutionsSiteCustomFieldValue'][$fieldVal] as $key => $val) {
                    if ($fieldVal == "checkbox") {

                        $arrCustomValues = $this->InstitutionSiteCustomValue->find('list', array('fields' => array('value'), 'conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $this->institutionSiteId, 'InstitutionSiteCustomValue.institution_site_custom_field_id' => $key)));

                        $tmp = array();
                        if (count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                            foreach ($arrCustomValues as $pk => $intVal) {
                                //pr($val['value']); echo "$intVal";
                                if (!in_array($intVal, $val['value'])) {
                                    //echo "not in db so remove \n";
                                    $this->InstitutionSiteCustomValue->delete($pk);
                                }
                            }
                        $ctr = 0;
                        if (count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                            foreach ($val['value'] as $intVal) {
                                //pr($val['value']); echo "$intVal";
                                if (!in_array($intVal, $arrCustomValues)) {
                                    $this->InstitutionSiteCustomValue->create();
                                    $arrV['institution_site_custom_field_id'] = $key;
                                    $arrV['value'] = $val['value'][$ctr];
                                    $arrV['institution_site_id'] = $this->institutionSiteId;
                                    $this->InstitutionSiteCustomValue->save($arrV);
                                    unset($arrCustomValues[$ctr]);
                                }
                                $ctr++;
                            }
                    } else { // if editing reuse the Primary KEY; so just update the record
                        $x = $this->InstitutionSiteCustomValue->find('first', array('fields' => array('id', 'value'), 'conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $this->institutionSiteId, 'InstitutionSiteCustomValue.institution_site_custom_field_id' => $key)));
                        $this->InstitutionSiteCustomValue->create();
                        if ($x)
                            $this->InstitutionSiteCustomValue->id = $x['InstitutionSiteCustomValue']['id'];
                        $arrV['institution_site_custom_field_id'] = $key;
                        $arrV['value'] = $val['value'];
                        $arrV['institution_site_id'] = $this->institutionSiteId;
                        $this->InstitutionSiteCustomValue->save($arrV);
                    }
                }
            }
            $this->redirect(array('action' => 'additional'));
        }

        $this->institutionSiteObj['InstitutionSite'];
        $datafields = $this->InstitutionSiteCustomField->find('all', array('conditions' => array('InstitutionSiteCustomField.visible' => 1, 'InstitutionSiteCustomField.institution_site_type_id' => (array($this->institutionSiteObj['InstitutionSite']['institution_site_type_id'], 0))), 'order' => array('InstitutionSiteCustomField.institution_site_type_id', 'InstitutionSiteCustomField.order')));
        //$datafields = $this->InstitutionSiteCustomField->find('all',array('conditions'=>array('InstitutionSiteCustomField.visible'=>1,'InstitutionSiteCustomField.institution_site_type_id'=>$this->institutionSiteObj['InstitutionSite']['institution_site_type_id'])));

        $this->InstitutionSiteCustomValue->unbindModel(
                array('belongsTo' => array('InstitutionSite'))
        );
        $datavalues = $this->InstitutionSiteCustomValue->find('all', array('conditions' => array('InstitutionSiteCustomValue.institution_site_id' => $this->institutionSiteId)));
        $tmp = array();
        foreach ($datavalues as $arrV) {
            $tmp[$arrV['InstitutionSiteCustomField']['id']][] = $arrV['InstitutionSiteCustomValue'];
        }
        $datavalues = $tmp;
        // pr($tmp);die;
        // pr($datafields);
        $this->set('datafields', $datafields);
        $this->set('datavalues', $tmp);
    }

    public function bankAccounts() {
        $this->Navigation->addCrumb('Bank Accounts');

        $data = $this->InstitutionSiteBankAccount->find('all', array('conditions' => array('InstitutionSiteBankAccount.institution_site_id' => $this->institutionSiteId)));
        $bank = $this->Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        $banklist = $this->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        $this->set('data', $data);
        $this->set('bank', $bank);
        $this->set('banklist', $banklist);
    }

    public function bankAccountsView() {
        $bankAccountId = $this->params['pass'][0];
        $bankAccountObj = $this->InstitutionSiteBankAccount->find('all', array('conditions' => array('InstitutionSiteBankAccount.id' => $bankAccountId)));

        if (!empty($bankAccountObj)) {
            $this->Navigation->addCrumb('Bank Account Details');

            $this->Session->write('InstitutionSiteBankAccountId', $bankAccountId);
            $this->set('bankAccountObj', $bankAccountObj);
        }
        $banklist = $this->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        $this->set('banklist', $banklist);
    }

    public function bankAccountsAdd() {
        $this->Navigation->addCrumb('Add Bank Accounts');
        if ($this->request->is('post')) { // save
            $this->InstitutionSiteBankAccount->create();
            if ($this->InstitutionSiteBankAccount->save($this->request->data)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'bankAccounts'));
            }
        }
        $bank = $this->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));

        $bankId = isset($this->request->data['InstitutionSiteBankAccount']['bank_id']) ? $this->request->data['InstitutionSiteBankAccount']['bank_id'] : "";
        if (!empty($bankId)) {
            $bankBranches = $this->BankBranch->find('list', array('conditions' => array('bank_id' => $bankId, 'visible' => 1), 'recursive' => -1));
        } else {
            $bankBranches = array();
        }

        $this->set('bankBranches', $bankBranches);
        $this->set('selectedBank', $bankId);

        $this->set('institution_site_id', $this->institutionSiteId);
        $this->set('bank', $bank);
    }

    public function bankAccountsEdit() {
        $bankBranch = array();

        $bankAccountId = $this->params['pass'][0];

        if ($this->request->is('get')) {
            $bankAccountObj = $this->InstitutionSiteBankAccount->find('first', array('conditions' => array('InstitutionSiteBankAccount.id' => $bankAccountId)));

            if (!empty($bankAccountObj)) {
                $this->Navigation->addCrumb('Edit Bank Account Details');
                //$bankAccountObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $this->request->data = $bankAccountObj;
            }
        } else {
            $this->request->data['InstitutionSiteBankAccount']['institution_site_id'] = $this->institutionSiteId;
            if ($this->InstitutionSiteBankAccount->save($this->request->data)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'bankAccountsView', $this->request->data['InstitutionSiteBankAccount']['id']));
            }
        }

        $bankId = isset($this->request->data['InstitutionSiteBankAccount']['bank_id']) ? $this->request->data['InstitutionSiteBankAccount']['bank_id'] : $bankAccountObj['BankBranch']['bank_id'];
        $this->set('selectedBank', $bankId);

        $bankBranch = $this->BankBranch->find('list', array('conditions' => array('bank_id' => $bankId, 'visible' => 1), 'recursive' => -1));
        $this->set('bankBranch', $bankBranch);

        $bank = $this->Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));
        $this->set('bank', $bank);

        $this->set('id', $bankAccountId);
    }

    public function bankAccountsDelete($id) {
        if ($this->Session->check('InstitutionSiteId') && $this->Session->check('InstitutionSiteBankAccountId')) {
            $id = $this->Session->read('InstitutionSiteBankAccountId');
            $institutionSiteId = $this->Session->read('InstitutionSiteId');
            $name = $this->InstitutionSiteBankAccount->field('account_number', array('InstitutionSiteBankAccount.id' => $id));
            $this->InstitutionSiteBankAccount->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'bankAccounts'));
        }
    }

    public function bankAccountsBankBranches() {
        $this->autoRender = false;
        $bank = $this->Bank->find('all', array('conditions' => Array('Bank.visible' => 1)));
        echo json_encode($bank);
    }

    public function programmesGradeList() {
        $this->layout = 'ajax';
        $programmeId = $this->params->query['programmeId'];
        $exclude = $this->params->query['exclude'];
        $gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, $exclude);
        $this->set('gradeOptions', $gradeOptions);
        $this->render('/Elements/programmes/grade_options');
    }

    public function programmes() {
        $this->Navigation->addCrumb('Programmes');

        $yearOptions = $this->SchoolYear->getAvailableYears();
        $selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
        $data = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);

        $this->set('yearOptions', $yearOptions);
        $this->set('selectedYear', $selectedYear);
        $this->set('data', $data);
    }

    public function programmesEdit() {
        if ($this->request->is('get')) {
            $this->Navigation->addCrumb('Edit Programmes');

            $yearOptions = $this->SchoolYear->getAvailableYears();
            $selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
            $data = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);

            $this->set('yearOptions', $yearOptions);
            $this->set('selectedYear', $selectedYear);
            $this->set('data', $data);
        } else {
            $this->autoRender = false;
        }
    }

    public function programmesAdd() {
        $yearId = $this->params['pass'][0];
        if ($this->request->is('get')) {
            $this->layout = 'ajax';

            $data = $this->EducationProgramme->getAvailableProgrammeOptions($this->institutionSiteId, $yearId);
            $_delete_programme = $this->AccessControl->check('InstitutionSites', 'programmesDelete');
            $this->set('data', $data);
            $this->set('_delete_programme', $_delete_programme);
        } else {
            $this->autoRender = false;
            $programmeId = $this->params->data['programmeId'];

            $obj = array(
                'education_programme_id' => $programmeId,
                'institution_site_id' => $this->institutionSiteId,
                'school_year_id' => $yearId
            );

            $this->InstitutionSiteProgramme->create();
            $result = $this->InstitutionSiteProgramme->save($obj);
            $return = array();
            if ($result) {
                $this->Utility->setAjaxResult('success', $return);
            } else {
                $this->Utility->setAjaxResult('error', $return);
                $return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
            }
            return json_encode($return);
        }
    }

    public function programmesDelete() {
        if (count($this->params['pass']) == 2) {
            $this->autoRender = false;
            $yearId = $this->params['pass'][0];
            $id = $this->params['pass'][1];

            $this->InstitutionSiteProgramme->delete($id, false);
            $this->Utility->alert($this->Utility->getMessage('DELETE_SUCCESS'));
            $this->redirect(array('action' => 'programmes', $yearId));
        }
    }

    public function programmesOptions() {
        $this->layout = 'ajax';

        $yearId = $this->params->query['yearId'];
        $programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeForSelection($this->institutionSiteId, $yearId, false);
        $this->set('programmeOptions', $programmeOptions);
        $this->render('/Elements/programmes/programmes_options');
    }

    public function history() {
        $this->Navigation->addCrumb('History');

        $arrTables = array('InstitutionSiteHistory', 'InstitutionSiteStatus', 'InstitutionSiteType', 'InstitutionSiteOwnership', 'InstitutionSiteLocality', 'Area');
        $historyData = $this->InstitutionSiteHistory->find('all', array('conditions' => array('InstitutionSiteHistory.institution_site_id' => $this->institutionSiteId), 'order' => array('InstitutionSiteHistory.created' => 'desc')));
        //pr($historyData);
        $data2 = array();
        foreach ($historyData as $key => $arrVal) {

            foreach ($arrTables as $table) {
                //pr($arrVal);die;
                foreach ($arrVal[$table] as $k => $v) {
                    $keyVal = ($k == 'name') ? $table . '_name' : $k;
                    $keyVal = ($k == 'code') ? $table . '_code' : $keyVal;
                    //echo $k.'<br>';
                    $data2[$keyVal][$v] = $arrVal['InstitutionSiteHistory']['created'];
                }
            }
        }

        if (empty($data2)) {
            $this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
        } else {
            $adminarealevels = $this->AreaEducationLevel->find('list', array('recursive' => 0));
            $arrEducation = array();
            foreach ($data2['area_education_id'] as $val => $time) {
                if ($val > 0) {
                    $adminarea = $this->AreaHandler->getAreatoParent($val, array('AreaEducation', 'AreaEducationLevel'));
                    $adminarea = array_reverse($adminarea);

                    $arrVal = '';
                    foreach ($adminarealevels as $levelid => $levelName) {
                        $areaVal = array('id' => '0', 'name' => 'a');
                        foreach ($adminarea as $arealevelid => $arrval) {
                            if ($arrval['level_id'] == $levelid) {
                                $areaVal = $arrval;
                                $arrVal .= ($areaVal['name'] == 'a' ? '' : $areaVal['name']) . ' (' . $levelName . ') ' . ',';
                                continue;
                            }
                        }
                    }
                    $arrEducation[] = array('val' => str_replace(',', ' &rarr; ', rtrim($arrVal, ',')), 'time' => $time);
                }
            }

            $myData = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
            $adminarea = $this->AreaHandler->getAreatoParent($myData['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
            $adminarea = array_reverse($adminarea);
            $arrVal = '';
            foreach ($adminarealevels as $levelid => $levelName) {
                $areaVal = array('id' => '0', 'name' => 'a');
                foreach ($adminarea as $arealevelid => $arrval) {
                    if ($arrval['level_id'] == $levelid) {
                        $areaVal = $arrval;
                        $arrVal .= ($areaVal['name'] == 'a' ? '' : $areaVal['name']) . ' (' . $levelName . ') ' . ',';
                        continue;
                    }
                }
            }
            $arrEducationVal = str_replace(',', ' &rarr; ', rtrim($arrVal, ','));
            $this->set('arrEducation', $arrEducation);
            $this->set('arrEducationVal', $arrEducationVal);
        }
        $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $this->institutionSiteId)));
        $this->set('data', $data);
        $this->set('data2', $data2);
        $this->set('id', $this->institutionSiteId);
    }

    public function classes() {
        $this->Navigation->addCrumb('List of Classes');
        $yearOptions = $this->SchoolYear->getYearList();
        $selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearOptions);
        $data = $this->InstitutionSiteClass->getListOfClasses($selectedYear, $this->institutionSiteId);

        // Checking if user has access to add
        $_add_class = $this->AccessControl->check('InstitutionSites', 'classesAdd');
        $this->set('_add_class', $_add_class);
        // End Access Control

        $this->set('yearOptions', $yearOptions);
        $this->set('selectedYear', $selectedYear);
        $this->set('data', $data);
    }

    public function classesAdd() {
        if ($this->request->is('get')) {
            $this->Navigation->addCrumb('Add Class');
            $years = $this->SchoolYear->getYearList();
            $yearOptions = array();

            $programmeOptions = array();
            foreach ($years as $yearId => $year) {
                $programmes = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId, $yearId);
                if (!empty($programmes)) {
                    $yearOptions[$yearId] = $year;
                    if (empty($programmeOptions)) {
                        $programmeOptions = $programmes;
                    }
                }
            }
            $displayContent = !empty($programmeOptions);

            if ($displayContent) {
                $gradeOptions = array();
                $selectedProgramme = false;
                // loop through the programme list until a valid list of grades is found
                foreach ($programmeOptions as $programmeId => $name) {
                    $gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, array(), true);
                    if (!empty($gradeOptions)) {
                        $selectedProgramme = $programmeId;
                        break;
                    }
                }

                $this->set('yearOptions', $yearOptions);
                $this->set('programmeOptions', $programmeOptions);
                $this->set('selectedProgramme', $selectedProgramme);
                $this->set('gradeOptions', $gradeOptions);
            } else {
                $this->Utility->alert($this->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
            }
            $this->set('displayContent', $displayContent);
        } else {
            $classData = $this->data['InstitutionSiteClass'];
            $classData['institution_site_id'] = $this->institutionSiteId;
            $this->InstitutionSiteClass->create();
            $classObj = $this->InstitutionSiteClass->save($classData);
            if ($classObj) {
                $classId = $classObj['InstitutionSiteClass']['id'];
                $gradesData = $this->data['InstitutionSiteClassGrade'];
                $grades = array();
                foreach ($gradesData as $obj) {
                    $gradeId = $obj['education_grade_id'];
                    if ($gradeId > 0 && !in_array($gradeId, $grades)) {
                        $grades[] = $obj['education_grade_id'];
                        $obj['institution_site_class_id'] = $classId;
                        $this->InstitutionSiteClassGrade->create();
                        $this->InstitutionSiteClassGrade->save($obj);
                    }
                }
            }
            $this->redirect(array('action' => 'classesEdit', $classId));
        }
    }

    public function classesView() {
        $classId = $this->params['pass'][0];
        $this->Session->write('InstitutionSiteClassId', $classId);
        $classObj = $this->InstitutionSiteClass->getClass($classId);

        if (!empty($classObj)) {
            $className = $classObj['InstitutionSiteClass']['name'];
            $this->Navigation->addCrumb($className);

            $grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
            $students = $this->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
            $teachers = $this->InstitutionSiteClassTeacher->getTeachers($classId);
            $subjects = $this->InstitutionSiteClassSubject->getSubjects($classId);

            $this->set('classId', $classId);
            $this->set('className', $className);
            $this->set('yearId', $classObj['SchoolYear']['id']);
            $this->set('year', $classObj['SchoolYear']['name']);
            $this->set('grades', $grades);
            $this->set('students', $students);
            $this->set('teachers', $teachers);
            $this->set('no_of_seats', $classObj['InstitutionSiteClass']['no_of_seats']);
            $this->set('no_of_shifts', $classObj['InstitutionSiteClass']['no_of_shifts']);

            $this->set('subjects', $subjects);
        } else {
            $this->redirect(array('action' => 'classesList'));
        }
    }

    public function classesEdit() {
        $classId = $this->params['pass'][0];
        $classObj = $this->InstitutionSiteClass->getClass($classId);

        if (!empty($classObj)) {
            $className = $classObj['InstitutionSiteClass']['name'];
            $this->Navigation->addCrumb(__('Edit') . ' ' . $className);

            $grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
            $students = $this->InstitutionSiteClassGradeStudent->getStudentsByGrade(array_keys($grades));
            $teachers = $this->InstitutionSiteClassTeacher->getTeachers($classId);
            $subjects = $this->InstitutionSiteClassSubject->getSubjects($classId);
            $studentCategoryOptions = $this->StudentCategory->findList(true);

            $this->set('classId', $classId);
            $this->set('className', $className);
            $this->set('year', $classObj['SchoolYear']['name']);
            $this->set('grades', $grades);
            $this->set('students', $students);
            $this->set('teachers', $teachers);
            $this->set('no_of_seats', $classObj['InstitutionSiteClass']['no_of_seats']);
            $this->set('no_of_shifts', $classObj['InstitutionSiteClass']['no_of_shifts']);
            $this->set('studentCategoryOptions', $studentCategoryOptions);

            $this->set('subjects', $subjects);
        } else {
            $this->redirect(array('action' => 'classesList'));
        }
    }

    public function classesDelete() {
        $id = $this->params['pass'][0];
        $name = $this->InstitutionSiteClass->field('name', array('InstitutionSiteClass.id' => $id));
        $this->InstitutionSiteClass->delete($id);
        $this->Utility->alert($name . ' have been deleted successfully.');
        $this->redirect(array('action' => 'classes'));
    }

    public function classesAddGrade() {
        $this->layout = 'ajax';
        $exclude = isset($this->params->query['exclude']) ? $this->params->query['exclude'] : array();
        $index = $this->params->query['index'];
        $yearId = $this->params->query['yearId'];
        $programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId, $yearId);

        $gradeOptions = array();
        $selectedProgramme = false;
        foreach ($programmeOptions as $programmeId => $name) {
            $gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, $exclude, true);
            if (!empty($gradeOptions)) {
                $selectedProgramme = $programmeId;
                break;
            }
        }
        $this->set('model', 'InstitutionSiteClassGrade');
        $this->set('index', $index);
        $this->set('gradeOptions', $gradeOptions);
        $this->set('programmeOptions', $programmeOptions);
        $this->set('selectedProgramme', $selectedProgramme);
    }

    public function classesStudentAjax() {
        $this->autoRender = false;

        if (sizeof($this->params['pass']) == 1) {
            $gradeId = $this->params['pass'][0];
            $studentId = $this->params->query['studentId'];
            $action = $this->params->query['action'];

            $result = false;
            if ($action === 'add') {
                $categoryId = $this->params->query['categoryId'];

                $data = array(
                    'student_id' => $studentId,
                    'student_category_id' => $categoryId,
                    'institution_site_class_grade_id' => $gradeId
                );
                $this->InstitutionSiteClassGradeStudent->create();
                $result = $this->InstitutionSiteClassGradeStudent->save($data);
            } else if ($action === 'change_category') {
                $categoryId = $this->params->query['categoryId'];

                $fieldsToBeUpdated = array('InstitutionSiteClassGradeStudent.student_category_id' => $categoryId);
                $updateConditions = array(
                    'InstitutionSiteClassGradeStudent.student_id' => $studentId,
                    'InstitutionSiteClassGradeStudent.institution_site_class_grade_id' => $gradeId
                );
                $result = $this->InstitutionSiteClassGradeStudent->updateAll($fieldsToBeUpdated, $updateConditions);
            } else {
                $result = $this->InstitutionSiteClassGradeStudent->deleteAll(array(
                    'InstitutionSiteClassGradeStudent.student_id' => $studentId,
                    'InstitutionSiteClassGradeStudent.institution_site_class_grade_id' => $gradeId
                        ), false);
            }

            $return = array();
            if ($result) {
                $this->Utility->setAjaxResult('success', $return);
            } else {
                $this->Utility->setAjaxResult('error', $return);
                $return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
            }
            return json_encode($return);
        }
    }

    public function classesAddStudentRow() {
        $this->layout = 'ajax';

        if (sizeof($this->params['pass']) == 2) {
            $year = $this->params['pass'][0];
            $gradeId = $this->params['pass'][1];
            $index = $this->params->query['index'];
            $data = $this->InstitutionSiteStudent->getStudentSelectList($year, $this->institutionSiteId, $gradeId);
            $categoryOptions = $this->StudentCategory->findList(true);
            $this->set('index', $index);
            $this->set('gradeId', $gradeId);
            $this->set('data', $data);
            $this->set('categoryOptions', $categoryOptions);
        }
    }

    public function classesCheckName() {
        $this->autoRender = false;
        $name = trim($this->params->query['name']);
        $yearId = $this->params->query['year'];
        $count = $this->params->query['count'];

        if ($count == 0) {
            return $this->Utility->getMessage('SITE_CLASS_NO_GRADES');
        } else if (strlen($name) == 0) {
            return $this->Utility->getMessage('SITE_CLASS_EMPTY_NAME');
        } else if ($this->InstitutionSiteClass->isNameExists($name, $this->institutionSiteId, $yearId)) {
            return $this->Utility->getMessage('SITE_CLASS_DUPLICATE_NAME');
        }
        return 'true';
    }

    public function classesAddSubjectRow() {
        $this->layout = 'ajax';

        if (sizeof($this->params['pass']) == 2) {
            $year = $this->params['pass'][0];
            $classId = $this->params['pass'][1];
            $subjects = $this->EducationSubject->getSubjectByClassId($classId);
            $this->set('subjects', $subjects);
        }
    }

    public function classesSubjectAjax() {
        $this->autoRender = false;

        if (sizeof($this->params['pass']) == 1) {
            $classId = $this->params['pass'][0];
            $subjectId = $this->params->query['subjectId'];
            $action = $this->params->query['action'];

            $result = false;
            if ($action === 'add') {
                $data = array('institution_site_class_id' => $classId, 'education_grade_subject_id' => $subjectId);
                $this->InstitutionSiteClassSubject->create();
                $result = $this->InstitutionSiteClassSubject->save($data);
            } else {
                $result = $this->InstitutionSiteClassSubject->deleteAll(array(
                    'InstitutionSiteClassSubject.institution_site_class_id' => $classId,
                    'InstitutionSiteClassSubject.education_grade_subject_id' => $subjectId
                        ), false);
            }

            $return = array();
            if ($result) {
                $this->Utility->setAjaxResult('success', $return);
            } else {
                $this->Utility->setAjaxResult('error', $return);
                $return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
            }
            return json_encode($return);
        }
    }

    public function classesDeleteSubject() {
        $id = $this->params['pass'][0];
        $name = $this->InstitutionSiteClassSubject->field('name', array('InstitutionSiteClassSubject.id' => $id));
        $this->InstitutionSiteClassSubject->delete($id);
        $this->Utility->alert('Subject has been deleted successfully.');
        $this->redirect(array('action' => 'classes'));
    }

    public function classesAddTeacherRow() {
        $this->layout = 'ajax';

        if (sizeof($this->params['pass']) == 2) {
            $year = $this->params['pass'][0];
            $classId = $this->params['pass'][1];
            $index = $this->params->query['index'];
            $data = $this->InstitutionSiteTeacher->getTeacherSelectList($year, $this->institutionSiteId, $classId);

            $this->set('index', $index);
            $this->set('data', $data);
        }
    }

    public function classesTeacherAjax() {
        $this->autoRender = false;

        if (sizeof($this->params['pass']) == 1) {
            $classId = $this->params['pass'][0];
            $teacherId = $this->params->query['teacherId'];
            $action = $this->params->query['action'];

            $result = false;
            if ($action === 'add') {
                $data = array('teacher_id' => $teacherId, 'institution_site_class_id' => $classId);
                $this->InstitutionSiteClassTeacher->create();
                $result = $this->InstitutionSiteClassTeacher->save($data);
            } else {
                $result = $this->InstitutionSiteClassTeacher->deleteAll(array(
                    'InstitutionSiteClassTeacher.teacher_id' => $teacherId,
                    'InstitutionSiteClassTeacher.institution_site_class_id' => $classId
                        ), false);
            }

            $return = array();
            if ($result) {
                $this->Utility->setAjaxResult('success', $return);
            } else {
                $this->Utility->setAjaxResult('error', $return);
                $return['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
            }
            return json_encode($return);
        }
    }

    public function classesDeleteTeacher() {
        $this->autoRender = false;

        if (sizeof($this->params['pass']) == 1) {
            $gradeId = $this->params['pass'][0];
            $studentId = $this->params->query['studentId'];

            $data = array('student_id' => $studentId, 'institution_site_class_grade_id' => $gradeId);
            $this->InstitutionSiteClassGradeStudent->create();
            $obj = $this->InstitutionSiteClassGradeStudent->save($data);

            $result = array();
            if ($obj) {
                $this->Utility->setAjaxResult('success', $result);
            } else {
                $this->Utility->setAjaxResult('error', $result);
                $result['msg'] = $this->Utility->getMessage('ERROR_UNEXPECTED');
            }
            return json_encode($result);
        }
    }

    public function classesAssessments() {
        if (isset($this->params['pass'][0])) {
            $classId = $this->params['pass'][0];
            $class = $this->InstitutionSiteClass->findById($classId);
            if ($class) {
                $class = $class['InstitutionSiteClass'];
                $this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
                $this->Navigation->addCrumb('Results');
                $data = $this->AssessmentItemType->getAssessmentsByClass($classId);

                if (empty($data)) {
                    $this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
                }
                $this->set('classId', $classId);
                $this->set('data', $data);
            } else {
                $this->redirect(array('action' => 'classes'));
            }
        } else {
            $this->redirect(array('action' => 'classes'));
        }
    }

    public function classesResults() {
        if (count($this->params['pass']) == 2 || count($this->params['pass']) == 3) {
            $classId = $this->params['pass'][0];
            $assessmentId = $this->params['pass'][1];
            $class = $this->InstitutionSiteClass->findById($classId);
            $selectedItem = 0;
            if ($class) {
                $class = $class['InstitutionSiteClass'];
                $this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
                $this->Navigation->addCrumb('Results');
                $items = $this->AssessmentItem->getItemList($assessmentId);
                if (empty($items)) {
                    $this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT_ITEM'), array('type' => 'info'));
                } else {
                    $selectedItem = isset($this->params['pass'][2]) ? $this->params['pass'][2] : key($items);
                    $data = $this->InstitutionSiteClassGradeStudent->getStudentAssessmentResults($classId, $selectedItem, $assessmentId);
                    if (empty($data)) {
                        $this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_STUDENTS'), array('type' => 'info'));
                    }
                    $this->set('itemOptions', $items);
                    $this->set('data', $data);
                }
                $this->set('classId', $classId);
                $this->set('assessmentId', $assessmentId);
                $this->set('selectedItem', $selectedItem);
            } else {
                $this->redirect(array('action' => 'classes'));
            }
        } else {
            $this->redirect(array('action' => 'classes'));
        }
    }

    public function classesResultsEdit() {
        if (count($this->params['pass']) == 2 || count($this->params['pass']) == 3) {
            $classId = $this->params['pass'][0];
            $assessmentId = $this->params['pass'][1];
            $class = $this->InstitutionSiteClass->findById($classId);
            $selectedItem = 0;
            if ($class) {
                $class = $class['InstitutionSiteClass'];
                $this->Navigation->addCrumb($class['name'], array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
                $this->Navigation->addCrumb('Results');
                $items = $this->AssessmentItem->getItemList($assessmentId);
                if (empty($items)) {
                    $this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT_ITEM'), array('type' => 'info'));
                } else {
                    $selectedItem = isset($this->params['pass'][2]) ? $this->params['pass'][2] : key($items);
                    $data = $this->InstitutionSiteClassGradeStudent->getStudentAssessmentResults($classId, $selectedItem, $assessmentId);
                    if ($this->request->is('get')) {
                        if (empty($data)) {
                            $this->Utility->alert($this->Utility->getMessage('ASSESSMENT_NO_STUDENTS'), array('type' => 'info'));
                        }
                        $gradingOptions = $this->AssessmentResultType->findList(true);
                        $this->set('classId', $classId);
                        $this->set('assessmentId', $assessmentId);
                        $this->set('selectedItem', $selectedItem);
                        $this->set('itemOptions', $items);
                        $this->set('gradingOptions', $gradingOptions);
                        $this->set('data', $data);
                    } else {
                        if (isset($this->data['AssessmentItemResult'])) {
                            $result = $this->data['AssessmentItemResult'];
                            foreach ($result as $key => &$obj) {
                                $obj['assessment_item_id'] = $selectedItem;
                                $obj['institution_site_id'] = $this->institutionSiteId;
                            }
                            if (!empty($result)) {
                                $this->AssessmentItemResult->saveMany($result);
                                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                            }
                        }
                        $this->redirect(array('action' => 'classesResults', $classId, $assessmentId, $selectedItem));
                    }
                }
                $this->set('classId', $classId);
                $this->set('assessmentId', $assessmentId);
                $this->set('selectedItem', $selectedItem);
            } else {
                $this->redirect(array('action' => 'classes'));
            }
        } else {
            $this->redirect(array('action' => 'classes'));
        }
    }

    public function students() {
        App::uses('Sanitize', 'Utility');
        $this->Navigation->addCrumb('List of Students');

        $page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;

        $selectedYear = "";
        $selectedProgramme = "";
        $searchField = "";
        $orderBy = 'Student.first_name';
        $order = 'asc';
        $yearOptions = $this->SchoolYear->getYearListValues('start_year');
        $programmeOptions = $this->InstitutionSiteProgramme->getProgrammeOptions($this->institutionSiteId);
        $prefix = 'InstitutionSiteStudent.Search.%s';
        if ($this->request->is('post')) {
            $searchField = Sanitize::escape(trim($this->data['Student']['SearchField']));
            $selectedYear = $this->data['Student']['school_year'];
            $selectedProgramme = $this->data['Student']['education_programme_id'];
            $orderBy = $this->data['Student']['orderBy'];
            $order = $this->data['Student']['order'];

            $this->Session->write(sprintf($prefix, 'SearchField'), $searchField);
            $this->Session->write(sprintf($prefix, 'SchoolYear'), $selectedYear);
            $this->Session->write(sprintf($prefix, 'EducationProgrammeId'), $selectedProgramme);
            $this->Session->write(sprintf($prefix, 'order'), $order);
            $this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
        } else {
            $searchField = $this->Session->read(sprintf($prefix, 'SearchField'));
            $selectedYear = $this->Session->read(sprintf($prefix, 'SchoolYear'));
            $selectedProgramme = $this->Session->read(sprintf($prefix, 'EducationProgrammeId'));

            if ($this->Session->check(sprintf($prefix, 'orderBy'))) {
                $orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
            }
            if ($this->Session->check(sprintf($prefix, 'order'))) {
                $order = $this->Session->read(sprintf($prefix, 'order'));
            }
        }
        $conditions = array('institution_site_id' => $this->institutionSiteId, 'order' => array($orderBy => $order));
        $conditions['search'] = $searchField;
        if (!empty($selectedYear)) {
            $conditions['year'] = $selectedYear;
        }

        if (!empty($selectedProgramme)) {
            $conditions['education_programme_id'] = $selectedProgramme;
        }

        $this->paginate = array('limit' => 15, 'maxLimit' => 100);
        $data = $this->paginate('InstitutionSiteStudent', $conditions);

        if (empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('STUDENT_SEARCH_NO_RESULT'), array('type' => 'info', 'dismissOnClick' => false));
        }

        // Checking if user has access to add
        $_add_student = $this->AccessControl->check('InstitutionSites', 'studentsAdd');
        $this->set('_add_student', $_add_student);
        // End Access Control

        $this->set('searchField', $searchField);
        $this->set('page', $page);
        $this->set('orderBy', $orderBy);
        $this->set('order', $order);
        $this->set('yearOptions', $yearOptions);
        $this->set('programmeOptions', $programmeOptions);
        $this->set('selectedYear', $selectedYear);
        $this->set('selectedProgramme', $selectedProgramme);
        $this->set('data', $data);
    }

    public function studentsSearch() {
        $this->layout = 'ajax';
        $search = trim($this->params->query['searchString']);
        $params = array('limit' => 100);
        $data = $this->Student->search($search, $params);
        $this->set('search', $search);
        $this->set('data', $data);
    }

    public function studentsAdd() {
        $this->Navigation->addCrumb('Add Student');
        $yearOptions = $this->SchoolYear->getYearList();
        $yearRange = $this->SchoolYear->getYearRange();
        $statusOptions = $this->StudentStatus->findList(true);
        $programmeOptions = array();
        $selectedYear = '';
        if (!empty($yearOptions)) {
            $selectedYear = key($yearOptions);
            $programmeOptions = $this->InstitutionSiteProgramme->getSiteProgrammeForSelection($this->institutionSiteId, $selectedYear);
        }
        $this->set('yearOptions', $yearOptions);
        $this->set('minYear', current($yearRange));
        $this->set('maxYear', array_pop($yearRange));
        $this->set('programmeOptions', $programmeOptions);
        $this->set('statusOptions', $statusOptions);
    }

    public function studentsSave() {
        if ($this->request->is('post')) {
            $data = $this->data['InstitutionSiteStudent'];
            if (isset($data['student_id'])) {
                $date = $data['start_date'];
                if (!empty($date['day']) && !empty($date['month']) && !empty($date['year'])) {
                    $data['start_year'] = $date['year'];
                    $yr = $date['year'];
                    $mth = $date['month'];
                    $day = $date['day'];

                    while (!checkdate($mth, $day, $yr)) {
                        $day--;
                    }
                    $data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
                    $date = $data['start_date'];
                    $student = $this->Student->find('first', array('conditions' => array('Student.id' => $data['student_id'])));
                    $name = $student['Student']['first_name'] . ' ' . $student['Student']['last_name'];
                    $siteProgrammeId = $data['institution_site_programme_id'];
                    $exists = $this->InstitutionSiteStudent->isStudentExistsInProgramme($data['student_id'], $siteProgrammeId, $data['start_year']);

                    if (!$exists) {
                        $duration = $this->EducationProgramme->getDurationBySiteProgramme($siteProgrammeId);
                        $startDate = new DateTime(sprintf('%s-%s-%s', $date['year'], $date['month'], $date['day']));
                        $endDate = $startDate->add(new DateInterval('P' . $duration . 'Y'));
                        $endYear = $endDate->format('Y');
                        $data['end_date'] = $endDate->format('Y-m-d');
                        $data['end_year'] = $endYear;
                        $this->InstitutionSiteStudent->save($data);
                        $this->Utility->alert($this->Utility->getMessage('CREATE_SUCCESS'));
                    } else {
                        $this->Utility->alert($name . ' ' . $this->Utility->getMessage('STUDENT_ALREADY_ADDED'), array('type' => 'error'));
                    }
                } else {
                    $this->Utility->alert($this->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
                }
                $this->redirect(array('action' => 'studentsAdd'));
            }
        }
    }

    public function studentsView() {
        if (isset($this->params['pass'][0])) {
            $studentId = $this->params['pass'][0];
            $this->Session->write('InstitutionSiteStudentId', $studentId);
            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s %s', $data['Student']['first_name'], $data['Student']['middle_name'], $data['Student']['last_name']);
            $this->Navigation->addCrumb($name);

            $details = $this->InstitutionSiteStudent->getDetails($studentId, $this->institutionSiteId);
            $classes = $this->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId, $this->institutionSiteId);
            $results = $this->AssessmentItemResult->getResultsByStudent($studentId, $this->institutionSiteId);
            $results = $this->AssessmentItemResult->groupItemResults($results);
            $_view_details = $this->AccessControl->check('Students', 'view');
            $this->set('_view_details', $_view_details);
            $this->set('data', $data);
            $this->set('classes', $classes);
            $this->set('results', $results);
            $this->set('details', $details);
        } else {
            $this->redirect(array('action' => 'students'));
        }
    }

    public function studentsDelete() {
        if ($this->Session->check('InstitutionSiteStudentId') && $this->Session->check('InstitutionSiteId')) {
            $studentId = $this->Session->read('InstitutionSiteStudentId');
            $InstitutionSiteId = $this->Session->read('InstitutionSiteId');

            $SiteStudentRecordIds = $this->InstitutionSiteStudent->getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId);
            if (!empty($SiteStudentRecordIds)) {
                $this->InstitutionSiteStudent->deleteAll(array('InstitutionSiteStudent.id' => $SiteStudentRecordIds), false);
            }

            $GradeStudentRecordIds = $this->InstitutionSiteClassGradeStudent->getRecordIdsByStudentIdAndSiteId($studentId, $InstitutionSiteId);
            if (!empty($GradeStudentRecordIds)) {
                $this->InstitutionSiteClassGradeStudent->deleteAll(array('InstitutionSiteClassGradeStudent.id' => $GradeStudentRecordIds), false);
            }

            $this->AssessmentItemResult->deleteAll(array(
                'AssessmentItemResult.student_id' => $studentId,
                'AssessmentItemResult.institution_site_id' => $InstitutionSiteId
                    ), false);

            $this->StudentBehaviour->deleteAll(array(
                'StudentBehaviour.student_id' => $studentId,
                'StudentBehaviour.institution_site_id' => $InstitutionSiteId
                    ), false);

            $this->StudentAttendance->deleteAll(array(
                'StudentAttendance.student_id' => $studentId,
                'StudentAttendance.institution_site_id' => $InstitutionSiteId
                    ), false);

            $StudentDetailsCustomValueObj = ClassRegistry::init('StudentDetailsCustomValue');
            $StudentDetailsCustomValueObj->deleteAll(array(
                'StudentDetailsCustomValue.student_id' => $studentId,
                'StudentDetailsCustomValue.institution_site_id' => $InstitutionSiteId
                    ), false);


            $this->Utility->alert($this->Utility->getMessage('DELETE_SUCCESS'));
            $this->redirect(array('action' => 'students'));
        } else {
            $this->redirect(array('action' => 'students'));
        }
    }

    public function studentsEdit() {
        if ($this->Session->check('InstitutionSiteStudentId')) {
            $studentId = $this->Session->read('InstitutionSiteStudentId');
            if ($this->request->is('post')) {
                $postData = $this->request->data['InstitutionSiteStudent'];
                foreach ($postData as $i => $obj) {
                    $postData[$i]['start_year'] = date('Y', strtotime($obj['start_date']));
                    $postData[$i]['end_year'] = date('Y', strtotime($obj['end_date']));
                }
                $this->InstitutionSiteStudent->saveMany($postData);
                $this->Utility->alert($this->Utility->getMessage('UPDATE_SUCCESS'));
                return $this->redirect(array('action' => 'studentsView', $studentId));
            }

            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s %s', $data['Student']['first_name'], $data['Student']['middle_name'], $data['Student']['last_name']);
            $this->Navigation->addCrumb($name);
            $statusOptions = $this->StudentStatus->findList(true);

            $details = $this->InstitutionSiteStudent->getDetails($studentId, $this->institutionSiteId);
            $classes = $this->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId, $this->institutionSiteId);
            $results = $this->AssessmentItemResult->getResultsByStudent($studentId, $this->institutionSiteId);
            $results = $this->AssessmentItemResult->groupItemResults($results);
            $_view_details = $this->AccessControl->check('Students', 'view');
            $this->set('_view_details', $_view_details);
            $this->set('data', $data);
            $this->set('classes', $classes);
            $this->set('results', $results);
            $this->set('details', $details);
            $this->set('statusOptions', $statusOptions);
        } else {
            $this->redirect(array('action' => 'students'));
        }
    }

    public function teachers() {
        $this->Navigation->addCrumb('List of Teachers');
        $page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
        $model = 'Teacher';
        $orderBy = $model . '.first_name';
        $order = 'asc';
        $yearOptions = $this->SchoolYear->getYearListValues('start_year');
        $selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : '';
        $prefix = sprintf('InstitutionSite%s.List.%%s', $model);
        if ($this->request->is('post')) {
            $selectedYear = $this->data[$model]['school_year'];
            $orderBy = $this->data[$model]['orderBy'];
            $order = $this->data[$model]['order'];

            $this->Session->write(sprintf($prefix, 'order'), $order);
            $this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
        } else {
            if ($this->Session->check(sprintf($prefix, 'orderBy'))) {
                $orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
            }
            if ($this->Session->check(sprintf($prefix, 'order'))) {
                $order = $this->Session->read(sprintf($prefix, 'order'));
            }
        }
        $conditions = array('year' => $selectedYear, 'InstitutionSiteTeacher.institution_site_id' => $this->institutionSiteId);

        $this->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
        $data = $this->paginate('InstitutionSiteTeacher', $conditions);

        // Checking if user has access to add
        $_add_teacher = $this->AccessControl->check('InstitutionSites', 'teachersAdd');
        $this->set('_add_teacher', $_add_teacher);
        // End Access Control

        $this->set('page', $page);
        $this->set('orderBy', $orderBy);
        $this->set('order', $order);
        $this->set('yearOptions', $yearOptions);
        $this->set('selectedYear', $selectedYear);
        $this->set('data', $data);
    }

    public function teachersSearch() {
        $this->layout = 'ajax';
        $search = trim($this->params->query['searchString']);
        $params = array('limit' => 100);
        $data = $this->Teacher->search($search, $params);
        $this->set('search', $search);
        $this->set('data', $data);
    }

    public function teachersAdd() {
        $this->Navigation->addCrumb('Add Teacher');
        $yearRange = $this->SchoolYear->getYearRange();
        $categoryOptions = $this->TeacherCategory->findList(true);
        $positionTitleptions = $this->TeacherPositionTitle->findList(true);
        $positionGradeOptions = $this->TeacherPositionGrade->findList(true);
        $positionStepOptions = $this->TeacherPositionStep->findList(true);
        $statusOptions = $this->TeacherStatus->findList(true);

        $this->set('minYear', current($yearRange));
        $this->set('maxYear', array_pop($yearRange));
        $this->set('categoryOptions', $categoryOptions);
        $this->set('positionTitleptions', $positionTitleptions);
        $this->set('positionGradeOptions', $positionGradeOptions);
        $this->set('positionStepOptions', $positionStepOptions);
        $this->set('statusOptions', $statusOptions);
    }

    public function teachersSave() {
        if ($this->request->is('post')) {
            $data = $this->data['InstitutionSiteTeacher'];
            if (isset($data['teacher_id'])) {
                if (!empty($data['start_date']['day']) && !empty($data['start_date']['month']) && !empty($data['start_date']['year'])) {
                    $data['institution_site_id'] = $this->institutionSiteId;
                    $data['start_year'] = $data['start_date']['year'];
                    $yr = $data['start_date']['year'];
                    $mth = $data['start_date']['month'];
                    $day = $data['start_date']['day'];

                    while (!checkdate($mth, $day, $yr)) {
                        $day--;
                    }
                    $data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
                    $insert = true;
                    if (!empty($data['position_no'])) {
                        $obj = $this->InstitutionSiteTeacher->isPositionNumberExists($data['position_no'], $data['start_date']);
                        if (!$obj) {
                            $obj = $this->InstitutionSiteStaff->isPositionNumberExists($data['position_no'], $data['start_date']);
                        }
                        if ($obj) {
                            $teacherObj = $this->Teacher->find('first', array(
                                'fields' => array('Teacher.identification_no', 'Teacher.first_name', 'Teacher.middle_name', 'Teacher.last_name', 'Teacher.gender'),
                                'conditions' => array('Teacher.id' => $data['teacher_id'])
                            ));
                            $position = $data['position_no'];
                            $name = '<b>' . trim($obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']) . '</b>';
                            $school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
                            $msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
                            $msg .= '<br>' . __('Please choose another position number.');
                            $this->Utility->alert($msg, array('type' => 'warn'));
                            $insert = false;
                        } else {
                            if (isset($data['FTE']) && strlen($data['FTE']) > 0) {
                                $PTE = floatval($data['FTE']);

                                if ($PTE < 0.01 || $PTE > 1) {
                                    $msg = 'FTE value should be from 0.01 to 1.00';
                                    $this->Utility->alert($msg, array('type' => 'warn'));
                                    $insert = false;
                                }
                            }
                        }
                    } else {
                        if (isset($data['FTE']) && strlen($data['FTE']) > 0) {
                            $PTE = floatval($data['FTE']);

                            if ($PTE < 0.01 || $PTE > 1) {
                                $msg = 'FTE value should be from 0.01 to 1.00';
                                $this->Utility->alert($msg, array('type' => 'warn'));
                                $insert = false;
                            }
                        }
                    }
                } else {
                    $this->Utility->alert($this->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
                }
                if (isset($insert) && $insert == true) {
                    $this->InstitutionSiteTeacher->save($data);
                    $this->Utility->alert($this->Utility->getMessage('CREATE_SUCCESS'));
                }
                $this->redirect(array('action' => 'teachersAdd'));
            }
        }
    }

    public function teachersView() {
        if (isset($this->params['pass'][0])) {
            $teacherId = $this->params['pass'][0];
            $this->Session->write('InstitutionSiteTeachersId', $teacherId);
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
            $positions = $this->InstitutionSiteTeacher->getPositions($teacherId, $this->institutionSiteId);
            $this->Navigation->addCrumb($name);
            if (!empty($positions)) {
                $classes = $this->InstitutionSiteClassTeacher->getClasses($teacherId, $this->institutionSiteId);
                $this->set('data', $data);
                $this->set('positions', $positions);
                $this->set('classes', $classes);
            } else {
                $this->redirect(array('action' => 'teachers'));
            }
        } else {
            $this->redirect(array('action' => 'teachers'));
        }
    }

    public function teachersEdit() {
        if (isset($this->params['pass'][0])) {
            $teacherId = $this->params['pass'][0];

            if ($this->request->is('get')) {
                $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
                $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
                $positions = $this->InstitutionSiteTeacher->getPositions($teacherId, $this->institutionSiteId);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $data['Teacher']['id']));
                $this->Navigation->addCrumb('Edit');

                if (!empty($positions)) {
                    $classes = $this->InstitutionSiteClassTeacher->getClasses($teacherId, $this->institutionSiteId);
                    $statusOptions = $this->TeacherStatus->findList(true);
                    $_view_details = $this->AccessControl->check('Teachers', 'view');
                    $this->set('_view_details', $_view_details);
                    $this->set('data', $data);
                    $this->set('positions', $positions);
                    $this->set('classes', $classes);
                    $this->set('statusOptions', $statusOptions);
                } else {
                    $this->redirect(array('action' => 'teachers'));
                }
            } else {
                if (isset($this->data['delete'])) {
                    $delete = $this->data['delete'];
                    $this->InstitutionSiteTeacher->deleteAll(array('InstitutionSiteTeacher.id' => $delete), false);
                }
                $data = $this->data['InstitutionSiteTeacher'];

                $update_proceed = true;

                // checking for existing position number
                foreach ($data as $i => $row) {
                    if (!array_key_exists('id', $row)) {
                        if ($row['position_no'] === __('Position No')) {
                            $data[$i]['position_no'] = null;

                            if (isset($row['FTE']) && strlen($row['FTE']) > 0) {
                                $PTE = floatval($row['FTE']);

                                if ($PTE < 0.01 || $PTE > 1) {
                                    unset($data[$i]);
                                }
                            }
                        } else {
                            $obj = $this->InstitutionSiteTeacher->isPositionNumberExists($row['position_no'], $row['start_date']);
                            if (!$obj) {
                                $obj = $this->InstitutionSiteStaff->isPositionNumberExists($row['position_no'], $row['start_date']);
                            }
                            if ($obj) {
                                $position = $row['position_no'];
                                $name = '<b>' . trim($obj['first_name'] . ' ' . $obj['last_name']) . '</b>';
                                $school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
                                $msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
                                $msg .= '<br>' . __('Please choose another position number.');
                                $this->Utility->alert($msg, array('type' => 'warn'));
                                unset($data[$i]);
                            } else {
                                if (isset($row['FTE']) && strlen($row['FTE']) > 0) {
                                    $PTE = floatval($row['FTE']);

                                    if ($PTE < 0.01 || $PTE > 1) {
                                        unset($data[$i]);
                                    }
                                }
                            }
                        }
                    } else {
                        if (isset($row['FTE']) && strlen($row['FTE']) > 0) {
                            $PTE = floatval($row['FTE']);

                            if ($PTE < 0.01 || $PTE > 1) {
                                $msg = 'FTE value should be from 0.01 to 1.00';
                                $this->Utility->alert($msg, array('type' => 'warn'));
                                $update_proceed = false;
                            }
                        }
                    }
                }
                if ($update_proceed == true) {
                    $this->InstitutionSiteTeacher->saveEmployment($data, $this->institutionSiteId, $teacherId);
                    $this->redirect(array('action' => 'teachersView', $teacherId));
                } else {
                    $this->redirect(array('action' => 'teachersEdit', $teacherId));
                }
            }
        } else {
            $this->redirect(array('action' => 'teachers'));
        }
    }

    public function teachersAddPosition() {
        $this->layout = 'ajax';

        $index = $this->params->query['index'] + 1;
        $categoryOptions = $this->TeacherCategory->findList(true);
        $positionTitleOptions = $this->TeacherPositionTitle->findList(true);
        $positionGradeOptions = $this->TeacherPositionGrade->findList(true);
        $positionStepOptions = $this->TeacherPositionStep->findList(true);
        $statusOptions = $this->TeacherStatus->findList(true);

        $this->set('index', $index);
        $this->set('categoryOptions', $categoryOptions);
        $this->set('positionTitleOptions', $positionTitleOptions);
        $this->set('positionGradeOptions', $positionGradeOptions);
        $this->set('positionStepOptions', $positionStepOptions);
        $this->set('statusOptions', $statusOptions);
    }

    public function staff() {
        $this->Navigation->addCrumb('List of Staff');
        $page = isset($this->params->named['page']) ? $this->params->named['page'] : 1;
        $model = 'Staff';
        $orderBy = $model . '.first_name';
        $order = 'asc';
        $yearOptions = $this->SchoolYear->getYearListValues('start_year');
        $selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : '';
        $prefix = sprintf('InstitutionSite%s.List.%%s', $model);
        if ($this->request->is('post')) {
            $selectedYear = $this->data[$model]['school_year'];
            $orderBy = $this->data[$model]['orderBy'];
            $order = $this->data[$model]['order'];

            $this->Session->write(sprintf($prefix, 'order'), $order);
            $this->Session->write(sprintf($prefix, 'orderBy'), $orderBy);
        } else {
            if ($this->Session->check(sprintf($prefix, 'orderBy'))) {
                $orderBy = $this->Session->read(sprintf($prefix, 'orderBy'));
            }
            if ($this->Session->check(sprintf($prefix, 'order'))) {
                $order = $this->Session->read(sprintf($prefix, 'order'));
            }
        }
        $conditions = array('year' => $selectedYear, 'InstitutionSiteStaff.institution_site_id' => $this->institutionSiteId);

        $this->paginate = array('limit' => 15, 'maxLimit' => 100, 'order' => sprintf('%s %s', $orderBy, $order));
        $data = $this->paginate('InstitutionSiteStaff', $conditions);

        // Checking if user has access to add
        $_add_staff = $this->AccessControl->check('InstitutionSites', 'staffAdd');
        $this->set('_add_staff', $_add_staff);
        // End Access Control

        $this->set('page', $page);
        $this->set('orderBy', $orderBy);
        $this->set('order', $order);
        $this->set('yearOptions', $yearOptions);
        $this->set('selectedYear', $selectedYear);
        $this->set('data', $data);
    }

    public function staffSearch() {
        $this->layout = 'ajax';
        $search = trim($this->params->query['searchString']);
        $params = array('limit' => 100);
        $data = $this->Staff->search($search, $params);
        $this->set('search', $search);
        $this->set('data', $data);
    }

    public function staffAdd() {
        $this->Navigation->addCrumb('Add Staff');
        $yearRange = $this->SchoolYear->getYearRange();
        $categoryOptions = $this->StaffCategory->findList(true);
        $positionTitleptions = $this->StaffPositionTitle->findList(true);
        $positionGradeOptions = $this->StaffPositionGrade->findList(true);
        $positionStepOptions = $this->StaffPositionStep->findList(true);
        $statusOptions = $this->StaffStatus->findList(true);

        $this->set('minYear', current($yearRange));
        $this->set('maxYear', array_pop($yearRange));
        $this->set('categoryOptions', $categoryOptions);
        $this->set('positionTitleptions', $positionTitleptions);
        $this->set('positionGradeOptions', $positionGradeOptions);
        $this->set('positionStepOptions', $positionStepOptions);
        $this->set('statusOptions', $statusOptions);
    }

    public function staffSave() {
        if ($this->request->is('post')) {
            $data = $this->data['InstitutionSiteStaff'];
            if (isset($data['staff_id'])) {
                if (!empty($data['start_date']['day']) && !empty($data['start_date']['month']) && !empty($data['start_date']['year'])) {
                    $data['institution_site_id'] = $this->institutionSiteId;
                    $data['start_year'] = $data['start_date']['year'];
                    $data['start_year'] = $data['start_date']['year'];
                    $yr = $data['start_date']['year'];
                    $mth = $data['start_date']['month'];
                    $day = $data['start_date']['day'];

                    while (!checkdate($mth, $day, $yr)) {
                        $day--;
                    }
                    $data['start_date'] = sprintf('%d-%d-%d', $yr, $mth, $day);
                    $insert = true;
                    if (!empty($data['position_no'])) {
                        $obj = $this->InstitutionSiteStaff->isPositionNumberExists($data['position_no'], $data['start_date']);
                        if (!$obj) {
                            $obj = $this->InstitutionSiteTeacher->isPositionNumberExists($data['position_no'], $data['start_date']);
                        }
                        if ($obj) {
                            $staffObj = $this->Staff->find('first', array(
                                'fields' => array('Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name', 'Staff.gender'),
                                'conditions' => array('Staff.id' => $data['staff_id'])
                            ));
                            $position = $data['position_no'];
                            $name = '<b>' . trim($obj['first_name'] . ' ' . $obj['middle_name'] . ' ' . $obj['last_name']) . '</b>';
                            $school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
                            $msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
                            $msg .= '<br>' . __('Please choose another position number.');
                            $this->Utility->alert($msg, array('type' => 'warn'));
                            $insert = false;
                        }
                    }
                } else {
                    $insert = false;
                    $this->Utility->alert($this->Utility->getMessage('INVALID_DATE'), array('type' => 'error'));
                }
                if (isset($insert) && $insert) {
                    $this->InstitutionSiteStaff->save($data);
                    $this->Utility->alert($this->Utility->getMessage('CREATE_SUCCESS'));
                }
                $this->redirect(array('action' => 'staffAdd'));
            }
        }
    }

    public function staffView() {
        if (isset($this->params['pass'][0])) {
            $staffId = $this->params['pass'][0];
            $this->Session->write('InstitutionSiteStaffId', $staffId);
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
            $positions = $this->InstitutionSiteStaff->getPositions($staffId, $this->institutionSiteId);
            $this->Navigation->addCrumb($name);
            if (!empty($positions)) {
                $this->set('data', $data);
                $this->set('positions', $positions);
            } else {
                $this->redirect(array('action' => 'staff'));
            }
        } else {
            $this->redirect(array('action' => 'staff'));
        }
    }

    public function staffEdit() {
        if (isset($this->params['pass'][0])) {
            $staffId = $this->params['pass'][0];

            if ($this->request->is('get')) {
                $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
                $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
                $positions = $this->InstitutionSiteStaff->getPositions($staffId, $this->institutionSiteId);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $data['Staff']['id']));
                $this->Navigation->addCrumb('Edit');
                if (!empty($positions)) {
                    $statusOptions = $this->StaffStatus->findList(true);
                    $_view_details = $this->AccessControl->check('Staff', 'view');
                    $this->set('_view_details', $_view_details);
                    $this->set('data', $data);
                    $this->set('positions', $positions);
                    $this->set('statusOptions', $statusOptions);
                } else {
                    $this->redirect(array('action' => 'staff'));
                }
            } else {
                if (isset($this->data['delete'])) {
                    $delete = $this->data['delete'];
                    $this->InstitutionSiteStaff->deleteAll(array('InstitutionSiteStaff.id' => $delete), false);
                }
                $data = $this->data['InstitutionSiteStaff'];
                // checking for existing position number
                foreach ($data as $i => $row) {
                    if (!array_key_exists('id', $row)) {
                        if ($row['position_no'] === __('Position No')) {
                            $data[$i]['position_no'] = null;
                        } else {
                            $obj = $this->InstitutionSiteTeacher->isPositionNumberExists($row['position_no'], $row['start_date']);
                            if (!$obj) {
                                $obj = $this->InstitutionSiteStaff->isPositionNumberExists($row['position_no'], $row['start_date']);
                            }
                            if ($obj) {
                                $position = $row['position_no'];
                                $name = '<b>' . trim($obj['first_name'] . ' ' . $obj['last_name']) . '</b>';
                                $school = '<b>' . trim($obj['institution_name'] . ' - ' . $obj['institution_site_name']) . '</b>';
                                $msg = __('Position Number') . ' (' . $position . ') ' . __('is already being assigned to ') . $name . ' from ' . $school . '. ';
                                $msg .= '<br>' . __('Please choose another position number.');
                                $this->Utility->alert($msg, array('type' => 'warn'));
                                unset($data[$i]);
                            }
                        }
                    }
                }
                $this->InstitutionSiteStaff->saveEmployment($data, $this->institutionSiteId, $staffId);
                $this->redirect(array('action' => 'staffView', $staffId));
            }
        } else {
            $this->redirect(array('action' => 'staff'));
        }
    }

    public function staffAddPosition() {
        $this->layout = 'ajax';

        $index = $this->params->query['index'] + 1;
        $categoryOptions = $this->StaffCategory->findList(true);
        $positionTitleOptions = $this->StaffPositionTitle->findList(true);
        $positionGradeOptions = $this->StaffPositionGrade->findList(true);
        $positionStepOptions = $this->StaffPositionStep->findList(true);
        $statusOptions = $this->StaffStatus->findList(true);

        $this->set('index', $index);
        $this->set('categoryOptions', $categoryOptions);
        $this->set('positionTitleOptions', $positionTitleOptions);
        $this->set('positionGradeOptions', $positionGradeOptions);
        $this->set('positionStepOptions', $positionStepOptions);
        $this->set('statusOptions', $statusOptions);
    }

    //TEACHER CUSTOM FIELD PER YEAR - STARTS - 
    private function teachersCustFieldYrInits() {
        $action = $this->action;
        $siteid = $this->institutionSiteId;
        $id = @$this->request->params['pass'][0];
        $years = $this->SchoolYear->getYearList();
        $selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
        $condParam = array('teacher_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);
        $arrMap = array('CustomField' => 'TeacherDetailsCustomField',
            'CustomFieldOption' => 'TeacherDetailsCustomFieldOption',
            'CustomValue' => 'TeacherDetailsCustomValue',
            'Year' => 'SchoolYear');
        //BreadCrumb -- jeff logic
        $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $id)));
        $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
        $positions = $this->InstitutionSiteTeacher->getPositions($id, $this->institutionSiteId);
        $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $data['Teacher']['id']));

        return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
    }

    public function teachersCustFieldYrView() {
        extract($this->teachersCustFieldYrInits());
        $this->Navigation->addCrumb('Academic');
        $customfield = $this->Components->load('CustomField', $arrMap);
        $data = array();
        if ($id && $selectedYear && $siteid)
            $data = $customfield->getCustomFieldView($condParam);
        $displayEdit = true;
        if (count($data['dataFields']) == 0) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
            $displayEdit = false;
        }
        $this->set(compact('arrMap', 'selectedYear', 'years', 'action', 'id', 'displayEdit'));
        $this->set($data);
        $this->set('id', $id);
        $this->set('myview', 'teachersView');
        $this->render('/Elements/customfields/view');
    }

    public function teachersCustFieldYrEdit() {
        if ($this->request->is('post')) {
            extract($this->teachersCustFieldYrInits());
            $customfield = $this->Components->load('CustomField', $arrMap);
            $cond = array('institution_site_id' => $siteid,
                'teacher_id' => $id,
                'school_year_id' => $selectedYear);
            $customfield->saveCustomFields($this->request->data, $cond);
            $this->redirect(array('action' => 'teachersCustFieldYrView', $id, $selectedYear));
        } else {
            $this->teachersCustFieldYrView();
            $this->render('/Elements/customfields/edit');
        }
    }

    //TEACHER CUSTOM FIELD PER YEAR - ENDS - 
    //STUDENTS CUSTOM FIELD PER YEAR - STARTS - 
    private function studentsCustFieldYrInits() {
        $action = $this->action;
        $siteid = $this->institutionSiteId;
        $id = @$this->request->params['pass'][0];
        $years = $this->SchoolYear->getYearList();
        $selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
        $condParam = array('student_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);
        $arrMap = array('CustomField' => 'StudentDetailsCustomField',
            'CustomFieldOption' => 'StudentDetailsCustomFieldOption',
            'CustomValue' => 'StudentDetailsCustomValue',
            'Year' => 'SchoolYear');

        $studentId = $this->params['pass'][0];
        $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
        $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
        $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $data['Student']['id']));
        return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
    }

    public function studentsCustFieldYrView() {
        extract($this->studentsCustFieldYrInits());
        $this->Navigation->addCrumb('Academic');
        $customfield = $this->Components->load('CustomField', $arrMap);
        $data = array();
        if ($id && $selectedYear && $siteid)
            $data = $customfield->getCustomFieldView($condParam);

        $displayEdit = true;
        if (count($data['dataFields']) == 0) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
            $displayEdit = false;
        }
        $this->set(compact('arrMap', 'selectedYear', 'years', 'action', 'id', 'displayEdit'));
        $this->set($data);
        $this->set('id', $id);
        $this->set('myview', 'studentsView');
        $this->render('/Elements/customfields/view');
    }

    public function studentsCustFieldYrEdit() {
        if ($this->request->is('post')) {
            extract($this->studentsCustFieldYrInits());
            $customfield = $this->Components->load('CustomField', $arrMap);
            $cond = array('institution_site_id' => $siteid,
                'student_id' => $id,
                'school_year_id' => $selectedYear);
            $customfield->saveCustomFields($this->request->data, $cond);
            $this->redirect(array('action' => 'studentsCustFieldYrView', $id, $selectedYear));
        } else {
            $this->studentsCustFieldYrView();
            $this->render('/Elements/customfields/edit');
        }
    }

    //STUDENTS CUSTOM FIELD PER YEAR - ENDS - 
    //STAFF CUSTOM FIELD PER YEAR - STARTS - 
    private function staffCustFieldYrInits() {
        $action = $this->action;
        $siteid = $this->institutionSiteId;
        $id = @$this->request->params['pass'][0];
        $years = $this->SchoolYear->getYearList();
        $selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
        $condParam = array('staff_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);
        $arrMap = array('CustomField' => 'StaffDetailsCustomField',
            'CustomFieldOption' => 'StaffDetailsCustomFieldOption',
            'CustomValue' => 'StaffDetailsCustomValue',
            'Year' => 'SchoolYear');

        $staffId = $this->params['pass'][0];
        $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
        $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
        $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $data['Staff']['id']));
        return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
    }

    public function staffCustFieldYrView() {
        extract($this->staffCustFieldYrInits());
        $this->Navigation->addCrumb('Academic');
        $customfield = $this->Components->load('CustomField', $arrMap);
        $data = array();
        if ($id && $selectedYear && $siteid)
            $data = $customfield->getCustomFieldView($condParam);
        $displayEdit = true;
        if (count($data['dataFields']) == 0) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'), array('type' => 'info'));
            $displayEdit = false;
        }
        $this->set(compact('arrMap', 'selectedYear', 'years', 'action', 'id', 'displayEdit'));
        $this->set($data);
        $this->set('id', $id);
        $this->set('myview', 'staffView');
        $this->render('/Elements/customfields/view');
    }

    public function staffCustFieldYrEdit() {
        if ($this->request->is('post')) {
            extract($this->staffCustFieldYrInits());
            $customfield = $this->Components->load('CustomField', $arrMap);
            $cond = array('institution_site_id' => $siteid,
                'staff_id' => $id,
                'school_year_id' => $selectedYear);
            $customfield->saveCustomFields($this->request->data, $cond);
            $this->redirect(array('action' => 'staffCustFieldYrView', $id, $selectedYear));
        } else {
            $this->staffCustFieldYrView();
            $this->render('/Elements/customfields/edit');
        }
    }

    //STAFF CUSTOM FIELD PER YEAR - ENDS -
    // STUDENT BEHAVIOUR PART
    public function studentsBehaviour() {
        extract($this->studentsCustFieldYrInits());
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->StudentBehaviour->getBehaviourData($id, $this->institutionSiteId);

        if (empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('STUDENT_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
        }

        $this->set('id', $id);
        $this->set('data', $data);
    }

    public function studentsBehaviourAdd() {
        if ($this->request->is('get')) {
            $studentId = $this->params['pass'][0];
            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
            $this->Navigation->addCrumb('Add Behaviour');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();

            $categoryOptions = array();
            $categoryOptions = $this->StudentBehaviourCategory->getCategory();
            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
            $this->set('institution_site_id', $this->institutionSiteId);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
            $this->set('id', $studentId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
        } else {
            $studentBehaviourData = $this->data['InstitutionSiteStudentBehaviour'];
            $studentBehaviourData['institution_site_id'] = $this->institutionSiteId;

            $this->StudentBehaviour->create();
            if (!$this->StudentBehaviour->save($studentBehaviourData)) {
                // Validation Errors
                //debug($this->StudentBehaviour->validationErrors); 
                //die;
            } else {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
            }

            $this->redirect(array('action' => 'studentsBehaviour', $studentBehaviourData['student_id']));
        }
    }

    public function studentsBehaviourView() {
        $studentBehaviourId = $this->params['pass'][0];
        $studentBehaviourObj = $this->StudentBehaviour->find('all', array('conditions' => array('StudentBehaviour.id' => $studentBehaviourId)));

        if (!empty($studentBehaviourObj)) {
            $studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];
            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->StudentBehaviourCategory->getCategory();

            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));

            $this->set('institution_site_id', $this->institutionSiteId);
            $this->Session->write('StudentBehavourId', $studentBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('studentBehaviourObj', $studentBehaviourObj);
        } else {
            //$this->redirect(array('action' => 'classesList'));
        }
    }

    public function studentsBehaviourEdit() {
        if ($this->request->is('get')) {
            $studentBehaviourId = $this->params['pass'][0];
            $studentBehaviourObj = $this->StudentBehaviour->find('all', array('conditions' => array('StudentBehaviour.id' => $studentBehaviourId)));

            if (!empty($studentBehaviourObj)) {
                $studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];

                if ($studentBehaviourObj[0]['StudentBehaviour']['institution_site_id'] != $this->institutionSiteId) {
                    $this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
                    $this->redirect(array('action' => 'studentsBehaviourView', $studentBehaviourId));
                }
                $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
                $name = sprintf('%s %s', $data['Student']['first_name'], $data['Student']['last_name']);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'studentsView', $studentId));
                $this->Navigation->addCrumb('Edit Behaviour Details');

                $categoryOptions = array();
                $categoryOptions = $this->StudentBehaviourCategory->getCategory();
                $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
                $this->set('institutionSiteOptions', $institutionSiteOptions);
                $this->set('categoryOptions', $categoryOptions);
                $this->set('studentBehaviourObj', $studentBehaviourObj);
            } else {
                //$this->redirect(array('action' => 'studentsBehaviour'));
            }
        } else {
            $studentBehaviourData = $this->data['InstitutionSiteStudentBehaviour'];
            $studentBehaviourData['institution_site_id'] = $this->institutionSiteId;

            $this->StudentBehaviour->create();
            if (!$this->StudentBehaviour->save($studentBehaviourData)) {
                // Validation Errors
                //debug($this->StudentBehaviour->validationErrors); 
                //die;
            } else {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
            }

            $this->redirect(array('action' => 'studentsBehaviourView', $studentBehaviourData['id']));
        }
    }

    public function studentsBehaviourDelete() {
        if ($this->Session->check('InstitutionSiteStudentId') && $this->Session->check('StudentBehavourId')) {
            $id = $this->Session->read('StudentBehavourId');
            $studentId = $this->Session->read('InstitutionSiteStudentId');
            $name = $this->StudentBehaviour->field('title', array('StudentBehaviour.id' => $id));
            $institution_site_id = $this->StudentBehaviour->field('institution_site_id', array('StudentBehaviour.id' => $id));
            if ($institution_site_id != $this->institutionSiteId) {
                $this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
                $this->redirect(array('action' => 'studentsBehaviourView', $id));
            }
            $this->StudentBehaviour->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'studentsBehaviour', $studentId));
        }
    }

    public function studentsBehaviourCheckName() {
        $this->autoRender = false;
        $title = trim($this->params->query['title']);

        if (strlen($title) == 0) {
            return $this->Utility->getMessage('SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE');
        }

        return 'true';
    }

    // END STUDENT BEHAVIOUR PART
    // CLASS ATTENDANCE PART
    public function classesAttendance() {
        $classId = $this->Session->read('InstitutionSiteClassId');
        $classObj = $this->InstitutionSiteClass->getClass($classId);

        if (!empty($classObj)) {
            $className = $classObj['InstitutionSiteClass']['name'];
            $this->Navigation->addCrumb($className, array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
            $this->Navigation->addCrumb('Attendance');
            $yearId = $classObj['InstitutionSiteClass']['school_year_id'];

            $grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
            $students = $this->InstitutionSiteClassGradeStudent->getStudentsAttendance($classId, array_keys($grades), $yearId);

            $this->set('classId', $classId);
            $this->set('selectedYear', $yearId);
            $this->set('grades', $grades);
            $this->set('students', $students);
        } else {
            $this->redirect(array('action' => 'classesList'));
        }
    }

    public function classesAttendanceEdit() {
        if ($this->request->is('get')) {
            $classId = $this->Session->read('InstitutionSiteClassId');
            $classObj = $this->InstitutionSiteClass->getClass($classId);

            if (!empty($classObj)) {
                $className = $classObj['InstitutionSiteClass']['name'];
                $this->Navigation->addCrumb($className, array('controller' => 'InstitutionSites', 'action' => 'classesView', $classId));
                $this->Navigation->addCrumb('Attendance');
                $yearId = $classObj['InstitutionSiteClass']['school_year_id'];

                $grades = $this->InstitutionSiteClassGrade->getGradesByClass($classId);
                $students = $this->InstitutionSiteClassGradeStudent->getStudentsAttendance($classId, array_keys($grades), $yearId);

                $this->set('classId', $classId);
                $this->set('selectedYear', $yearId);
                $this->set('grades', $grades);
                $this->set('students', $students);
            } else {
                $this->redirect(array('action' => 'classesList'));
            }
        } else {
            $classId = $this->Session->read('InstitutionSiteClassId');
            $classObj = $this->InstitutionSiteClass->getClass($classId);
            $yearId = $classObj['InstitutionSiteClass']['school_year_id'];
            $classId = $this->request->data['ClassesAttendance']['institution_site_class_id'];
            $myArr = array();
            if (isset($this->request->data['Attendance'])) {
                foreach ($this->request->data['Attendance'] as $obj) {
                    $data = $obj;
                    if ($obj['id'] == 0) {
                        unset($data['id']);
                    }
                    $data['school_year_id'] = $yearId;
                    $data['institution_site_class_id'] = $classId;
                    $myArr[] = $data;
                }
                $this->StudentAttendance->saveAll($myArr);
                $this->Utility->alert($this->Utility->getMessage('SITE_STUDENT_ATTENDANCE_UPDATED'));
            }
            $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'classesAttendance', $yearId));
        }
    }

    // END CLASS ATTENDANCE PART
    // TEACHER ATTENDANCE PART
    public function teachersAttendance() {
        if ($this->Session->check('InstitutionSiteTeachersId')) {
            $teacherId = $this->Session->read('InstitutionSiteTeachersId');
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
            $this->Navigation->addCrumb('Attendance');

            $id = $teacherId;
            $yearList = $this->SchoolYear->getYearList();
            $yearId = $this->getAvailableYearId($yearList);
            $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

            $data = $this->TeacherAttendance->getAttendanceData($this->Session->read('InstitutionSiteTeachersId'), $yearId);
            $this->set('selectedYear', $yearId);
            $this->set('years', $yearList);
            $this->set('data', $data);
            $this->set('schoolDays', $schoolDays);
            $this->set('id', $teacherId);
        }
    }

    public function teachersAttendanceEdit() {
        if ($this->request->is('get')) {
            if ($this->Session->check('InstitutionSiteTeachersId')) {
                $teacherId = $this->Session->read('InstitutionSiteTeachersId');
                $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
                $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
                $this->Navigation->addCrumb('Edit Attendance');

                $yearList = $this->SchoolYear->getYearList();
                $yearId = $this->getAvailableYearId($yearList);
                $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));
                $data = $this->TeacherAttendance->getAttendanceData($this->Session->read('InstitutionSiteTeachersId'), $yearId);

                $this->set('teacherid', $this->Session->read('InstitutionSiteTeachersId'));
                $this->set('institutionSiteId', $this->institutionSiteId);
                $this->set('selectedYear', $yearId);
                $this->set('years', $yearList);
                $this->set('schoolDays', $schoolDays);
                $this->set('data', $data);
            }
        } else {
            $schoolDayNo = $this->request->data['schoolDays'];
            $totalNo = $this->request->data['TeachersAttendance']['total_no_attend'] + $this->request->data['TeachersAttendance']['total_no_absence'];
            unset($this->request->data['schoolDays']);

            $data = $this->request->data['TeachersAttendance'];
            $yearId = $data['school_year_id'];

            if ($schoolDayNo < $totalNo) {
                $this->Utility->alert('Total no of days Attended and Total no of days Absent cannot exceed the no of School Days.', array('type' => 'error'));
                $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'teachersAttendanceEdit', $yearId));
            } else {
                $thisId = $this->TeacherAttendance->findID($this->Session->read('InstitutionSiteTeachersId'), $yearId);
                if ($thisId != '') {
                    $data['id'] = $thisId;
                }
                $this->TeacherAttendance->save($data);
                $this->Utility->alert($this->Utility->getMessage('SITE_TEACHER_ATTENDANCE_UPDATED'));
                $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'teachersAttendance', $yearId));
            }
        }
    }

    // END TEACHER ATTENDANCE PART
    // TEACHER BEHAVIOUR PART
    public function teachersBehaviour() {
        extract($this->teachersCustFieldYrInits());
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->TeacherBehaviour->getBehaviourData($id);
        if (empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('TEACHER_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
        }

        $this->set('id', $id);
        $this->set('data', $data);
    }

    public function teachersBehaviourAdd() {
        if ($this->request->is('get')) {
            $teacherId = $this->params['pass'][0];
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
            $this->Navigation->addCrumb('Add Behaviour');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();

            $categoryOptions = array();
            $categoryOptions = $this->TeacherBehaviourCategory->getCategory();
            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
            $this->set('institution_site_id', $this->institutionSiteId);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
            $this->set('id', $teacherId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
        } else {
            $teacherBehaviourData = $this->data['InstitutionSiteTeacherBehaviour'];
            $teacherBehaviourData['institution_site_id'] = $this->institutionSiteId;

            $this->TeacherBehaviour->create();
            if (!$this->TeacherBehaviour->save($teacherBehaviourData)) {
                
            } else {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
            }

            $this->redirect(array('action' => 'teachersBehaviour', $teacherBehaviourData['teacher_id']));
        }
    }

    public function teachersBehaviourView() {
        $teacherBehaviourId = $this->params['pass'][0];
        $teacherBehaviourObj = $this->TeacherBehaviour->find('all', array('conditions' => array('TeacherBehaviour.id' => $teacherBehaviourId)));

        if (!empty($teacherBehaviourObj)) {
            $teacherId = $teacherBehaviourObj[0]['TeacherBehaviour']['teacher_id'];
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->TeacherBehaviourCategory->getCategory();
            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
            $this->set('institution_site_id', $this->institutionSiteId);
            $this->set('institutionSiteOptions', $institutionSiteOptions);

            $this->Session->write('TeacherBehaviourId', $teacherBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('teacherBehaviourObj', $teacherBehaviourObj);
        } else {
            //$this->redirect(array('action' => 'classesList'));
        }
    }

    public function teachersBehaviourEdit() {
        if ($this->request->is('get')) {
            $teacherBehaviourId = $this->params['pass'][0];
            $teacherBehaviourObj = $this->TeacherBehaviour->find('all', array('conditions' => array('TeacherBehaviour.id' => $teacherBehaviourId)));

            if (!empty($teacherBehaviourObj)) {
                $teacherId = $teacherBehaviourObj[0]['TeacherBehaviour']['teacher_id'];
                if ($teacherBehaviourObj[0]['TeacherBehaviour']['institution_site_id'] != $this->institutionSiteId) {
                    $this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
                    $this->redirect(array('action' => 'teachersBehaviourView', $teacherBehaviourId));
                }
                $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
                $name = sprintf('%s %s %s', $data['Teacher']['first_name'], $data['Teacher']['middle_name'], $data['Teacher']['last_name']);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
                $this->Navigation->addCrumb('Edit Behaviour Details');

                $categoryOptions = array();
                $categoryOptions = $this->TeacherBehaviourCategory->getCategory();
                $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
                $this->set('institutionSiteOptions', $institutionSiteOptions);

                $this->set('categoryOptions', $categoryOptions);
                $this->set('teacherBehaviourObj', $teacherBehaviourObj);
            } else {
                //$this->redirect(array('action' => 'studentsBehaviour'));
            }
        } else {
            $teacherBehaviourData = $this->data['InstitutionSiteTeacherBehaviour'];
            $teacherBehaviourData['institution_site_id'] = $this->institutionSiteId;

            $this->TeacherBehaviour->create();
            if (!$this->TeacherBehaviour->save($teacherBehaviourData)) {
                
            } else {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
            }

            $this->redirect(array('action' => 'teachersBehaviourView', $teacherBehaviourData['id']));
        }
    }

    public function teachersBehaviourDelete() {
        if ($this->Session->check('InstitutionSiteTeachersId') && $this->Session->check('TeacherBehaviourId')) {
            $id = $this->Session->read('TeacherBehaviourId');
            $teacherId = $this->Session->read('InstitutionSiteTeachersId');
            $name = $this->TeacherBehaviour->field('title', array('TeacherBehaviour.id' => $id));
            $institution_site_id = $this->TeacherBehaviour->field('institution_site_id', array('TeacherBehaviour.id' => $id));
            if ($institution_site_id != $this->institutionSiteId) {
                $this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
                $this->redirect(array('action' => 'teachersBehaviourView', $id));
            }
            $this->TeacherBehaviour->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'teachersBehaviour', $teacherId));
        }
    }

    public function teachersBehaviourCheckName() {
        $this->autoRender = false;
        $title = trim($this->params->query['title']);

        if (strlen($title) == 0) {
            return $this->Utility->getMessage('SITE_TEACHER_BEHAVIOUR_EMPTY_TITLE');
        }

        return 'true';
    }

    // END TEACHER BEHAVIOUR PART
    // STAFF ATTENDANCE PART
    public function staffAttendance() {
        if ($this->Session->check('InstitutionSiteStaffId')) {
            $staffId = $this->Session->read('InstitutionSiteStaffId');
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
            $this->Navigation->addCrumb('Attendance');

            $id = @$this->request->params['pass'][0];
            $yearList = $this->SchoolYear->getYearList();
            $yearId = $this->getAvailableYearId($yearList);
            $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

            $data = $this->StaffAttendance->getAttendanceData($this->Session->read('InstitutionSiteStaffId'), isset($id) ? $id : $yearId);

            $this->set('selectedYear', $yearId);
            $this->set('years', $yearList);
            $this->set('data', $data);
            $this->set('schoolDays', $schoolDays);
            $this->set('id', $staffId);
        }
    }

    public function staffAttendanceEdit() {
        if ($this->request->is('get')) {
            if ($this->Session->check('InstitutionSiteStaffId')) {
                $staffId = $this->Session->read('InstitutionSiteStaffId');
                $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
                $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
                $this->Navigation->addCrumb('Edit Attendance');

                $yearList = $this->SchoolYear->getYearList();
                $yearId = $this->getAvailableYearId($yearList);
                $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

                $data = $this->StaffAttendance->getAttendanceData($this->Session->read('InstitutionSiteStaffId'), $yearId);

                $this->set('staffid', $this->Session->read('InstitutionSiteStaffId'));
                $this->set('institutionSiteId', $this->institutionSiteId);
                $this->set('selectedYear', $yearId);
                $this->set('years', $yearList);
                $this->set('schoolDays', $schoolDays);
                $this->set('data', $data);
            }
        } else {
            $schoolDayNo = $this->request->data['schoolDays'];
            $totalNo = $this->request->data['StaffAttendance']['total_no_attend'] + $this->request->data['StaffAttendance']['total_no_absence'];
            unset($this->request->data['schoolDays']);

            $data = $this->request->data['StaffAttendance'];
            $yearId = $data['school_year_id'];

            if ($schoolDayNo < $totalNo) {
                $this->Utility->alert('Total no of days Attended and Total no of days Absent cannot exceed the no of School Days.', array('type' => 'error'));
                $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'staffAttendanceEdit', $yearId));
            } else {
                $thisId = $this->StaffAttendance->findID($this->Session->read('InstitutionSiteStaffId'), $yearId);
                if ($thisId != '') {
                    $data['id'] = $thisId;
                }
                $this->StaffAttendance->save($data);
                $this->Utility->alert($this->Utility->getMessage('SITE_STAFF_ATTENDANCE_UPDATED'));
                $this->redirect(array('controller' => 'InstitutionSites', 'action' => 'staffAttendance', $yearId));
            }
        }
    }

    // END STAFF ATTENDANCE PART
    // STAFF BEHAVIOUR PART
    public function staffBehaviour() {
        extract($this->staffCustFieldYrInits());
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->StaffBehaviour->getBehaviourData($id);
        if (empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('TEACHER_NO_BEHAVIOUR_DATA'), array('type' => 'info'));
        }

        $this->set('id', $id);
        $this->set('data', $data);
    }

    public function staffBehaviourAdd() {
        if ($this->request->is('get')) {
            $staffId = $this->params['pass'][0];
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
            $this->Navigation->addCrumb('Add Behaviour');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();

            $categoryOptions = array();
            $categoryOptions = $this->StaffBehaviourCategory->getCategory();
            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
            $this->set('institution_site_id', $this->institutionSiteId);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
            $this->set('id', $staffId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
        } else {
            $staffBehaviourData = $this->data['InstitutionSiteStaffBehaviour'];
            $staffBehaviourData['institution_site_id'] = $this->institutionSiteId;

            $this->StaffBehaviour->create();
            if (!$this->StaffBehaviour->save($staffBehaviourData)) {
                
            } else {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
            }

            $this->redirect(array('action' => 'staffBehaviour', $staffBehaviourData['staff_id']));
        }
    }

    public function staffBehaviourView() {
        $staffBehaviourId = $this->params['pass'][0];
        $staffBehaviourObj = $this->StaffBehaviour->find('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

        if (!empty($staffBehaviourObj)) {
            $staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
            $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->StaffBehaviourCategory->getCategory();
            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
            $this->set('institution_site_id', $this->institutionSiteId);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
            $this->Session->write('StaffBehaviourId', $staffBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('staffBehaviourObj', $staffBehaviourObj);
        } else {
            //$this->redirect(array('action' => 'classesList'));
        }
    }

    public function staffBehaviourEdit() {
        if ($this->request->is('get')) {
            $staffBehaviourId = $this->params['pass'][0];
            $staffBehaviourObj = $this->StaffBehaviour->find('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

            if (!empty($staffBehaviourObj)) {
                $staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
                if ($staffBehaviourObj[0]['StaffBehaviour']['institution_site_id'] != $this->institutionSiteId) {
                    $this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
                    $this->redirect(array('action' => 'staffBehaviourView', $staffBehaviourId));
                }
                $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
                $name = sprintf('%s %s %s', $data['Staff']['first_name'], $data['Staff']['middle_name'], $data['Staff']['last_name']);
                $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'staffView', $staffId));
                $this->Navigation->addCrumb('Edit Behaviour Details');

                $categoryOptions = array();
                $categoryOptions = $this->StaffBehaviourCategory->getCategory();
                $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
                $this->set('institution_site_id', $this->institutionSiteId);
                $this->set('institutionSiteOptions', $institutionSiteOptions);
                $this->set('categoryOptions', $categoryOptions);
                $this->set('staffBehaviourObj', $staffBehaviourObj);
            } else {
                //$this->redirect(array('action' => 'studentsBehaviour'));
            }
        } else {
            $staffBehaviourData = $this->data['InstitutionSiteStaffBehaviour'];
            $staffBehaviourData['institution_site_id'] = $this->institutionSiteId;

            $this->StaffBehaviour->create();
            if (!$this->StaffBehaviour->save($staffBehaviourData)) {
                
            } else {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
            }

            $this->redirect(array('action' => 'staffBehaviourView', $staffBehaviourData['id']));
        }
    }

    public function staffBehaviourDelete() {
        if ($this->Session->check('InstitutionSiteStaffId') && $this->Session->check('StaffBehaviourId')) {
            $id = $this->Session->read('StaffBehaviourId');
            $staffId = $this->Session->read('InstitutionSiteStaffId');
            $name = $this->StaffBehaviour->field('title', array('StaffBehaviour.id' => $id));
            $institution_site_id = $this->StaffBehaviour->field('institution_site_id', array('StaffBehaviour.id' => $id));
            if ($institution_site_id != $this->institutionSiteId) {
                $this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'));
                $this->redirect(array('action' => 'staffsBehaviourView', $id));
            }
            $this->StaffBehaviour->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'staffBehaviour', $staffId));
        }
    }

    public function staffBehaviourCheckName() {
        $this->autoRender = false;
        $title = trim($this->params->query['title']);

        if (strlen($title) == 0) {
            return $this->Utility->getMessage('SITE_STUDENT_BEHAVIOUR_EMPTY_TITLE');
        }

        return 'true';
    }

    // END STAFF BEHAVIOUR PART

    private function getAvailableYearId($yearList) {
        $yearId = 0;
        if (isset($this->params['pass'][0])) {
            $yearId = $this->params['pass'][0];
            if (!array_key_exists($yearId, $yearList)) {
                $yearId = key($yearList);
            }
        } else {
            $yearId = key($yearList);
        }
        return $yearId;
    }

    public function getSiteProfile() {
        
    }

    public function siteProfile($id) {

        $levels = $this->AreaLevel->find('list', array('recursive' => 0));
        $adminarealevels = $this->AreaEducationLevel->find('list', array('recursive' => 0));
        $data = $this->InstitutionSite->find('first', array('conditions' => array('InstitutionSite.id' => $id)));

        $areaLevel = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_id']);
        $areaLevel = array_reverse($areaLevel);

        $adminarea = $this->AreaHandler->getAreatoParent($data['InstitutionSite']['area_education_id'], array('AreaEducation', 'AreaEducationLevel'));
        $adminarea = array_reverse($adminarea);

        $this->set('data', $data);
        $this->set('levels', $levels);
        $this->set('adminarealevel', $adminarealevels);

        $this->set('arealevel', $areaLevel);
        $this->set('adminarea', $adminarea);
    }

    private function getReportData($name) {
        if (array_key_exists($name, $this->reportMapping)) {
            $whereKey = ($this->reportMapping[$name]['Model'] == 'InstitutionSite') ? 'id' : 'institution_site_id';
            $cond = array($this->reportMapping[$name]['Model'] . "." . $whereKey => $this->institutionSiteId);
            $options = array('fields' => $this->getFields($name), 'conditions' => $cond);

            if ($name == 'More') {
                $options['joins'] = array(
                    array('table' => 'institution_site_custom_field_options',
                        'alias' => 'InstitutionSiteCustomFieldOption',
                        'type' => 'LEFT',
                        'conditions' => array(
                            'InstitutionSiteCustomValue.value = InstitutionSiteCustomFieldOption.id'
                        )
                    )
                );

                $options['order'] = array('InstitutionSiteCustomField.institution_site_type_id', 'InstitutionSiteCustomField.order', 'InstitutionSiteCustomValue.id');

                $this->{$this->reportMapping[$name]['Model']}->virtualFields = array(
                    'custom_value' => 'IF((InstitutionSiteCustomField.type = 3) OR (InstitutionSiteCustomField.type = 4), InstitutionSiteCustomFieldOption.value, InstitutionSiteCustomValue.value)'
                );
            } else if ($name == 'Classes - Students') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_site_class_grades',
                        'alias' => 'InstitutionSiteClassGrade',
                        'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id')
                    ),
                    array(
                        'table' => 'institution_site_class_grade_students',
                        'alias' => 'InstitutionSiteClassGradeStudent',
                        'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id = InstitutionSiteClassGrade.id')
                    ),
                    array(
                        'table' => 'education_grades',
                        'alias' => 'EducationGrade',
                        'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
                    ),
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('Student.id = InstitutionSiteClassGradeStudent.student_id')
                    ),
                    array(
                        'table' => 'student_categories',
                        'alias' => 'StudentCategory',
                        'conditions' => array('StudentCategory.id = InstitutionSiteClassGradeStudent.student_category_id')
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('SchoolYear.id = InstitutionSiteClass.school_year_id')
                    )
                );
                $options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name', 'Student.first_name');
            } else if ($name == 'Bank Accounts') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'bank_branches',
                        'alias' => 'BankBranch',
                        'conditions' => array('InstitutionSiteBankAccount.bank_branch_id = BankBranch.id')
                    ),
                    array(
                        'table' => 'banks',
                        'alias' => 'Bank',
                        'conditions' => array('BankBranch.bank_id = Bank.id')
                    )
                );
            } else if ($name == 'Overview') {
                //$options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'area_educations',
                        'alias' => 'AreaEducation',
                        'conditions' => array('InstitutionSite.area_education_id = AreaEducation.id')
                    )
                );
            } else if ($name == 'Programme List') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array(
                            'InstitutionSiteProgramme.school_year_id = SchoolYear.id'
                        )
                    ),
                    array(
                        'table' => 'education_programmes',
                        'alias' => 'EducationProgramme',
                        'conditions' => array(
                            'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id'
                        )
                    ),
                    array(
                        'table' => 'education_cycles',
                        'alias' => 'EducationCycle',
                        'conditions' => array(
                            'EducationProgramme.education_cycle_id = EducationCycle.id'
                        )
                    ),
                    array(
                        'table' => 'education_levels',
                        'alias' => 'EducationLevel',
                        'conditions' => array(
                            'EducationCycle.education_level_id = EducationLevel.id'
                        )
                    ),
                    array(
                        'table' => 'education_systems',
                        'alias' => 'EducationSystem',
                        'conditions' => array(
                            'EducationLevel.education_system_id = EducationSystem.id'
                        )
                    )
                );

                $options['order'] = array('SchoolYear.name', 'EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order');

                $this->{$this->reportMapping[$name]['Model']}->virtualFields = array(
                    'system_cycle' => 'CONCAT(EducationSystem.name, " - ", EducationCycle.name)'
                );
            } else if ($name == 'Student List') {
                $options['conditions'] = array();
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
                    ),
                    array(
                        'table' => 'institution_site_programmes',
                        'alias' => 'InstitutionSiteProgramme',
                        'conditions' => array(
                            'InstitutionSiteStudent.institution_site_programme_id = InstitutionSiteProgramme.id',
                            'InstitutionSiteProgramme.institution_site_id = ' . $this->institutionSiteId
                        )
                    ),
                    array(
                        'table' => 'education_programmes',
                        'alias' => 'EducationProgramme',
                        'conditions' => array('InstitutionSiteProgramme.education_programme_id = EducationProgramme.id')
                    ),
                    array(
                        'table' => 'student_statuses',
                        'alias' => 'StudentStatus',
                        'type' => 'left',
                        'conditions' => array('InstitutionSiteStudent.student_status_id = StudentStatus.id')
                    )
                );

                $options['order'] = array('Student.first_name');
            } else if ($name == 'Student Result') {
                $options['conditions'] = array();
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_site_class_grades',
                        'alias' => 'InstitutionSiteClassGrade',
                        'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id = InstitutionSiteClassGrade.id')
                    ),
                    array(
                        'table' => 'education_grades',
                        'alias' => 'EducationGrade',
                        'conditions' => array(
                            'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id'
                        )
                    ),
                    array(
                        'table' => 'institution_site_classes',
                        'alias' => 'InstitutionSiteClass',
                        'conditions' => array(
                            'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
                            'InstitutionSiteClass.institution_site_id = ' . $this->institutionSiteId
                        )
                    ),
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'InstitutionSiteClass.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
                    ),
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('InstitutionSiteClassGradeStudent.student_id = Student.id')
                    ),
                    array(
                        'table' => 'assessment_item_types',
                        'alias' => 'AssessmentItemType',
                        'conditions' => array('InstitutionSiteClassGrade.education_grade_id = AssessmentItemType.education_grade_id')
                    ),
                    array(
                        'table' => 'assessment_items',
                        'alias' => 'AssessmentItem',
                        'conditions' => array('AssessmentItem.assessment_item_type_id = AssessmentItemType.id')
                    ),
                    array(
                        'table' => 'education_grades_subjects',
                        'alias' => 'EducationGradeSubject',
                        'conditions' => array('AssessmentItem.education_grade_subject_id = EducationGradeSubject.id')
                    ),
                    array(
                        'table' => 'education_subjects',
                        'alias' => 'EducationSubject',
                        'conditions' => array('EducationGradeSubject.education_subject_id = EducationSubject.id')
                    ),
                    array(
                        'table' => 'assessment_item_results',
                        'alias' => 'AssessmentItemResult',
                        'type' => 'LEFT',
                        'conditions' => array(
                            'AssessmentItemResult.student_id = Student.id',
                            'AssessmentItemResult.institution_site_id = InstitutionSiteClass.institution_site_id',
                            'AssessmentItemResult.school_year_id = InstitutionSiteClass.school_year_id',
                            'AssessmentItemResult.assessment_item_id = AssessmentItem.id'
                        )
                    ),
                    array(
                        'table' => 'assessment_result_types',
                        'alias' => 'AssessmentResultType',
                        'type' => 'LEFT',
                        'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
                    )
                );

                $options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name', 'EducationGrade.name', 'AssessmentItemType.name', 'EducationSubject.name', 'Student.identification_no');
            } else if ($name == 'Student Attendance') {
                $options['conditions'] = array();
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_site_class_grades',
                        'alias' => 'InstitutionSiteClassGrade',
                        'conditions' => array('InstitutionSiteClassGradeStudent.institution_site_class_grade_id = InstitutionSiteClassGrade.id')
                    ),
                    array(
                        'table' => 'education_grades',
                        'alias' => 'EducationGrade',
                        'conditions' => array(
                            'InstitutionSiteClassGrade.education_grade_id = EducationGrade.id'
                        )
                    ),
                    array(
                        'table' => 'institution_site_classes',
                        'alias' => 'InstitutionSiteClass',
                        'conditions' => array(
                            'InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id',
                            'InstitutionSiteClass.institution_site_id = ' . $this->institutionSiteId
                        )
                    ),
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'InstitutionSiteClass.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
                    ),
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('InstitutionSiteClassGradeStudent.student_id = Student.id')
                    ),
                    array(
                        'table' => 'student_attendances',
                        'alias' => 'StudentAttendance',
                        'type' => 'LEFT',
                        'conditions' => array(
                            'StudentAttendance.student_id = Student.id',
                            'StudentAttendance.institution_site_class_id = InstitutionSiteClassGrade.institution_site_class_id',
                            'StudentAttendance.school_year_id = InstitutionSiteClass.school_year_id'
                        )
                    )
                );

                $options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name', 'EducationGrade.name', 'Student.identification_no');

                $this->{$this->reportMapping[$name]['Model']}->virtualFields = array(
                    'total' => 'StudentAttendance.total_no_attend + StudentAttendance.total_no_absence'
                );
            } else if ($name == 'Student Behaviour') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'StudentBehaviour.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'student_behaviour_categories',
                        'alias' => 'StudentBehaviourCategory',
                        'conditions' => array('StudentBehaviour.student_behaviour_category_id = StudentBehaviourCategory.id')
                    ),
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array('StudentBehaviour.student_id = Student.id')
                    )
                );

                $options['order'] = array('Student.identification_no', 'StudentBehaviour.date_of_behaviour', 'StudentBehaviour.id');
            } else if ($name == 'Teacher List') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'teachers',
                        'alias' => 'Teacher',
                        'conditions' => array('InstitutionSiteTeacher.teacher_id = Teacher.id')
                    ),
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array('InstitutionSiteTeacher.institution_site_id = InstitutionSite.id')
                    )
                );

                $options['group'] = array('Teacher.id');
                $options['order'] = array('Teacher.first_name');
            } else if ($name == 'Teacher Attendance') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'TeacherAttendance.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'teachers',
                        'alias' => 'Teacher',
                        'conditions' => array('TeacherAttendance.teacher_id = Teacher.id')
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('TeacherAttendance.school_year_id = SchoolYear.id')
                    ),
                );

                $options['order'] = array('Teacher.identification_no', 'SchoolYear.name');

                $this->{$this->reportMapping[$name]['Model']}->virtualFields = array(
                    'total' => 'TeacherAttendance.total_no_attend + TeacherAttendance.total_no_absence'
                );
            } else if ($name == 'Teacher Behaviour') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'TeacherBehaviour.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'teacher_behaviour_categories',
                        'alias' => 'TeacherBehaviourCategory',
                        'conditions' => array('TeacherBehaviour.teacher_behaviour_category_id = TeacherBehaviourCategory.id')
                    ),
                    array(
                        'table' => 'teachers',
                        'alias' => 'Teacher',
                        'conditions' => array('TeacherBehaviour.teacher_id = Teacher.id')
                    )
                );

                $options['order'] = array('Teacher.identification_no', 'TeacherBehaviour.date_of_behaviour', 'TeacherBehaviour.id');
            } else if ($name == 'Staff List') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
                    ),
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array('InstitutionSiteStaff.institution_site_id = InstitutionSite.id')
                    )
                );

                $options['group'] = array('Staff.id');
                $options['order'] = array('Staff.first_name');
            } else if ($name == 'Staff Attendance') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'StaffAttendance.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array('StaffAttendance.staff_id = Staff.id')
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('StaffAttendance.school_year_id = SchoolYear.id')
                    ),
                );

                $options['order'] = array('Staff.identification_no', 'SchoolYear.name');

                $this->{$this->reportMapping[$name]['Model']}->virtualFields = array(
                    'total' => 'StaffAttendance.total_no_attend + StaffAttendance.total_no_absence'
                );
            } else if ($name == 'Staff Behaviour') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'StaffBehaviour.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'staff_behaviour_categories',
                        'alias' => 'StaffBehaviourCategory',
                        'conditions' => array('StaffBehaviour.staff_behaviour_category_id = StaffBehaviourCategory.id')
                    ),
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array('StaffBehaviour.staff_id = Staff.id')
                    )
                );

                $options['order'] = array('Staff.identification_no', 'StaffBehaviour.date_of_behaviour', 'StaffBehaviour.id');
            } else if ($name == 'Class List') {
                $options['recursive'] = -1;
                $options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'InstitutionSiteClass.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
                    )
                );

                $options['order'] = array('SchoolYear.name', 'InstitutionSiteClass.name');
            } else if ($name == 'QA Report') {
                $options['recursive'] = -1;
                $options['joins'] = array(
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
                        'type' => 'LEFT',
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
                            'QualityInstitutionRubric.institution_site_class_grade_id = InstitutionSiteClassGrade.id',
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


                $options['order'] = array('SchoolYear.name DESC', 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id', 'RubricTemplateHeader.order');
                $options['group'] = array('InstitutionSiteClass.id', 'RubricTemplate.id', 'RubricTemplateHeader.id');

                //  pr('in if statement');
            } else if ($name == 'Visit Report') {
                $options['recursive'] = -1;

                $options['joins'] = array(
                    array(
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
                            'QualityInstitutionVisit.institution_site_class_id = InstitutionSiteClass.id',
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
                    )
                );
            }

            $data = $this->{$this->reportMapping[$name]['Model']}->find('all', $options);
        }
        // pr($this->reportMapping[$name]);
        //  pr($data); die;
        return $data;
    }

    private function getHeaderAcademic($name) {
        $commonFields = array(
            'institution_site' => 'Institution Site',
            'openemis_id' => 'OpenEMIS ID',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'preferred_name' => 'Preferred Name',
            'year' => 'Year'
        );
        $customFields = array();

        if ($name == 'Student Academic') {
            $customFields = $this->StudentDetailsCustomField->find('list', array(
                'fields' => array('id', 'name'),
                'conditions' => array(
                    'visible' => 1,
                    'type > ' => 1
                ),
                'order' => array('order')
                    )
            );
        } else if ($name == 'Teacher Academic') {
            $customFields = $this->TeacherDetailsCustomField->find('list', array(
                'fields' => array('id', 'name'),
                'conditions' => array(
                    'visible' => 1,
                    'type > ' => 1
                ),
                'order' => array('order')
                    )
            );
        } else if ($name == 'Staff Academic') {
            $customFields = $this->StaffDetailsCustomField->find('list', array(
                'fields' => array('id', 'name'),
                'conditions' => array(
                    'visible' => 1,
                    'type > ' => 1
                ),
                'order' => array('order')
                    )
            );
        }

        foreach ($commonFields AS &$value) {
            $value = __($value);
        }

        $resultFields = $commonFields;

        foreach ($customFields AS $fieldId => $fieldName) {
            $resultFields[$fieldId] = $fieldName;
        }

        //$resultFields = array_merge($commonFields, $customFields);
        return $resultFields;
    }

    private function getReportDataAcademic($name) {
        $header = $this->getHeaderAcademic($name);
        $rowTpl = array();
        foreach ($header AS $key => $field) {
            $rowTpl[$key] = '';
        }
        //pr($rowTpl);
        $data = array();

        if ($name == 'Student Academic') {
            $studentsYears = $this->StudentDetailsCustomValue->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'StudentDetailsCustomValue.student_id',
                    'Student.identification_no',
                    'Student.first_name',
                    'Student.middle_name',
                    'Student.last_name',
                    'Student.preferred_name',
                    'StudentDetailsCustomValue.school_year_id',
                    'SchoolYear.name'
                ),
                'joins' => array(
                    array(
                        'table' => 'students',
                        'alias' => 'Student',
                        'conditions' => array(
                            'StudentDetailsCustomValue.student_id = Student.id'
                        )
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array(
                            'StudentDetailsCustomValue.school_year_id = SchoolYear.id'
                        )
                    )
                ),
                'conditions' => array('StudentDetailsCustomValue.institution_site_id' => $this->institutionSiteId),
                'group' => array('StudentDetailsCustomValue.student_id', 'StudentDetailsCustomValue.school_year_id')
                    )
            );

            foreach ($studentsYears AS $rowValue) {
                $fieldValues = $this->StudentDetailsCustomValue->find('all', array(
                    'recursive' => -1,
                    'fields' => array(
                        'StudentDetailsCustomField.id',
                        'StudentDetailsCustomField.name',
                        'StudentDetailsCustomField.type',
                        'StudentDetailsCustomValue.value',
                        'StudentDetailsCustomFieldOption.value'
                    ),
                    'joins' => array(
                        array(
                            'table' => 'student_details_custom_fields',
                            'alias' => 'StudentDetailsCustomField',
                            'conditions' => array(
                                'StudentDetailsCustomValue.student_details_custom_field_id = StudentDetailsCustomField.id'
                            )
                        ),
                        array(
                            'table' => 'student_details_custom_field_options',
                            'alias' => 'StudentDetailsCustomFieldOption',
                            'type' => 'LEFT',
                            'conditions' => array(
                                'StudentDetailsCustomValue.value = StudentDetailsCustomFieldOption.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'StudentDetailsCustomValue.institution_site_id' => $this->institutionSiteId,
                        'StudentDetailsCustomValue.student_id' => $rowValue['StudentDetailsCustomValue']['student_id'],
                        'StudentDetailsCustomValue.school_year_id' => $rowValue['StudentDetailsCustomValue']['school_year_id']
                    )
                        )
                );

                $row = $rowTpl;
                $row['institution_site'] = $this->institutionSiteObj['InstitutionSite']['name'];
                $row['openemis_id'] = $rowValue['Student']['identification_no'];
                $row['first_name'] = $rowValue['Student']['first_name'];
                $row['middle_name'] = $rowValue['Student']['middle_name'];
                $row['last_name'] = $rowValue['Student']['last_name'];
                $row['preferred_name'] = $rowValue['Student']['preferred_name'];
                $row['year'] = $rowValue['SchoolYear']['name'];

                foreach ($fieldValues AS $fieldValueRow) {
                    $fieldId = $fieldValueRow['StudentDetailsCustomField']['id'];
                    $fieldName = $fieldValueRow['StudentDetailsCustomField']['name'];
                    $fieldType = $fieldValueRow['StudentDetailsCustomField']['type'];

                    if ($fieldType == 3) {
                        $row[$fieldId] = $fieldValueRow['StudentDetailsCustomFieldOption']['value'];
                    } else if ($fieldType == 4) {
                        if (empty($row[$fieldId])) {
                            $row[$fieldId] = $fieldValueRow['StudentDetailsCustomFieldOption']['value'];
                        } else {
                            $row[$fieldId] .= ', ' . $fieldValueRow['StudentDetailsCustomFieldOption']['value'];
                        }
                    } else {
                        $row[$fieldId] = $fieldValueRow['StudentDetailsCustomValue']['value'];
                    }
                }
                //pr($row);
                $data[] = $row;
            }
        } else if ($name == 'Teacher Academic') {
            $teachersYears = $this->TeacherDetailsCustomValue->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'TeacherDetailsCustomValue.teacher_id',
                    'Teacher.identification_no',
                    'Teacher.first_name',
                    'Teacher.middle_name',
                    'Teacher.last_name',
                    'Teacher.preferred_name',
                    'TeacherDetailsCustomValue.school_year_id',
                    'SchoolYear.name'
                ),
                'joins' => array(
                    array(
                        'table' => 'teachers',
                        'alias' => 'Teacher',
                        'conditions' => array(
                            'TeacherDetailsCustomValue.teacher_id = Teacher.id'
                        )
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array(
                            'TeacherDetailsCustomValue.school_year_id = SchoolYear.id'
                        )
                    )
                ),
                'conditions' => array('TeacherDetailsCustomValue.institution_site_id' => $this->institutionSiteId),
                'group' => array('TeacherDetailsCustomValue.teacher_id', 'TeacherDetailsCustomValue.school_year_id')
                    )
            );

            foreach ($teachersYears AS $rowValue) {
                $fieldValues = $this->TeacherDetailsCustomValue->find('all', array(
                    'recursive' => -1,
                    'fields' => array(
                        'TeacherDetailsCustomField.id',
                        'TeacherDetailsCustomField.name',
                        'TeacherDetailsCustomField.type',
                        'TeacherDetailsCustomValue.value',
                        'TeacherDetailsCustomFieldOption.value'
                    ),
                    'joins' => array(
                        array(
                            'table' => 'teacher_details_custom_fields',
                            'alias' => 'TeacherDetailsCustomField',
                            'conditions' => array(
                                'TeacherDetailsCustomValue.teacher_details_custom_field_id = TeacherDetailsCustomField.id'
                            )
                        ),
                        array(
                            'table' => 'teacher_details_custom_field_options',
                            'alias' => 'TeacherDetailsCustomFieldOption',
                            'type' => 'LEFT',
                            'conditions' => array(
                                'TeacherDetailsCustomValue.value = TeacherDetailsCustomFieldOption.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'TeacherDetailsCustomValue.institution_site_id' => $this->institutionSiteId,
                        'TeacherDetailsCustomValue.teacher_id' => $rowValue['TeacherDetailsCustomValue']['teacher_id'],
                        'TeacherDetailsCustomValue.school_year_id' => $rowValue['TeacherDetailsCustomValue']['school_year_id']
                    )
                        )
                );

                $row = $rowTpl;
                $row['institution_site'] = $this->institutionSiteObj['InstitutionSite']['name'];
                $row['openemis_id'] = $rowValue['Teacher']['identification_no'];
                $row['first_name'] = $rowValue['Teacher']['first_name'];
                $row['middle_name'] = $rowValue['Teacher']['middle_name'];
                $row['last_name'] = $rowValue['Teacher']['last_name'];
                $row['preferred_name'] = $rowValue['Teacher']['preferred_name'];
                $row['year'] = $rowValue['SchoolYear']['name'];

                foreach ($fieldValues AS $fieldValueRow) {
                    $fieldId = $fieldValueRow['TeacherDetailsCustomField']['id'];
                    $fieldName = $fieldValueRow['TeacherDetailsCustomField']['name'];
                    $fieldType = $fieldValueRow['TeacherDetailsCustomField']['type'];

                    if ($fieldType == 3) {
                        $row[$fieldId] = $fieldValueRow['TeacherDetailsCustomFieldOption']['value'];
                    } else if ($fieldType == 4) {
                        if (empty($row[$fieldId])) {
                            $row[$fieldId] = $fieldValueRow['TeacherDetailsCustomFieldOption']['value'];
                        } else {
                            $row[$fieldId] .= ', ' . $fieldValueRow['TeacherDetailsCustomFieldOption']['value'];
                        }
                    } else {
                        $row[$fieldId] = $fieldValueRow['TeacherDetailsCustomValue']['value'];
                    }
                }
                //pr($row);
                $data[] = $row;
            }
        } else if ($name == 'Staff Academic') {
            $staffYears = $this->StaffDetailsCustomValue->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'StaffDetailsCustomValue.staff_id',
                    'Staff.identification_no',
                    'Staff.first_name',
                    'Staff.middle_name',
                    'Staff.last_name',
                    'Staff.preferred_name',
                    'StaffDetailsCustomValue.school_year_id',
                    'SchoolYear.name'
                ),
                'joins' => array(
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array(
                            'StaffDetailsCustomValue.staff_id = Staff.id'
                        )
                    ),
                    array(
                        'table' => 'school_years',
                        'alias' => 'SchoolYear',
                        'conditions' => array(
                            'StaffDetailsCustomValue.school_year_id = SchoolYear.id'
                        )
                    )
                ),
                'conditions' => array('StaffDetailsCustomValue.institution_site_id' => $this->institutionSiteId),
                'group' => array('StaffDetailsCustomValue.staff_id', 'StaffDetailsCustomValue.school_year_id')
                    )
            );

            foreach ($staffYears AS $rowValue) {
                $fieldValues = $this->StaffDetailsCustomValue->find('all', array(
                    'recursive' => -1,
                    'fields' => array(
                        'StaffDetailsCustomField.id',
                        'StaffDetailsCustomField.name',
                        'StaffDetailsCustomField.type',
                        'StaffDetailsCustomValue.value',
                        'StaffDetailsCustomFieldOption.value'
                    ),
                    'joins' => array(
                        array(
                            'table' => 'staff_details_custom_fields',
                            'alias' => 'StaffDetailsCustomField',
                            'conditions' => array(
                                'StaffDetailsCustomValue.staff_details_custom_field_id = StaffDetailsCustomField.id'
                            )
                        ),
                        array(
                            'table' => 'staff_details_custom_field_options',
                            'alias' => 'StaffDetailsCustomFieldOption',
                            'type' => 'LEFT',
                            'conditions' => array(
                                'StaffDetailsCustomValue.value = StaffDetailsCustomFieldOption.id'
                            )
                        )
                    ),
                    'conditions' => array(
                        'StaffDetailsCustomValue.institution_site_id' => $this->institutionSiteId,
                        'StaffDetailsCustomValue.staff_id' => $rowValue['StaffDetailsCustomValue']['staff_id'],
                        'StaffDetailsCustomValue.school_year_id' => $rowValue['StaffDetailsCustomValue']['school_year_id']
                    )
                        )
                );

                $row = $rowTpl;
                $row['institution_site'] = $this->institutionSiteObj['InstitutionSite']['name'];
                $row['openemis_id'] = $rowValue['Staff']['identification_no'];
                $row['first_name'] = $rowValue['Staff']['first_name'];
                $row['middle_name'] = $rowValue['Staff']['middle_name'];
                $row['last_name'] = $rowValue['Staff']['last_name'];
                $row['preferred_name'] = $rowValue['Staff']['preferred_name'];
                $row['year'] = $rowValue['SchoolYear']['name'];

                foreach ($fieldValues AS $fieldValueRow) {
                    $fieldId = $fieldValueRow['StaffDetailsCustomField']['id'];
                    $fieldName = $fieldValueRow['StaffDetailsCustomField']['name'];
                    $fieldType = $fieldValueRow['StaffDetailsCustomField']['type'];

                    if ($fieldType == 3) {
                        $row[$fieldId] = $fieldValueRow['StaffDetailsCustomFieldOption']['value'];
                    } else if ($fieldType == 4) {
                        if (empty($row[$fieldId])) {
                            $row[$fieldId] = $fieldValueRow['StaffDetailsCustomFieldOption']['value'];
                        } else {
                            $row[$fieldId] .= ', ' . $fieldValueRow['StaffDetailsCustomFieldOption']['value'];
                        }
                    } else {
                        $row[$fieldId] = $fieldValueRow['StaffDetailsCustomValue']['value'];
                    }
                }
                //pr($row);
                $data[] = $row;
            }
        }

        return $data;
    }

    public function genReport($name, $type) { //$this->genReport('Site Details','CSV');
        $this->autoRender = false;
        $this->ReportData['name'] = $name;
        $this->ReportData['type'] = $type;

        if (method_exists($this, 'gen' . $type)) {
            if ($type == 'CSV') {
                if (array_key_exists($name, $this->reportMappingAcademic)) {
                    $data = $this->getReportDataAcademic($name);
                    $this->genCSVAcademic($data, $name);
                } else {
                    $data = $this->getReportData($name);
                    $this->genCSV($data, $this->ReportData['name']);
                }
            } elseif ($type == 'PDF') {
                $data = $this->genReportPDF($this->ReportData['name']);
                $data['name'] = $this->ReportData['name'];
                $this->genPDF($data);
            }
        }
    }

    public function genReportCensus($name, $type) {
        $this->autoRender = false;

        if (method_exists($this, 'gen' . $type)) {
            if ($type == 'CSV') {
                switch ($name) {
                    case 'Students':
                        $data = $this->getReportDataCensusStudents();
                        break;
                    case 'Teachers':
                        $data = $this->getReportDataCensusTeachers();
                        break;
                    case 'Staff':
                        $data = $this->getReportDataCensusStaff();
                        break;
                    case 'Classes':
                        $data = $this->getReportDataCensusClasses();
                        break;
                    case 'Shifts':
                        $data = $this->getReportDataCensusShifts();
                        break;
                    case 'Graduates':
                        $data = $this->getReportDataCensusGraduates();
                        break;
                    case 'Attendance':
                        $data = $this->getReportDataCensusAttendance();
                        break;
                    case 'Results':
                        $data = $this->getReportDataCensusResults();
                        break;
                    case 'Behaviour':
                        $data = $this->getReportDataCensusBehaviour();
                        break;
                    case 'Textbooks':
                        $data = $this->getReportDataCensusTextbooks();
                        break;
                    case 'Infrastructure':
                        $data = $this->getReportDataCensusInfrastructure();
                        break;
                    case 'Finances':
                        $data = $this->getReportDataCensusFinances();
                        break;
                    case 'More':
                        $data = $this->getReportDataCensusMore();
                        break;
                    default:
                        $data = array();
                }

                //pr($data);
                $this->genCSVCensus($data, $name);
            } elseif ($type == 'PDF') {
                
            }
        }
    }

    private function getReportDataCensusMore() {
        $data = array();
        $dataYears = $this->CensusGridValue->getYearsHaveValues($this->institutionSiteId);
        $institutionSiteObj = $this->institutionSiteObj;
        $institutionSiteTypeId = $institutionSiteObj['InstitutionSite']['institution_site_type_id'];

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $dataGrid = $this->CensusGrid->find('all', array(
                'conditions' => array(
                    'CensusGrid.institution_site_type_id' => array($institutionSiteTypeId, 0),
                    'CensusGrid.visible' => 1
                ),
                'order' => array('CensusGrid.institution_site_type_id', 'CensusGrid.order')
                    )
            );

            foreach ($dataGrid AS $rowGrid) {
                $data[] = array(__($yearName));
                $data[] = array(__($rowGrid['CensusGrid']['name']));
                $data[] = array();
                $header = array('');
                foreach ($rowGrid['CensusGridXCategory'] AS $rowX) {
                    $header[] = __($rowX['name']);
                }
                $data[] = $header;

                $dataGridValue = $this->CensusGridValue->find('all', array(
                    'recursive' => -1,
                    'conditions' => array(
                        'CensusGridValue.institution_site_id' => $this->institutionSiteId,
                        'CensusGridValue.census_grid_id' => $rowGrid['CensusGrid']['id'],
                        'CensusGridValue.school_year_id' => $yearId
                    )
                        )
                );

                $valuesCheckSource = array();
                foreach ($dataGridValue AS $rowGridValue) {
                    $census_grid_x_category_id = $rowGridValue['CensusGridValue']['census_grid_x_category_id'];
                    $census_grid_y_category_id = $rowGridValue['CensusGridValue']['census_grid_y_category_id'];
                    $valuesCheckSource[$census_grid_x_category_id][$census_grid_y_category_id] = $rowGridValue['CensusGridValue'];
                }

                foreach ($rowGrid['CensusGridYCategory'] AS $rowY) {
                    $idY = $rowY['id'];
                    $nameY = $rowY['name'];
                    $rowCsv = array(__($nameY));
                    //$totalRow = 0;
                    foreach ($rowGrid['CensusGridXCategory'] AS $rowX) {
                        $idX = $rowX['id'];
                        //$nameX = $rowX['name'];
                        if (isset($valuesCheckSource[$idX][$idY]['value'])) {
                            $valueCell = !empty($valuesCheckSource[$idX][$idY]['value']) ? $valuesCheckSource[$idX][$idY]['value'] : 0;
                        } else {
                            $valueCell = 0;
                        }
                        $rowCsv[] = $valueCell;
                        //$totalRow += $valueCell;
                    }
                    //$rowCsv[] = $totalRow;
                    $data[] = $rowCsv;
                }
                $data[] = array();
            }

            $dataFields = $this->CensusCustomField->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'CensusCustomField.id',
                    'CensusCustomField.type',
                    'CensusCustomField.name',
                    'CensusCustomValue.value'
                ),
                'joins' => array(
                    array(
                        'table' => 'census_custom_values',
                        'alias' => 'CensusCustomValue',
                        'type' => 'LEFT',
                        'conditions' => array(
                            'CensusCustomField.id = CensusCustomValue.census_custom_field_id'
                        )
                    )
                ),
                'conditions' => array(
                    'CensusCustomField.institution_site_type_id' => array($institutionSiteTypeId, 0),
                    'CensusCustomField.visible' => 1,
                    'CensusCustomValue.institution_site_id' => $this->institutionSiteId,
                    'CensusCustomValue.school_year_id' => $yearId
                ),
                'order' => array('CensusCustomField.institution_site_type_id', 'CensusCustomField.order'),
                'group' => array('CensusCustomField.id')
                    )
            );

            foreach ($dataFields AS $rowFields) {
                $fieldId = $rowFields['CensusCustomField']['id'];
                $fieldType = $rowFields['CensusCustomField']['type'];
                $fieldName = $rowFields['CensusCustomField']['name'];
                $fieldValue = $rowFields['CensusCustomValue']['value'];

                $data[] = array(__($yearName));
                $data[] = array($fieldName);
                $answer = '';
                if ($fieldType == 3 || $fieldType == 4) {
                    $dataValue = $this->CensusCustomValue->find('all', array(
                        'recursive' => -1,
                        'fields' => array('CensusCustomFieldOption.value'),
                        'joins' => array(
                            array(
                                'table' => 'census_custom_field_options',
                                'alias' => 'CensusCustomFieldOption',
                                'type' => 'LEFT',
                                'conditions' => array(
                                    'CensusCustomValue.value = CensusCustomFieldOption.id'
                                )
                            )
                        ),
                        'conditions' => array(
                            'CensusCustomValue.census_custom_field_id' => $fieldId,
                            'CensusCustomValue.institution_site_id' => $this->institutionSiteId,
                            'CensusCustomValue.school_year_id' => $yearId
                        )
                            )
                    );

                    $countValue = 1;
                    foreach ($dataValue AS $rowValue) {
                        if ($countValue == 1) {
                            $answer .= $rowValue['CensusCustomFieldOption']['value'];
                        } else {
                            $answer .= ', ';
                            $answer .= $rowValue['CensusCustomFieldOption']['value'];
                        }
                        $countValue++;
                    }
                } else {
                    if (!is_null($fieldValue)) {
                        $answer = $fieldValue;
                    }
                }

                $data[] = array($answer);
                $data[] = array();
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusInfrastructure() {
        $data = array();
        $headerCommon = array(__('Year'), __('Infrastructure Name'), __('Category'));
        $yearList = $this->SchoolYear->getYearList();
        foreach ($yearList AS $yearId => $yearName) {
            $infraCategories = $this->InfrastructureCategory->find('list', array('conditions' => array('InfrastructureCategory.visible' => 1), 'order' => 'InfrastructureCategory.order'));
            foreach ($infraCategories AS $categoryId => $categoryName) {
                $dataInfraStatuses = $this->InfrastructureStatus->find('list', array('conditions' => array('InfrastructureStatus.infrastructure_category_id' => $categoryId, 'InfrastructureStatus.visible' => 1)));
                $countStatuses = count($dataInfraStatuses);
                if ($categoryName == 'Sanitation') {
                    $dataInfraTypes = $this->InfrastructureSanitation->find('list', array('conditions' => array('InfrastructureSanitation.visible' => 1)));
                    $dataSanitationMaterials = $this->CensusSanitation->find('all', array(
                        'recursive' => -1,
                        'fields' => array(
                            'CensusSanitation.infrastructure_material_id',
                            'InfrastructureMaterial.name'
                        ),
                        'joins' => array(
                            array(
                                'table' => 'infrastructure_materials',
                                'alias' => 'InfrastructureMaterial',
                                'conditions' => array(
                                    'CensusSanitation.infrastructure_material_id = InfrastructureMaterial.id'
                                )
                            )
                        ),
                        'conditions' => array(
                            'CensusSanitation.institution_site_id' => $this->institutionSiteId,
                            'CensusSanitation.school_year_id' => $yearId
                        ),
                        'group' => array('CensusSanitation.infrastructure_material_id'),
                        'order' => array('InfrastructureMaterial.order')
                            )
                    );

                    if (count($dataSanitationMaterials) > 0) {
                        foreach ($dataSanitationMaterials AS $RowSanitationMaterials) {
                            $sanitationMaterialId = $RowSanitationMaterials['CensusSanitation']['infrastructure_material_id'];
                            $sanitationMaterialName = $RowSanitationMaterials['InfrastructureMaterial']['name'];
                            $dataSanitationMaterialsById = $this->CensusSanitation->find('all', array(
                                'recursive' => -1,
                                'conditions' => array(
                                    'CensusSanitation.institution_site_id' => $this->institutionSiteId,
                                    'CensusSanitation.school_year_id' => $yearId,
                                    'CensusSanitation.infrastructure_material_id' => $sanitationMaterialId
                                )
                                    )
                            );

                            $cellValueCheckSource = array();
                            foreach ($dataSanitationMaterialsById AS $rowSanitationMaterialsById) {
                                $infrastructure_sanitation_id = $rowSanitationMaterialsById['CensusSanitation']['infrastructure_sanitation_id'];
                                $infrastructure_status_id = $rowSanitationMaterialsById['CensusSanitation']['infrastructure_status_id'];
                                $cellValueCheckSource[$infrastructure_sanitation_id][$infrastructure_status_id] = $rowSanitationMaterialsById['CensusSanitation'];
                            }
                            //pr($cellValueCheckSource);

                            $arrayGender = array('Male', 'Female', 'Unisex');
                            foreach ($arrayGender AS $gender) {
                                $genderLowerCase = strtolower($gender);
                                $countByGender = $this->CensusSanitation->find('count', array(
                                    'conditions' => array(
                                        'CensusSanitation.institution_site_id' => $this->institutionSiteId,
                                        'CensusSanitation.school_year_id' => $yearId,
                                        'CensusSanitation.infrastructure_material_id' => $sanitationMaterialId,
                                        'CensusSanitation.' . $genderLowerCase . ' > ' => 0
                                    )
                                        )
                                );

                                if ($countByGender > 0) {
                                    $header = array(__('Year'), __('Infrastructure Name'), __('Infrastructure Type'), __('Gender'), __('Category'));
                                    foreach ($dataInfraStatuses AS $infraStatusName) {
                                        $header[] = __($infraStatusName);
                                    }
                                    $header[] = __('Total');
                                    $data[] = $header;

                                    $totalAll = 0;
                                    foreach ($dataInfraTypes AS $infraTypeId => $infraTypeName) {
                                        $csvRow = array(__($yearName), __($categoryName), __($sanitationMaterialName), __($gender), __($infraTypeName));
                                        $totalRow = 0;
                                        foreach ($dataInfraStatuses AS $infraStatusId => $infraStatusName) {
                                            if (isset($cellValueCheckSource[$infraTypeId][$infraStatusId][$genderLowerCase])) {
                                                $cellValue = !empty($cellValueCheckSource[$infraTypeId][$infraStatusId][$genderLowerCase]) ? $cellValueCheckSource[$infraTypeId][$infraStatusId][$genderLowerCase] : 0;
                                            } else {
                                                $cellValue = 0;
                                            }
                                            $csvRow[] = $cellValue;
                                            $totalRow += $cellValue;
                                        }
                                        $csvRow[] = $totalRow;
                                        $data[] = $csvRow;
                                        $totalAll += $totalRow;
                                    }
                                    $emptyColumns = $countStatuses + 4;
                                    $rowTotal = array();
                                    for ($i = 0; $i < $emptyColumns; $i++) {
                                        $rowTotal[] = '';
                                    }
                                    $rowTotal[] = __('Total');
                                    $rowTotal[] = $totalAll;
                                    $data[] = $rowTotal;
                                    $data[] = array();
                                }
                            }
                        }
                    }
                } else if ($categoryName == 'Buildings') {
                    $dataInfraTypes = $this->InfrastructureBuilding->find('list', array('conditions' => array('InfrastructureBuilding.visible' => 1)));
                    $dataBuildingMaterials = $this->CensusBuilding->find('all', array(
                        'recursive' => -1,
                        'fields' => array(
                            'CensusBuilding.infrastructure_material_id',
                            'InfrastructureMaterial.name'
                        ),
                        'joins' => array(
                            array(
                                'table' => 'infrastructure_materials',
                                'alias' => 'InfrastructureMaterial',
                                'conditions' => array(
                                    'CensusBuilding.infrastructure_material_id = InfrastructureMaterial.id'
                                )
                            )
                        ),
                        'conditions' => array(
                            'CensusBuilding.institution_site_id' => $this->institutionSiteId,
                            'CensusBuilding.school_year_id' => $yearId
                        ),
                        'group' => array('CensusBuilding.infrastructure_material_id'),
                        'order' => array('InfrastructureMaterial.order')
                            )
                    );

                    if (count($dataBuildingMaterials) > 0) {
                        foreach ($dataBuildingMaterials AS $RowBuildingMaterials) {
                            $buildingMaterialId = $RowBuildingMaterials['CensusBuilding']['infrastructure_material_id'];
                            $buildingMaterialName = $RowBuildingMaterials['InfrastructureMaterial']['name'];
                            $dataBuildingMaterialsById = $this->CensusBuilding->find('all', array(
                                'recursive' => -1,
                                'conditions' => array(
                                    'CensusBuilding.institution_site_id' => $this->institutionSiteId,
                                    'CensusBuilding.school_year_id' => $yearId,
                                    'CensusBuilding.infrastructure_material_id' => $buildingMaterialId
                                )
                                    )
                            );

                            $cellValueCheckSource = array();
                            foreach ($dataBuildingMaterialsById AS $rowBuildingMaterialsById) {
                                $infrastructure_building_id = $rowBuildingMaterialsById['CensusBuilding']['infrastructure_building_id'];
                                $infrastructure_status_id = $rowBuildingMaterialsById['CensusBuilding']['infrastructure_status_id'];
                                $cellValueCheckSource[$infrastructure_building_id][$infrastructure_status_id] = $rowBuildingMaterialsById['CensusBuilding'];
                            }
                            //pr($buildingCheckSource);

                            $header = array(__('Year'), __('Infrastructure Name'), __('Infrastructure Type'), __('Category'));
                            foreach ($dataInfraStatuses AS $infraStatusName) {
                                $header[] = __($infraStatusName);
                            }
                            $header[] = __('Total');
                            $data[] = $header;

                            $totalAll = 0;
                            foreach ($dataInfraTypes AS $infraTypeId => $infraTypeName) {
                                $csvRow = array(__($yearName), __($categoryName), __($buildingMaterialName), __($infraTypeName));
                                $totalRow = 0;
                                foreach ($dataInfraStatuses AS $infraStatusId => $infraStatusName) {
                                    if (isset($cellValueCheckSource[$infraTypeId][$infraStatusId]['value'])) {
                                        $cellValue = !empty($cellValueCheckSource[$infraTypeId][$infraStatusId]['value']) ? $cellValueCheckSource[$infraTypeId][$infraStatusId]['value'] : 0;
                                    } else {
                                        $cellValue = 0;
                                    }
                                    $csvRow[] = $cellValue;
                                    $totalRow += $cellValue;
                                }
                                $csvRow[] = $totalRow;
                                $data[] = $csvRow;
                                $totalAll += $totalRow;
                            }
                            $emptyColumns = $countStatuses + 3;
                            $rowTotal = array();
                            for ($i = 0; $i < $emptyColumns; $i++) {
                                $rowTotal[] = '';
                            }
                            $rowTotal[] = __('Total');
                            $rowTotal[] = $totalAll;
                            $data[] = $rowTotal;
                            $data[] = array();
                        }
                    }
                } else {
                    $censusModel = $this->reportCensusInfraMapping[$categoryName]['censusModel'];
                    $typesModel = $this->reportCensusInfraMapping[$categoryName]['typesModel'];
                    $typeForeignKey = $this->reportCensusInfraMapping[$categoryName]['typeForeignKey'];
                    $dataInfraTypes = $this->{$typesModel}->find('list', array('conditions' => array('visible' => 1)));
                    $dataCensus = $this->{$censusModel}->find('all', array(
                        'recursive' => -1,
                        'conditions' => array(
                            'institution_site_id' => $this->institutionSiteId,
                            'school_year_id' => $yearId
                        )
                            )
                    );

                    if (count($dataCensus) > 0) {
                        $cellValueCheckSource = array();
                        foreach ($dataCensus AS $rowCensus) {
                            $infrastructure_type_id = $rowCensus[$censusModel][$typeForeignKey];
                            $infrastructure_status_id = $rowCensus[$censusModel]['infrastructure_status_id'];
                            $cellValueCheckSource[$infrastructure_type_id][$infrastructure_status_id] = $rowCensus[$censusModel];
                        }
                        //pr($cellValueCheckSource);

                        $header = $headerCommon;
                        foreach ($dataInfraStatuses AS $infraStatusName) {
                            $header[] = __($infraStatusName);
                        }
                        $header[] = __('Total');
                        $data[] = $header;

                        $totalAll = 0;
                        foreach ($dataInfraTypes AS $infraTypeId => $infraTypeName) {
                            $csvRow = array(__($yearName), __($categoryName), __($infraTypeName));
                            $totalRow = 0;
                            foreach ($dataInfraStatuses AS $infraStatusId => $infraStatusName) {
                                if (isset($cellValueCheckSource[$infraTypeId][$infraStatusId]['value'])) {
                                    $cellValue = !empty($cellValueCheckSource[$infraTypeId][$infraStatusId]['value']) ? $cellValueCheckSource[$infraTypeId][$infraStatusId]['value'] : 0;
                                } else {
                                    $cellValue = 0;
                                }
                                $csvRow[] = $cellValue;
                                $totalRow += $cellValue;
                            }
                            $csvRow[] = $totalRow;
                            $data[] = $csvRow;
                            $totalAll += $totalRow;
                        }
                        $emptyColumns = $countStatuses + 2;
                        $rowTotal = array();
                        for ($i = 0; $i < $emptyColumns; $i++) {
                            $rowTotal[] = '';
                        }
                        $rowTotal[] = __('Total');
                        $rowTotal[] = $totalAll;
                        $data[] = $rowTotal;
                        $data[] = array();
                    }
                }
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusGraduates() {
        $data = array();
        $header = array(__('Year'), __('Education Level'), __('Education Programme'), __('Certification'), __('Male'), __('Female'), __('Total'));

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $dataCensus = $this->CensusGraduate->getCensusData($this->institutionSiteId, $yearId);

            if (count($dataCensus) > 0) {
                foreach ($dataCensus AS $levelName => $dataByLevel) {
                    $data[] = $header;
                    foreach ($dataByLevel AS $rowCensus) {
                        $programme = $rowCensus['education_programme_name'];
                        $certificationName = $rowCensus['education_certification_name'];
                        $male = empty($rowCensus['male']) ? 0 : $rowCensus['male'];
                        $female = empty($rowCensus['female']) ? 0 : $rowCensus['female'];
                        $total = $male + $female;

                        $data[] = array(
                            $yearName,
                            $levelName,
                            $programme,
                            $certificationName,
                            $male,
                            $female,
                            $total
                        );
                    }
                    $data[] = array();
                }
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusTextbooks() {
        $data = array();
        $header = array(__('Year'), __('Programme'), __('Grade'), __('Subject'), __('Total'));

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $dataCensus = $this->CensusTextbook->getCensusData($this->institutionSiteId, $yearId);

            if (count($dataCensus) > 0) {
                foreach ($dataCensus AS $programmeName => $dataByProgramme) {
                    $data[] = $header;
                    $totalByProgramme = 0;
                    foreach ($dataByProgramme AS $rowCensus) {
                        $gradeName = $rowCensus['education_grade_name'];
                        $subjectName = $rowCensus['education_subject_name'];
                        $total = $rowCensus['total'];

                        $data[] = array(
                            $yearName,
                            $programmeName,
                            $gradeName,
                            $subjectName,
                            $total
                        );

                        $totalByProgramme += $total;
                    }
                    $data[] = array('', '', '', __('Total'), $totalByProgramme);
                    $data[] = array();
                }
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusResults() {
        $data = array();
        $header = array(__('Year'), __('Programme'), __('Grade'), __('Subject'), __('Score'));

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $dataCensus = $this->CensusAssessment->getCensusData($this->institutionSiteId, $yearId);

            if (count($dataCensus) > 0) {
                foreach ($dataCensus AS $programmeName => $dataByProgramme) {
                    $data[] = $header;
                    foreach ($dataByProgramme AS $rowCensus) {
                        $gradeName = $rowCensus['education_grade_name'];
                        $subjectName = $rowCensus['education_subject_name'];
                        $score = $rowCensus['total'];

                        $data[] = array(
                            $yearName,
                            $programmeName,
                            $gradeName,
                            $subjectName,
                            $score
                        );
                    }
                    $data[] = array();
                }
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusAttendance() {
        $data = array();
        $header = array(__('Year'), __('School Days'), __('Programme'), __('Grade'), __('Days Attended (Male)'), __('Days Attended (Female)'), __('Days Absent (Male)'), __('Days Absent (Female)'), __('Total'));

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $programmes = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $yearId);
            $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

            if (count($programmes) > 0) {
                foreach ($programmes as $obj) {
                    $data[] = $header;
                    $programmeId = $obj['education_programme_id'];
                    $dataCensus = $this->CensusAttendance->getCensusData($this->institutionSiteId, $yearId, $programmeId);
                    $programmeName = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
                    $total = 0;
                    foreach ($dataCensus AS $rowCensus) {
                        $gradeName = $rowCensus['education_grade_name'];
                        $attendedMale = empty($rowCensus['attended_male']) ? 0 : $rowCensus['attended_male'];
                        $attendedFemale = empty($rowCensus['attended_female']) ? 0 : $rowCensus['attended_female'];
                        $absentMale = empty($rowCensus['absent_male']) ? 0 : $rowCensus['absent_male'];
                        $absentFemale = empty($rowCensus['absent_female']) ? 0 : $rowCensus['absent_female'];
                        $totalRow = $attendedMale + $attendedFemale + $absentMale + $absentFemale;
                        $data[] = array(
                            $yearName,
                            $schoolDays,
                            $programmeName,
                            $gradeName,
                            $attendedMale,
                            $attendedFemale,
                            $absentMale,
                            $absentFemale,
                            $totalRow
                        );

                        $total += $totalRow;
                    }
                    $data[] = array('', '', '', '', '', '', '', __('Total'), $total);
                    $data[] = array();
                }
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusBehaviour() {
        $data = array();

        $header = array(__('Year'), __('Category'), __('Male'), __('Female'), __('Total'));

        $dataYears = $this->CensusBehaviour->getYearsHaveData($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $dataBehaviour = $this->CensusBehaviour->getCensusData($this->institutionSiteId, $yearId);

            if (count($dataBehaviour) > 0) {
                $data[] = $header;
                $total = 0;
                foreach ($dataBehaviour AS $row) {
                    $male = empty($row['male']) ? 0 : $row['male'];
                    $female = empty($row['female']) ? 0 : $row['female'];

                    $data[] = array(
                        $yearName,
                        $row['name'],
                        $male,
                        $female,
                        $male + $female
                    );

                    $total += $male;
                    $total += $female;
                }

                $data[] = array('', '', '', __('Total'), $total);
                $data[] = array();
            }
        }

        //pr($data);
        return $data;
    }

    private function getReportDataCensusFinances() {
        $data = array();

        $header = array(__('Year'), __('Nature'), __('Type'), __('Source'), __('Category'), __('Description'), __('Amount (PM)'));

        $dataYears = $this->CensusFinance->getYearsHaveData($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $dataFinances = $this->CensusFinance->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $this->institutionSiteId, 'CensusFinance.school_year_id' => $yearId)));
            $newSort = array();
            foreach ($dataFinances as $k => $arrv) {
                $newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
            }

            if (count($newSort) > 0) {
                foreach ($newSort as $nature => $dataNature) {
                    foreach ($dataNature as $type => $dataType) {
                        $totalByType = 0;
                        $data[] = $header;
                        foreach ($dataType as $arrValues) {
                            $financeNature = $nature;
                            $financeType = $type;
                            $financeSource = $arrValues['FinanceSource']['name'];
                            $financeCategory = $arrValues['FinanceCategory']['name'];
                            $financeDescription = $arrValues['CensusFinance']['description'];
                            $financeAmount = $arrValues['CensusFinance']['amount'];

                            $data[] = array(
                                $yearName,
                                $financeNature,
                                $financeType,
                                $financeSource,
                                $financeCategory,
                                $financeDescription,
                                $financeAmount
                            );

                            $totalByType += $financeAmount;
                        }
                        $data[] = array('', '', '', '', '', __('Total'), $totalByType);
                        $data[] = array();
                    }
                }
            }
        }

        //pr($data);
        return $data;
    }

    private function getReportDataCensusShifts() {
        $data = array();
        $header = array(__('Year'), __('Class Type'), __('Programme'), __('Grade'), __('Classes'));
        $no_of_shifts = $this->ConfigItem->getValue('no_of_shifts');
        for ($i = 1; $i <= intval($no_of_shifts); $i++) {
            $header[] = __('Shift') . $i;
        }

        $header[] = __('Total');

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $singleGradeClasses = $this->CensusShift->getData($this->institutionSiteId, $yearId);
            $singleGradeData = $this->CensusClass->getSingleGradeData($this->institutionSiteId, $yearId);
            $multiGradeData = $this->CensusClass->getMultiGradeData($this->institutionSiteId, $yearId);

            $this->CensusShift->mergeSingleGradeData($singleGradeData, $singleGradeClasses);
            $this->CensusShift->mergeMultiGradeData($multiGradeData, $singleGradeClasses);

            // single grade classes data start
            if (count($singleGradeData) > 0) {
                $data[] = $header;
                $totalClasses = 0;
                foreach ($singleGradeData AS $rowSingleGrade) {
                    $preDataRow = array(
                        $yearName,
                        __('Single Grade Classes Only'),
                        $rowSingleGrade['education_programme_name'],
                        $rowSingleGrade['education_grade_name'],
                        $rowSingleGrade['classes']
                    );

                    $totalShifts = 0;
                    for ($i = 1; $i <= intval($no_of_shifts); $i++) {
                        $shift = 0;
                        if (isset($rowSingleGrade['shift_' . $i])) {
                            $shift = $rowSingleGrade['shift_' . $i];
                            $totalShifts += $shift;
                        }
                        $preDataRow[] = $shift;
                    }
                    $preDataRow[] = $totalShifts;

                    $data[] = $preDataRow;
                    $totalClasses += $rowSingleGrade['classes'];
                }
                $data[] = array('', '', '', 'Total', $totalClasses);
                $data[] = array();
            }
            // single grade classes data end
            // multi grades classes data start
            if (count($multiGradeData) > 0) {
                $data[] = $header;
                $totalClasses = 0;
                foreach ($multiGradeData AS $rowMultiGrade) {
                    $multiProgrammes = '';
                    $multiProgrammeCount = 0;
                    foreach ($rowMultiGrade['programmes'] AS $multiProgramme) {
                        if ($multiProgrammeCount > 0) {
                            $multiProgrammes .= "\n\r";
                            $multiProgrammes .= $multiProgramme;
                        } else {
                            $multiProgrammes .= $multiProgramme;
                        }
                        $multiProgrammeCount ++;
                    }

                    $multiGrades = '';
                    $multiGradeCount = 0;
                    foreach ($rowMultiGrade['grades'] AS $multiGrade) {
                        if ($multiGradeCount > 0) {
                            $multiGrades .= "\n\r";
                            $multiGrades .= $multiGrade;
                        } else {
                            $multiGrades .= $multiGrade;
                        }
                        $multiGradeCount ++;
                    }

                    $preDataRow = array(
                        $yearName,
                        __('Multi Grade Classes'),
                        $multiProgrammes,
                        $multiGrades,
                        $rowMultiGrade['classes']
                    );

                    $totalShifts = 0;
                    for ($i = 1; $i <= intval($no_of_shifts); $i++) {
                        $shift = 0;
                        if (isset($rowMultiGrade['shift_' . $i])) {
                            $shift = $rowMultiGrade['shift_' . $i];
                            $totalShifts += $shift;
                        }
                        $preDataRow[] = $shift;
                    }
                    $preDataRow[] = $totalShifts;

                    $data[] = $preDataRow;
                    $totalClasses += $rowMultiGrade['classes'];
                }
                $data[] = array('', '', '', __('Total'), $totalClasses);
                $data[] = array();
            }
            // multi grades classes data end
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusClasses() {
        $data = array();
        $header = array(__('Year'), __('Class Type'), __('Programme'), __('Grade'), __('Classes'), __('Seats'));

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            // single grade classes data start
            $programmeGrades = $this->InstitutionSiteProgramme->getProgrammeList($this->institutionSiteId, $yearId);
            $singleGradeClasses = $this->CensusClass->getSingleGradeData($this->institutionSiteId, $yearId);
            $singleGradeData = $programmeGrades;
            $this->CensusClass->mergeSingleGradeData($singleGradeData, $singleGradeClasses);

            if (count($singleGradeData) > 0) {
                $data[] = $header;
                $totalClassesSingleGrade = 0;
                $totalSeatsSingleGrade = 0;
                foreach ($singleGradeData AS $programmeName => $programmeData) {
                    foreach ($programmeData['education_grades'] AS $gradeId => $gradeData) {
                        $classesSingleGrade = empty($gradeData['classes']) ? 0 : $gradeData['classes'];
                        $seatsSingleGrade = empty($gradeData['seats']) ? 0 : $gradeData['seats'];

                        $data[] = array(
                            $yearName,
                            __('Single Grade Classes Only'),
                            $programmeName,
                            $gradeData['name'],
                            $gradeData['classes'],
                            $gradeData['seats']
                        );

                        $totalClassesSingleGrade += $classesSingleGrade;
                        $totalSeatsSingleGrade += $seatsSingleGrade;
                    }
                }

                $data[] = array('', '', '', 'Total', $totalClassesSingleGrade, $totalSeatsSingleGrade);
                $data[] = array();
            }
            // single grade classes data end
            // multi grades classes data start
            $multiGradesData = $this->CensusClass->getMultiGradeData($this->institutionSiteId, $yearId);

            if (count($multiGradesData) > 0) {
                $data[] = $header;
                $totalClassesMultiGrades = 0;
                $totalSeatsMultiGrades = 0;
                foreach ($multiGradesData AS $rowMultiGrades) {
                    $classesMultiGrades = empty($rowMultiGrades['classes']) ? 0 : $rowMultiGrades['classes'];
                    $seatsMultiGrades = empty($rowMultiGrades['seats']) ? 0 : $rowMultiGrades['seats'];
                    $multiProgrammes = '';
                    $multiProgrammeCount = 0;
                    foreach ($rowMultiGrades['programmes'] AS $multiProgramme) {
                        if ($multiProgrammeCount > 0) {
                            $multiProgrammes .= "\n\r";
                            $multiProgrammes .= $multiProgramme;
                        } else {
                            $multiProgrammes .= $multiProgramme;
                        }
                        $multiProgrammeCount ++;
                    }

                    $multiGrades = '';
                    $multiGradeCount = 0;
                    foreach ($rowMultiGrades['grades'] AS $multiGrade) {
                        if ($multiGradeCount > 0) {
                            $multiGrades .= "\n\r";
                            $multiGrades .= $multiGrade;
                        } else {
                            $multiGrades .= $multiGrade;
                        }
                        $multiGradeCount ++;
                    }

                    $data[] = array(
                        $yearName,
                        __('Multi Grade Classes'),
                        $multiProgrammes,
                        $multiGrades,
                        $classesMultiGrades,
                        $seatsMultiGrades
                    );

                    $totalClassesMultiGrades += $classesMultiGrades;
                    $totalSeatsMultiGrades += $seatsMultiGrades;
                }

                $data[] = array('', '', '', __('Total'), $totalClassesMultiGrades, $totalSeatsMultiGrades);
                $data[] = array();
            }
            // multi grades classes data end
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusStaff() {
        $data = array();

        $header = array(__('Year'), __('Position Type'), __('Male'), __('Female'), __('Total'));

        $dataYears = $this->CensusStaff->getYearsHaveData($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            $censusData = $this->CensusStaff->getCensusData($this->institutionSiteId, $yearId);
            if (count($censusData) > 0) {
                $data[] = $header;
                $total = 0;
                foreach ($censusData AS $row) {
                    if ($row['staff_category_visible'] == 1) {
                        $male = empty($row['male']) ? 0 : $row['male'];
                        $female = empty($row['female']) ? 0 : $row['female'];

                        $data[] = array(
                            $yearName,
                            $row['staff_category_name'],
                            $male,
                            $female,
                            $male + $female
                        );

                        $total += $male;
                        $total += $female;
                    }
                }

                $data[] = array('', '', '', __('Total'), $total);
                $data[] = array();
            }
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusTeachers() {
        $data = array();

        $headerFTE = array(__('Year'), __('Teacher Type'), __('Education Level'), __('Male'), __('Female'), __('Total'));
        $headerTraining = $headerFTE;
        $headerSingleGrade = array(__('Year'), __('Teacher Type'), __('Programme'), __('Grade'), __('Male'), __('Female'));
        $headerMultiGrade = $headerSingleGrade;

        $dataYears = $this->InstitutionSiteProgramme->getYearsHaveProgrammes($this->institutionSiteId);

        foreach ($dataYears AS $rowYear) {
            $yearId = $rowYear['SchoolYear']['id'];
            $yearName = $rowYear['SchoolYear']['name'];

            // FTE teachers data start
            $dataFTE = $this->CensusTeacherFte->getCensusData($this->institutionSiteId, $yearId);
            if (count($dataFTE) > 0) {
                $data[] = $headerFTE;
                $totalFTE = 0;
                foreach ($dataFTE AS $rowFTE) {
                    $maleFTE = empty($rowFTE['male']) ? 0 : $rowFTE['male'];
                    $femaleFTE = empty($rowFTE['female']) ? 0 : $rowFTE['female'];

                    $data[] = array(
                        $yearName,
                        'Full Time Equivalent Teachers',
                        $rowFTE['education_level_name'],
                        $maleFTE,
                        $femaleFTE,
                        $maleFTE + $femaleFTE
                    );

                    $totalFTE += $maleFTE;
                    $totalFTE += $femaleFTE;
                }

                $data[] = array('', '', '', '', __('Total'), $totalFTE);
                $data[] = array();
            }
            // FTE teachers data end
            // trained teachers data start
            $dataTraining = $this->CensusTeacherTraining->getCensusData($this->institutionSiteId, $yearId);
            if (count($dataTraining) > 0) {
                $data[] = $headerTraining;
                $totalTraining = 0;
                foreach ($dataTraining AS $rowTraining) {
                    $maleTraining = empty($rowTraining['male']) ? 0 : $rowTraining['male'];
                    $femaleTraining = empty($rowTraining['female']) ? 0 : $rowTraining['female'];

                    $data[] = array(
                        $yearName,
                        'Trained Teachers',
                        $rowTraining['education_level_name'],
                        $maleTraining,
                        $femaleTraining,
                        $maleTraining + $femaleTraining
                    );

                    $totalTraining += $maleTraining;
                    $totalTraining += $femaleTraining;
                }

                $data[] = array('', '', '', '', __('Total'), $totalTraining);
                $data[] = array();
            }
            // trained teachers data end
            // single grade teachers data start
            $programmeGrades = $this->InstitutionSiteProgramme->getProgrammeList($this->institutionSiteId, $yearId);
            $singleGradeData = $programmeGrades;
            $singleGradeTeachers = $this->CensusTeacher->getSingleGradeData($this->institutionSiteId, $yearId);
            $this->CensusTeacher->mergeSingleGradeData($singleGradeData, $singleGradeTeachers);

            if (count($singleGradeData) > 0) {
                $data[] = $headerSingleGrade;
                $totalMaleSingleGrade = 0;
                $totalFemaleSingleGrade = 0;
                foreach ($singleGradeData AS $programmeName => $programmeData) {
                    foreach ($programmeData['education_grades'] AS $gradeId => $gradeData) {
                        $maleSingleGrade = empty($gradeData['male']) ? 0 : $gradeData['male'];
                        $femaleSingleGrade = empty($gradeData['female']) ? 0 : $gradeData['female'];

                        $data[] = array(
                            $yearName,
                            'Single Grade Teachers Only',
                            $programmeName,
                            $gradeData['name'],
                            $gradeData['male'],
                            $gradeData['female']
                        );

                        $totalMaleSingleGrade += $maleSingleGrade;
                        $totalFemaleSingleGrade += $femaleSingleGrade;
                    }
                }

                $data[] = array('', '', '', __('Total'), $totalMaleSingleGrade, $totalFemaleSingleGrade);
                $data[] = array();
            }
            // single grade teachers data end
            // multi grades teachers data start
            $multiGradesData = $this->CensusTeacher->getMultiGradeData($this->institutionSiteId, $yearId);

            if (count($multiGradesData) > 0) {
                $data[] = $headerMultiGrade;
                $totalMaleMultiGrades = 0;
                $totalFemaleMultiGrades = 0;
                foreach ($multiGradesData AS $rowMultiGrades) {
                    $maleMultiGrades = empty($rowMultiGrades['male']) ? 0 : $rowMultiGrades['male'];
                    $femaleMultiGrades = empty($rowMultiGrades['female']) ? 0 : $rowMultiGrades['female'];
                    $multiProgrammes = '';
                    $multiProgrammeCount = 0;
                    foreach ($rowMultiGrades['programmes'] AS $multiProgramme) {
                        if ($multiProgrammeCount > 0) {
                            $multiProgrammes .= "\n\r";
                            $multiProgrammes .= $multiProgramme;
                        } else {
                            $multiProgrammes .= $multiProgramme;
                        }
                        $multiProgrammeCount ++;
                    }

                    $multiGrades = '';
                    $multiGradeCount = 0;
                    foreach ($rowMultiGrades['grades'] AS $multiGrade) {
                        if ($multiGradeCount > 0) {
                            $multiGrades .= "\n\r";
                            $multiGrades .= $multiGrade;
                        } else {
                            $multiGrades .= $multiGrade;
                        }
                        $multiGradeCount ++;
                    }

                    $data[] = array(
                        $yearName,
                        'Multi Grade Teachers',
                        $multiProgrammes,
                        $multiGrades,
                        $maleMultiGrades,
                        $femaleMultiGrades
                    );

                    $totalMaleMultiGrades += $maleMultiGrades;
                    $totalFemaleMultiGrades += $femaleMultiGrades;
                }

                $data[] = array('', '', '', __('Total'), $totalMaleMultiGrades, $totalFemaleMultiGrades);
                $data[] = array();
            }
            // multi grades teachers data end
        }
        //pr($data);
        return $data;
    }

    private function getReportDataCensusStudents() {
        $data = array();
        //$header = array('Age', 'Male', 'Female', __('Total'));
        $header = array(__('Year'), __('Programme'), __('Grade'), __('Category'), __('Age'), __('Male'), __('Female'), __('Total'));

        $baseData = $this->CensusStudent->groupByYearGradeCategory($this->institutionSiteId);

        foreach ($baseData AS $row) {
            $year = $row['SchoolYear']['name'];
            $educationCycle = $row['EducationCycle']['name'];
            $educationProgramme = $row['EducationProgramme']['name'];
            $educationGrade = $row['EducationGrade']['name'];
            $studentCategory = $row['StudentCategory']['name'];

            $data[] = $header;

            $censusData = $this->CensusStudent->find('all', array(
                'recursive' => -1,
                'fields' => array(
                    'CensusStudent.age',
                    'CensusStudent.male',
                    'CensusStudent.female'
                ),
                'conditions' => array(
                    'CensusStudent.institution_site_id' => $this->institutionSiteId,
                    'CensusStudent.school_year_id' => $row['CensusStudent']['school_year_id'],
                    'CensusStudent.education_grade_id' => $row['CensusStudent']['education_grade_id'],
                    'CensusStudent.student_category_id' => $row['CensusStudent']['student_category_id']
                ),
                'group' => array('CensusStudent.age')
                    )
            );

            $total = 0;
            foreach ($censusData AS $censusRow) {
                $data[] = array(
                    $year,
                    $educationCycle . ' - ' . $educationProgramme,
                    $educationGrade,
                    $studentCategory,
                    $censusRow['CensusStudent']['age'],
                    $censusRow['CensusStudent']['male'],
                    $censusRow['CensusStudent']['female'],
                    $censusRow['CensusStudent']['male'] + $censusRow['CensusStudent']['female']
                );

                $total += $censusRow['CensusStudent']['male'];
                $total += $censusRow['CensusStudent']['female'];
            }

            $data[] = array('', '', '', '', '', '', __('Total'), $total);
            $data[] = array();
        }

        return $data;
    }

    private function getFields($name) {
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
        return $new;
    }

    private function getHeader($name, $humanize = false) {
        if (array_key_exists($name, $this->reportMapping)) {
            if ($name == 'QA Report') {
                $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
                $header = $RubricsTemplate->getInstitutionQAReportHeader($this->institutionSiteId);
                //   return $header;
            } else {
                $header = $this->reportMapping[$name]['fields'];
            }
        }
        $new = array();
        foreach ($header as $model => &$arrcols) {
            foreach ($arrcols as $col => $value) {
                if (empty($value)) {
                    $new[] = __(Inflector::humanize(Inflector::underscore($model))) . ' ' . __(Inflector::humanize($col));
                } else {
                    $new[] = __($value);
                }
            }
        }
        return $new;
    }

    private function formatCSVData($data, $name) {
        $newData = array();
        $dateFormat = 'd F, Y';

        if ($name == 'Overview') {
            foreach ($data AS $row) {
                if ($row['InstitutionSite']['date_opened'] == '0000-00-00') {
                    $row['InstitutionSite']['date_opened'] = '';
                } else {
                    $originalDate = new DateTime($row['InstitutionSite']['date_opened']);
                    $row['InstitutionSite']['date_opened'] = $originalDate->format($dateFormat);
                }

                if ($row['InstitutionSite']['date_closed'] == '0000-00-00') {
                    $row['InstitutionSite']['date_closed'] = '';
                } else {
                    $originalDate = new DateTime($row['InstitutionSite']['date_closed']);
                    $row['InstitutionSite']['date_closed'] = $originalDate->format($dateFormat);
                }

                $newData[] = $row;
            }
        } else if ($name == 'Bank Accounts') {
            foreach ($data AS $row) {
                $row['InstitutionSiteBankAccount']['active'] = $row['InstitutionSiteBankAccount']['active'] == 1 ? 'Yes' : 'No';
                $newData[] = $row;
            }
        } else if ($name == 'More') {
            $tempArray = array();
            foreach ($data AS $row) {
                if (array_key_exists($row['InstitutionSiteCustomField']['name'], $tempArray)) {
                    if (empty($tempArray[$row['InstitutionSiteCustomField']['name']])) {
                        $tempArray[$row['InstitutionSiteCustomField']['name']] = $row['InstitutionSiteCustomValue']['custom_value'];
                    } else {
                        if (!empty($row['InstitutionSiteCustomValue']['custom_value'])) {
                            $tempArray[$row['InstitutionSiteCustomField']['name']] .= ' ,' . $row['InstitutionSiteCustomValue']['custom_value'];
                        }
                    }
                } else {
                    $tempArray[$row['InstitutionSiteCustomField']['name']] = $row['InstitutionSiteCustomValue']['custom_value'];
                }
            }

            foreach ($tempArray AS $key => $value) {
                $newData[] = array(
                    'InstitutionSiteCustomField' => array('name' => $key),
                    'InstitutionSiteCustomValue' => array('custom_value' => $value)
                );
            }
        } else if ($name == 'Student Attendance') {
            foreach ($data AS $row) {
                $row['StudentAttendance']['total_no_attend'] = $row['StudentAttendance']['total_no_attend'] == NULL ? 0 : $row['StudentAttendance']['total_no_attend'];
                $row['StudentAttendance']['total_no_absence'] = $row['StudentAttendance']['total_no_absence'] == NULL ? 0 : $row['StudentAttendance']['total_no_absence'];
                $row['InstitutionSiteClassGradeStudent']['total'] = $row['InstitutionSiteClassGradeStudent']['total'] == NULL ? 0 : $row['InstitutionSiteClassGradeStudent']['total'];
                $newData[] = $row;
            }
        } else if ($name == 'Student Behaviour') {
            foreach ($data AS $row) {
                $row['StudentBehaviour']['date_of_behaviour'] = $this->DateTime->formatDateByConfig($row['StudentBehaviour']['date_of_behaviour']);
                $newData[] = $row;
            }
        } else if ($name == 'Teacher List') {
            foreach ($data AS $row) {
                $row['Teacher']['gender'] = $this->Utility->formatGender($row['Teacher']['gender']);
                $row['Teacher']['date_of_birth'] = $this->DateTime->formatDateByConfig($row['Teacher']['date_of_birth']);
                $newData[] = $row;
            }
        } else if ($name == 'Teacher Behaviour') {
            foreach ($data AS $row) {
                $row['TeacherBehaviour']['date_of_behaviour'] = $this->DateTime->formatDateByConfig($row['TeacherBehaviour']['date_of_behaviour']);
                $newData[] = $row;
            }
        } else if ($name == 'Staff List') {
            foreach ($data AS $row) {
                $row['Staff']['gender'] = $this->Utility->formatGender($row['Staff']['gender']);
                $row['Staff']['date_of_birth'] = $this->DateTime->formatDateByConfig($row['Staff']['date_of_birth']);
                $newData[] = $row;
            }
        } else if ($name == 'Staff Behaviour') {
            foreach ($data AS $row) {
                $row['StaffBehaviour']['date_of_behaviour'] = $this->DateTime->formatDateByConfig($row['StaffBehaviour']['date_of_behaviour']);
                $newData[] = $row;
            }
        } else if ($name == 'QA Report') {
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
        //pr($arrData);

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
         
        $header_row = $this->getHeader($name);
      //  pr($header_row);
           fputcsv($csv_file, $header_row, ',', '"');

          // Each iteration of this while loop will be a row in your .csv file where each field corresponds to the heading of the column
          foreach ($arrData as $arrSingleResult) {
          $row = array();
          foreach ($arrSingleResult as $table => $arrFields) {

          foreach ($arrFields as $col) {
          $row[] = $col;
          }
          }

          fputcsv($csv_file, $row, ',', '"');
          }

          fclose($csv_file); 
    }

    private function genCSVAcademic($data, $name) {
        $this->autoRender = false;

        if (array_key_exists($name, $this->reportMappingAcademic)) {
            $fileName = array_key_exists('FileName', $this->reportMappingAcademic[$name]) ? $this->reportMappingAcademic[$name]['FileName'] : "export_" . date("Y.m.d");
        } else {
            $fileName = "export_" . date("Y.m.d");
        }
        $downloadedFile = $fileName . '.csv';

        ini_set('max_execution_time', 600); //increase max_execution_time to 10 min if data set is very large
        //create a file

        $csv_file = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');

        $header_row = $this->getHeaderAcademic($name);
        fputcsv($csv_file, $header_row, ',', '"');

        // Each iteration of this while loop will be a row in your .csv file where each field corresponds to the heading of the column
        foreach ($data as $row) {
            fputcsv($csv_file, $row, ',', '"');
        }

        fclose($csv_file);
    }

    private function genCSVCensus($data, $name) {
        $this->autoRender = false;

        if (array_key_exists($name, $this->reportMappingCensus)) {
            $fileName = array_key_exists('FileName', $this->reportMappingCensus[$name]) ? $this->reportMappingCensus[$name]['FileName'] : "export_" . date("Y.m.d");
        } else {
            $fileName = "export_" . date("Y.m.d");
        }
        $downloadedFile = $fileName . '.csv';

        ini_set('max_execution_time', 600); //increase max_execution_time to 10 min if data set is very large
        //create a file

        $csv_file = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');

        // Each iteration of this while loop will be a row in your .csv file where each field corresponds to the heading of the column
        foreach ($data as $row) {
            fputcsv($csv_file, $row, ',', '"');
        }

        fclose($csv_file);
    }

    public function genReportPDF($name) {
        if ($name == 'Overview') {
            $profileurl = Router::url(array('controller' => 'InstitutionSites', 'action' => 'siteProfile', $this->institutionSiteId), true);
            $html = file_get_contents($profileurl);
            $html = str_replace('common.css', '', $html);
            $stylesheet = file_get_contents(WWW_ROOT . 'css/mpdf.css');
            $data = compact('html', 'stylesheet');
        }
        return $data;
    }

    public function genPDF($arrData) {
        // initializing mPDF
        $this->Mpdf->init();
        $this->Mpdf->showImageErrors = false; //for debugging
        $this->Mpdf->WriteHTML($arrData['stylesheet'], 1);
        $this->Mpdf->WriteHTML($arrData['html']);
        $this->Mpdf->Output($arrData['name'] . '.pdf', 'I');
    }

    public function reportsGeneral() {
        $this->Navigation->addCrumb('Reports - General');
        $data = array('Reports - General' => array(
                array('name' => 'Overview', 'types' => array('CSV')),
                array('name' => 'Bank Accounts', 'types' => array('CSV')),
                array('name' => 'More', 'types' => array('CSV'))
        ));
        $this->set('data', $data);
        $this->set('actionName', 'genReport');
        $this->render('Reports/general');
    }

    public function reportsDetails() {
        $this->Navigation->addCrumb('Reports - Details');
        $data = array(
            'Reports - Details' => array(
                array(
                    'name' => 'Programme List',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Student List',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Student Result',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Student Attendance',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Student Behaviour',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Student Academic',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Teacher List',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Teacher Attendance',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Teacher Behaviour',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Teacher Academic',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Staff List',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Staff Attendance',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Staff Behaviour',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Staff Academic',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Class List',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Classes - Students',
                    'types' => array('CSV')
                )
            )
        );
        $this->set('data', $data);
        $this->set('actionName', 'genReport');
        $this->render('Reports/general');
    }

    public function reportsTotals() {
        $this->Navigation->addCrumb('Reports - Totals');
        $data = array(
            'Reports - Totals' => array(
                array(
                    'name' => 'Students',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Teachers',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Staff',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Classes',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Shifts',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Graduates',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Attendance',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Results',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Behaviour',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Textbooks',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Infrastructure',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'Finances',
                    'types' => array('CSV')
                ),
                array(
                    'name' => 'More',
                    'types' => array('CSV')
                ),
            )
        );
        $this->set('data', $data);
        $this->set('actionName', 'genReportCensus');
        $this->render('Reports/general');
    }

    public function reportsQuality() {
        $this->Navigation->addCrumb('Reports - Quality');
        $data = array('Reports - Quality' => array(
                array('name' => 'QA Report', 'types' => array('CSV')),
                array('name' => 'Visit Report', 'types' => array('CSV'))/* ,
              array('name' => 'More', 'types' => array('CSV')) */
        ));
        $this->set('data', $data);
        $this->set('actionName', 'genReport');
        $this->render('Reports/general');
    }

}
