<?php
namespace Student\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class StudentsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('SecurityUsers');
		// $this->ControllerAction->model()->addBehavior('Student.Student');

		$this->ControllerAction->models = [
			'Contacts' => ['className' => 'UserContacts'],
			'StudentIdentities' => ['className' => 'Student.StudentIdentities'],
			'StudentLanguages' => ['className' => 'Student.StudentLanguages'],
			'StudentComments' => ['className' => 'Student.StudentComments'],
			'StudentSpecialNeeds' => ['className' => 'Student.StudentSpecialNeeds'],
			'StudentAwards' => ['className' => 'Student.StudentAwards'],
			'StudentAttachments' => ['className' => 'Student.StudentAttachments']
		];

		$this->set('contentHeader', 'Students');
    }

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		$this->ControllerAction->beforePaginate = function($model, $options) {
			if (in_array($model->alias, array_keys($this->ControllerAction->models))) {
				if ($this->ControllerAction->Session->check('SecurityUsers.id')) {
					$securityUserId = $this->ControllerAction->Session->read('SecurityUsers.id');
					if (!array_key_exists('conditions', $options)) {
						$options['conditions'] = [];
					}
					$options['conditions'][] = [$model->alias().'.security_user_id = ' => $securityUserId];
				} else {
					$this->ControllerAction->Message->alert('general.noData');
					$this->redirect(['action' => 'index']);
				}
			}
			return $options;
		};

		$visibility = ['view' => true, 'edit' => true];

		$header = __('Student');
		$controller = $this;

		$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;
			$session = $this->request->session();

			$model->fields['student_id']['type'] = 'hidden';
			$model->fields['student_id']['value'] = 1;//$session->read('InstitutionSite.id');
			$controller->set('contentHeader', $header);
		};

		unset($this->SecurityUsers->fields['photo_content']);

		
		// pr($this->ControllerAction->models);

		
		// $this->Institutions->fields['alternative_name']['visible'] = $visibility;
		// $this->Institutions->fields['address']['visible'] = $visibility;
		// $this->Institutions->fields['postal_code']['visible'] = $visibility;
		// $this->Institutions->fields['telephone']['visible'] = $visibility;
		// $this->Institutions->fields['fax']['visible'] = $visibility;
		// $this->Institutions->fields['email']['visible'] = $visibility;
		// $this->Institutions->fields['website']['visible'] = $visibility;
		// $this->Institutions->fields['date_opened']['visible'] = $visibility;
		// $this->Institutions->fields['year_opened']['visible'] = $visibility;
		// $this->Institutions->fields['date_closed']['visible'] = $visibility;
		// $this->Institutions->fields['year_closed']['visible'] = $visibility;
		// $this->Institutions->fields['longitude']['visible'] = $visibility;
		// $this->Institutions->fields['latitude']['visible'] = $visibility;
		// $this->Institutions->fields['security_group_id']['visible'] = $visibility;
		// $this->Institutions->fields['contact_person']['visible'] = $visibility;

		// // columns to be removed, used by ECE QA Dashboard
		// $this->Institutions->fields['institution_site_area_id']['visible'] = $visibility;
	}

	public function view($id = null) {
		if (is_null($id)) {
			$id = $this->ControllerAction->Session->read('SecurityUsers.id');
		}
		// if ($this->InstitutionSiteStudents->Students->exists($id)) {
		// 	$this->ControllerAction->Session->write('InstitutionSiteStudents.id',$id);
		// 	// wrong because db will be changed but currently now lets just try to get the security user id
		// 	$query = $this->InstitutionSiteStudents->get($id, ['contain' => ['Students']]);
		// 	$this->ControllerAction->Session->write('Student.security_user_id',$query->student->security_user_id);
		// }
		$this->ControllerAction->view($id);
		$this->ControllerAction->render();
	}
}
