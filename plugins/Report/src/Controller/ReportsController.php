<?php
namespace Report\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ReportsController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->ControllerAction->models = [
			'Institutions'	=> ['className' => 'Report.Institutions', 'actions' => ['index', 'add']],
			'Students'	 	=> ['className' => 'Report.Students', 'actions' => ['index', 'add']],
			'Staff'	 		=> ['className' => 'Report.Staff', 'actions' => ['index', 'add']],
			'Surveys'	 	=> ['className' => 'Report.Surveys', 'actions' => ['index', 'add']],
			'InstitutionRubrics' => ['className' => 'Report.InstitutionRubrics', 'actions' => ['index', 'add']],
			'DataQuality' => ['className' => 'Report.DataQuality', 'actions' => ['index', 'add']],
			'Audit' => ['className' => 'Report.Audit', 'actions' => ['index', 'add']],
		];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Reports';
		$this->Navigation->addCrumb($header, ['plugin' => 'Security', 'controller' => 'Securities', 'action' => 'index']);
		$this->Navigation->addCrumb($this->request->action);
	}

	public function onInitialize(Event $event, Table $table) {
		$header = __('Reports') . ' - ' . __($table->alias());
		$this->set('contentHeader', $header);
	}

	public function getFeatureOptions($module) {
		$options = [];
		if ($module == 'Institutions') {
			$options = [
				'Report.Institutions' => __('Institutions'),
				'Report.InstitutionPositions' => __('Positions'),
				'Report.InstitutionProgrammes' => __('Programmes'),
				'Report.InstitutionClasses' => __('Classes'),
				'Report.InstitutionSubjects' => __('Subjects'),
				'Report.InstitutionStudents' => __('Students'),
				// 'Report.InstitutionStudentEnrollments' => __('Students Enrolments'),
				'Report.InstitutionStaff' => __('Staff'),
				// 'Report.InstitutionStaffOnLeave' => __('StaffOnLeave')
				'Report.StaffAbsences' => __('Staff Absence'),
				'Report.InstitutionStudentTeacherRatio' => __('Student Teacher Ratio'),
				'Report.InstitutionStudentClassroomRatio' => __('Student Classroom Ratio'),
				'Report.InstitutionStudentsOutOfSchool' => __('Students Out of School'),
			];
		} else if ($module == 'Students') {
			$options = [
				'Report.Students' => __('Students'),
				'Report.StudentIdentities' => __('Identities'),
				'Report.StudentContacts' => __('Contacts')
			];
		} else if ($module == 'Staff') {
			$options = [
				'Report.Staff' => __('Staff'),
				'Report.StaffIdentities' => __('Identities'),
				'Report.StaffContacts' => __('Contacts')
			];
		} else if ($module == 'Surveys') {
			$options = [
				'Report.Surveys' => __('Institutions')
			];
		} else if ($module == 'InstitutionRubrics') {
			$options = [
				'Report.InstitutionRubrics' => __('Rubrics')
			];
		} else if ($module == 'DataQuality') {
			$options = [
				'Report.PotentialStudentDuplicates' => __('Potential Student Duplicates'),
				'Report.PotentialStaffDuplicates' => __('Potential Staff Duplicates')
			];
		} else if ($module == 'Audit') {
			$options = [
				'Report.Audit' => __('Audit')
			];
		}
		return $options;
	}

	public function index() {
		return $this->redirect(['action' => 'Users']);
	}

	public function ajaxGetReportProgress() {
		$this->autoRender = false;

		$userId = $this->Auth->user('id');
		$id = $this->request->query['id'];

		$fields = array(
			'ReportProgress.status',
			'ReportProgress.modified',
			'ReportProgress.current_records',
			'ReportProgress.total_records'
		);
		$ReportProgress = TableRegistry::get('Report.ReportProgress');
		$entity = $ReportProgress->find()->where(['id' => $id])->first();
		$data = [];

		if ($entity) {
			if ($entity->total_records > 0) {
				$data['percent'] = intval($entity->current_records / $entity->total_records * 100);
			} else {
				$data['percent'] = 0;
			}
			if (is_null($entity->modified)) {
				$data['modified'] = $ReportProgress->formatDateTime($entity->created);
			} else {
				$data['modified'] = $ReportProgress->formatDateTime($entity->modified);
			}
			$data['status'] = $entity->status;
		}
		echo json_encode($data);
		die;
	}
}
