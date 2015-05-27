<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;

class NavigationComponent extends Component {
	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public $controller;
	public $action;

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
		$this->action = $this->request->params['action'];
	}

	public function beforeRender(Event $event) {
		$controller = $this->controller;
		$action = $this->action;
		$id = $this->request->param('id');

		$navigations = [];

		if ($controller->name == 'Institutions' && $action == 'index') {
			$navigations = [
				'collapse' => false,
				'items' => [
					'Institutions' => [
						'collapse' => true,
						'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']
					],
					'Students' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Students', 'action' => 'index']
					],
					'Areas' => [
						'collapse' => true,
						'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'index']
					]
				]
			];
		} else {
			$navigations = [
				'collapse' => false,
				'items' => [
					'Institutions' => [
						'collapse' => true,
						'url' => ['plugin' => false, 'controller' => 'Institutions', 'action' => 'index'],
						'items' => [
							'Overview' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'view', $id]],
							'Attachments' => ['url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'attachments']]
						]
					],
					'Students' => [
						'collapse' => true,
						'items' => [
							'General' => [
								'collapse' => true,
								'items' => [
									'Overview' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'view']],
									'Contacts' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Contacts']],
									'Identities' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Identities']],
									'Languages' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Languages']],
									'Comments' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Comments']],
									'Special Needs' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'SpecialNeeds']],
									'Awards' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Awards']],
									'Attachments' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Attachments']],
									'More' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'additional']]
								]
							],
							'Details' => [
								'collapse' => true,
								'items' => [
									'Guardians' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Guardians']],
									'Programmes' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Programmes']],
									'Sections' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Sections']],
									'Classes' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Classes']],
									'Absence' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Absence']],
									'Behaviour' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Behaviour']],
									'Results' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Results']],
									'Extracurricular' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'Extracurricular']]
// <a href="/core/Students/guardians" >Guardians
// <a href="/core/Students/Programme" >Programmes
// <a href="/core/Students/StudentSection" >Sections
// <a href="/core/Students/classes" >Classes
// <a href="/core/Students/Absence" >Absence
// <a href="/core/Students/StudentBehaviour" >Behaviour
// <a href="/core/Students/assessments" >Results
// <a href="/core/Students/extracurricular" >Extracurricular
								]
							],
							'Finance' => [
								'collapse' => true,
								'items' => [
									'Bank Accounts' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'BankAccounts']],
									'Fees' => ['url' => ['plugin' => 'Student', 'controller' => 'students', 'action' => 'StudentFee']],
// <a href="/core/Students/bankAccounts" >Bank Accounts
// <a href="/core/Students/StudentFee" >Fees
								]
							]
						]
					],


				]
			];
		}

		$controller->set('_navigations', $navigations);
	}
}
