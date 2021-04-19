<?php
namespace Report\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\ORM\ResultSet;
use PHPExcel_IOFactory;

class ReportsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->models = [
            'Directory'  => ['className' => 'Report.Directory', 'actions' => ['index', 'add']],
            'Institutions'	=> ['className' => 'Report.Institutions', 'actions' => ['index', 'add']],
            'Profiles'	=> ['className' => 'Report.Profiles', 'actions' => ['index', 'add']],
            'Students'	 	=> ['className' => 'Report.Students', 'actions' => ['index', 'add']],
            'Staff'	 		=> ['className' => 'Report.Staff', 'actions' => ['index', 'add']],
            'Textbooks'     => ['className' => 'Report.Textbooks', 'actions' => ['index', 'add']],
            'Trainings' 	=> ['className' => 'Report.Trainings', 'actions' => ['index', 'add']],
            'Examinations'	=> ['className' => 'Report.Examinations', 'actions' => ['index', 'add']],
            'Scholarships'  => ['className' => 'Report.Scholarships', 'actions' => ['index', 'add']],
            'Surveys'	 	=> ['className' => 'Report.Surveys', 'actions' => ['index', 'add']],
            'InstitutionRubrics' => ['className' => 'Report.InstitutionRubrics', 'actions' => ['index', 'add']],
            'DataQuality' => ['className' => 'Report.DataQuality', 'actions' => ['index', 'add']],
            'Audits' => ['className' => 'Report.Audits', 'actions' => ['index', 'add']],
            'Workflows' => ['className' => 'Report.Workflows', 'actions' => ['index', 'add']],
            'CustomReports' => ['className' => 'Report.CustomReports', 'actions' => ['index', 'add']]
        ];
        $this->loadComponent('Paginator');
        $this->loadComponent('Training.Training');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'Reports';
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
        $this->Navigation->addCrumb($this->request->action);
    }

    public function onInitialize(Event $event, Table $table, ArrayObject $extra)
    {
        $header = __('Reports') . ' - ' . __($table->alias());
        $this->set('contentHeader', $header);
    }

    public function getInstitutionStatusOptions($module)
    {
        $options = [
                'Active' => __('Active'),
                'Inactive' => __('Inactive')
            ];
            return $options;
    }

    public function getFeatureOptions($module)
    {
        $options = [];
        if ($module == 'Directory') {
            $options = [
                'Report.Directory' => __('User Default Identity'),
                'Report.Users'     => __('User List')
            ];
        } elseif ($module == 'Institutions') {
            $options = [
                'Report.Institutions' => __('Institutions'),
                'Report.InstitutionAssociations' => __('Associations'),
                'Report.InstitutionPositions' => __('Positions'),
                'Report.InstitutionProgrammes' => __('Programmes'),
                'Report.InstitutionClasses' => __('Classes'),
                'Report.InstitutionSubjects' => __('Subjects'),
                'Report.InstitutionStudents' => __('Students'),
                // 'Report.InstitutionStudentEnrollments' => __('Students Enrolments'),
                'Report.InstitutionStaff' => __('Staff'),
                'Report.StudentAbsences' => __('Student Absence'),
                'Report.StudentAttendanceSummary' => __('Student Attendance Summary'),
                'Report.StudentWithdrawalReport' => __('Student Withdrawal Report'),
                'Report.InstitutionSummaryReport' => __('Institution Summary Report'),
                'Report.BodyMasses' => __('Student Body Masses'),
                // 'Report.StaffAbsences' => __('Staff Absence'),
                'Report.StaffAttendances' => __('Staff Attendance'),
                'Report.StaffLeave' => __('Staff Leave'),
                'Report.StaffTransfers' => __('Staff Transfer'),
                'Report.InstitutionCases' => __('Cases'),
                'Report.ClassAttendanceNotMarkedRecords' => __('Class Attendance Marked'),
                //'Report.InstitutionSpecialNeedsStudents' => __('Special Needs Students'),
                // 'Report.InstitutionStudentsWithSpecialNeeds' => __('Students with Special Needs'),
                'Report.WashReports' => __('Wash Report'),
                'Report.Guardians' => __('Guardians'),
                'Report.InstitutionInfrastructures' => __('Infrastructure'),
                'Report.SpecialNeedsFacilities' => __('Special Needs Facilities'),
                'Report.InstitutionCommittees' => __('Committees'),
                //'Report.InstitutionSubjectsClasses' => __('Subjects/Classes'),//POCOR-5852 
                'Report.ClassAttendanceMarkedSummaryReport' => __('Class Attendance Marked Summary Report'),
                'Report.InfrastructureNeeds' => __('Infrastructure Needs'),
                'Report.Income' => __('Income Report'),
                'Report.Expenditure' => __('Expenditure Report')
            ];
        } elseif ($module == 'Students') {
            $options = [
                'Report.Students' => __('Students'),
                'Report.StudentsPhoto' => __('Students Photo'), 
                'Report.StudentIdentities' => __('Identities'),
                'Report.StudentContacts' => __('Contacts'),
                'Report.InstitutionStudentsOutOfSchool' => __('Students Out of School'),
                //'Report.StudentGuardians' => __('Guardians'), //POCOR-5393
                'Report.HealthReports' => __('Student Health Report'),
                'Report.BodyMassStatusReports' => __('BMI Status Report'), 
                'Report.StudentsRiskAssessment' => __('Risk Assessment Report') ,
				'Report.SubjectsBookLists' => __('Subject and Book List'),
                'Report.StudentNotAssignedClass' => __('Not Assigned to Class'),
                'Report.StudentsEnrollmentSummary' => __('Enrollment Summary'),
                'Report.SpecialNeeds' => __('Special Needs')
				
            ];
        } elseif ($module == 'Staff') {
            $options = [
                'Report.Staff' => __('Staff'),
                'Report.StaffPhoto' => __('Staff Photo'),
                'Report.StaffIdentities' => __('Identities'),
                'Report.StaffContacts' => __('Contacts'),
                'Report.StaffQualifications' => __('Qualifications'),
                'Report.StaffLicenses' => __('Licenses'),
                'Report.StaffEmploymentStatuses' => __('Employment Statuses'),
                'Report.StaffHealthReports' => __('Staff Health Report'),
                'Report.StaffSalaries' => __('Salaries'),
                'Report.StaffSystemUsage' => __('System Usage'),
                'Report.StaffTrainingReports' => __('Training Courses Report'),
                'Report.StaffLeaveReport' => __('Staff Leave'),
                'Report.StaffPositions' => __('Staff Positions Report'),
                'Report.PositionSummary' => __('Position Summary Report'),
                'Report.StaffDuties' => __('Duties Report'),
                'Report.StaffExtracurriculars' => __('Staff Extracurricular'),
				
            ];
        } elseif ($module == 'Textbooks') {
            $options = [
                'Report.Textbooks' => __('Textbooks'),
                'Report.InstitutionTextbooks' => __('Institution Textbooks')
            ];
        } elseif ($module == 'Trainings') {
            $options = [
                'Report.TrainingNeeds' => __('Needs'),
                'Report.TrainingCourses' => __('Courses'),
                'Report.TrainingSessions' => __('Sessions'),
                'Report.TrainingResults' => __('Results'),
                'Report.StaffTrainingApplications' => __('Applications'),
                'Report.TrainingTrainers' => __('Trainers'),
                'Report.TrainingSessionParticipants' => __('Session Participants')
            ];
        } elseif ($module == 'Scholarships') {
            $options = [
                'Report.Scholarships' => __('Scholarships'),
                'Report.ScholarshipApplications' => __('Scholarship Applications'),
                'Report.RecipientPaymentStructures' => __('Recipient Payment Structures'),
                'Report.RecipientAcademicStandings' => __('Recipient Academic Standings'),
                'Report.ScholarshipRecipients' => __('Scholarship Recipients'),
                'Report.ScholarshipDisbursements' => __('Scholarship Disbursements (Overview)'),
                'Report.ScholarshipDisbursementsAmounts' => __('Scholarship Disbursements (Detailed)'),
                'Report.ScholarshipEnrollments' => __('Scholarship Enrollments')
            ];
        } elseif ($module == 'Surveys') {
            $options = [
                'Report.Surveys' => __('Institutions')
            ];
        } elseif ($module == 'InstitutionRubrics') {
            $options = [
                'Report.InstitutionRubrics' => __('Rubrics')
            ];
        } elseif ($module == 'DataQuality') {
            $options = [
                'Report.PotentialStudentDuplicates' => __('Potential Student Duplicates'),
                'Report.PotentialStaffDuplicates' => __('Potential Staff Duplicates'),
                'Report.PotentialWrongBirthdates' => __('Potential Wrong Birthdates')
            ];
        } elseif ($module == 'Audits') {
            $options = [
                'Report.AuditLogins' => __('Logins'),
                'Report.AuditInstitutions' => __('Institutions'),
                'Report.AuditUsers' => __('Users')
            ];
        } elseif ($module == 'Examinations') {
            $options = [
                'Report.RegisteredStudentsExaminationCentre' => __('Registered Students by Examination Centre'),
                'Report.NotRegisteredStudents' => __('Not Registered Students'),
                'Report.ExaminationResults' => __('Examination Results'),
            ];
        } elseif ($module == 'Workflows') {
            $options = [
                'Report.WorkflowRecords' => __('Workflow Records')
            ];
        }
        return $options;
    }

    public function index()
    {
        return $this->redirect(['action' => 'Users']);
    }

    public function ajaxGetReportProgress()
    {

        $this->autoRender = false;
        $userId = $this->Auth->user('id');
        $dataSet = [];

        if (isset($this->request->query['ids'])) {
            $ids = $this->request->query['ids'];

            $fields = array(
                'ReportProgress.status',
                'ReportProgress.modified',
                'ReportProgress.current_records',
                'ReportProgress.total_records'
            );
            $ReportProgress = TableRegistry::get('Report.ReportProgress');
            if (!empty($ids)) {
                $results = $ReportProgress
                    ->find()
                    ->where([$ReportProgress->aliasField('id IN ') => $ids])
                    ->all();

                if (!$results->isEmpty()) {
                    foreach ($results as $key => $entity) {
                        if ($entity->total_records > 0) {
                            $data['percent'] = intval($entity->current_records / $entity->total_records * 100);
                            if ($data['percent'] > 100) {
                                $data['percent'] = 100;
                            }
                        } elseif ($entity->total_records == 0 && $entity->status == 0) {
                            // if only the status is complete, than percent will be 100, total record can still be 0 if the shell excel generation is slow, and percent should not be 100.
                            $data['percent'] = 100;
                        } else {
                            $data['percent'] = 0;
                        }
                        if (is_null($entity->modified)) {
                            $data['modified'] = $ReportProgress->formatDateTime($entity->created);
                        } else {
                            $data['modified'] = $ReportProgress->formatDateTime($entity->modified);
                        }

                        if (!is_null($entity->expiry_date)) {
                            $data['expiry_date'] = $ReportProgress->formatDateTime($entity->expiry_date);
                        } else {
                            $data['expiry_date'] = null;
                        }
                        $data['status'] = $entity->status;

                        $dataSet[$entity->id] = $data;
                    }
                }
            }
        }

        echo json_encode($dataSet);
        die;
    }
	
	public function Profiles()
    { 
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Report.Profiles']);
    }

    public function ViewReport()
    {
        ini_set('memory_limit', '-1');
        $data = $_GET;
        $explode_data = explode("/", $data['file_path']);
        if (!empty($this->request->param('institutionId'))) {
            $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
        } else {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
        }

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
        $this->Navigation->addCrumb($data['module']);
        $header = __('Reports') . ' - ' .$data['module'];

        $inputFileName = WWW_ROOT. 'export/'.end($explode_data);

        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);

        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = $sheet->getHighestRow(); 
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= 1; $row++){
            $rowHeader = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                            NULL,
                                            TRUE,
                                            FALSE);
        }
        $rowHeaderNew = $this->array_flatten($rowHeader);
        for ($row = 2; $row <= $highestRow -1; $row++){ 
            //  Read a row of data into an array
            $rowData[] = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                            NULL,
                                            TRUE,
                                            FALSE);
            if($this->isEmptyRow(reset($rowData))) { continue; }
            //  Insert row data array into your database of choice here
        }
        foreach($rowData as $newKey => $newDataVal){
        	foreach($newDataVal as $kay2 => $new_data_arr){
        		if(isset($new_data_arr)){
        			$newArr2[] = array_combine($rowHeaderNew, $new_data_arr);
        		}
        	}
        }
        $this->set('rowHeader', $rowHeader);
        $this->set('newArr2', $newArr2);

        $this->set('contentHeader', $header);
    }

    function array_flatten($array) { 
        if (!is_array($array)) { 
          return false; 
        } 
        $result = array(); 
        foreach ($array as $key => $value) { 
          if (is_array($value)) { 
            $result = array_merge($result, $this->array_flatten($value)); 
          } else { 
            $result = array_merge($result, array($key => $value));
          } 
        } 
        return $result; 
      }

    function isEmptyRow($row) {
        foreach($row as $cell){
            if (null !== $cell) return false;
        }
        return true;
    }
	
}
