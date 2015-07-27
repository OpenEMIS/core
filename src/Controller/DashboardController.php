<?php
namespace App\Controller;

use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Controller\AppController;

class DashboardController extends AppController {
	public function initialize() {
		parent::initialize();

		// $this->ControllerAction->model('Notices');
		// $this->loadComponent('Paginator');

		$this->loadComponent('WorkBench', [
			'models' => [
				'Institution.StudentTransfers',
				'Institution.StudentDropout'
			]
		]);
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	// $this->Navigation->addCrumb('Dashboard', ['plugin' => false, 'controller' => 'Dashboards', 'action' => 'index']);

    	$header = __('Dashboard');
		$this->set('contentHeader', $header);
    }

	public function index() {
		$workbenchData = $this->WorkBench->getList();

		$InstitutionSiteStudents = TableRegistry::get('Institution.InstitutionSiteStudents');
		$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
		$InstitutionSiteStaff = TableRegistry::get('Institution.InstitutionSiteStaff');

		$highChartDatas = [];
		$highChartDatas[] = $InstitutionSiteStudents->getHighChart('number_of_students_by_year');
		$highChartDatas[] = $InstitutionSiteSectionStudents->getHighChart('number_of_students_by_grade');
		$highChartDatas[] = $InstitutionSiteStaff->getHighChart('number_of_staff');

		$noticeData = TableRegistry::get('Notices')->find('all')->order(['Notices.created desc'])->toArray();

		$this->set('workbenchData', $workbenchData);
		$this->set('noticeData', $noticeData);
		$this->set('highChartDatas', $highChartDatas);
	}
}
