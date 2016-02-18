<?php
namespace FieldOption\Controller;

use ArrayObject;
use FieldOption\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class FieldOptionsController extends AppController {
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('FieldOption.FieldOptionValues', ['!search'], ['deleteStrategy' => 'transfer']);

		$controller = $this->name;
		$this->request->addParams([
			'accessMap' => [
				"$controller.Genders" 						=> "$controller.%s",
				"$controller.Localities" 					=> "$controller.%s",
				"$controller.Ownerships" 					=> "$controller.%s",
				"$controller.Providers" 					=> "$controller.%s",
				"$controller.Sectors" 						=> "$controller.%s",
				"$controller.Statuses" 						=> "$controller.%s",
				"$controller.Types" 						=> "$controller.%s",
				"$controller.NetworkConnectivities" 		=> "$controller.%s",
				"$controller.StaffPositionGrades" 			=> "$controller.%s",
				"$controller.StaffPositionTitles" 			=> "$controller.%s",
				"$controller.AllergyTypes" 					=> "$controller.%s",
				"$controller.ConsultationTypes" 			=> "$controller.%s",
				"$controller.Relationships" 				=> "$controller.%s",
				"$controller.Conditions" 					=> "$controller.%s",
				"$controller.ImmunizationTypes" 			=> "$controller.%s",
				"$controller.TestTypes" 					=> "$controller.%s",
				"$controller.QualityVisitTypes" 			=> "$controller.%s",
				"$controller.InfrastructureOwnerships" 		=> "$controller.%s",
				"$controller.InfrastructureConditions" 		=> "$controller.%s",
				"$controller.QualificationSpecialisations" 	=> "$controller.%s",
				"$controller.QualificationLevels" 			=> "$controller.%s",
				"$controller.FeeTypes" 						=> "$controller.%s"
			]
		]);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$header = 'Field Options';
		
		$this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
		$session = $this->request->session();
		$action = $this->request->params['action'];
		
		$this->set('contentHeader', __($header));
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$alias = $model->alias;
		$header = __('Field Options') . ' - ' . $model->getHeader($alias);

		$this->Navigation->addCrumb($model->getHeader($alias));

		$this->set('contentHeader', $header);
	}

	public function Genders() { $this->ControllerAction->process(['alias' => __FUNCTION__, 						'className' => 'Institution.Genders']); }
	public function Localities() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Localities']); }
	public function Ownerships() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Ownerships']); }
	public function Providers() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Providers']); }
	public function Sectors() { $this->ControllerAction->process(['alias' => __FUNCTION__, 						'className' => 'Institution.Sectors']); }
	public function Statuses() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Statuses']); }
	public function Types() { $this->ControllerAction->process(['alias' => __FUNCTION__, 						'className' => 'Institution.Types']); }
	public function NetworkConnectivities() { $this->ControllerAction->process(['alias' => __FUNCTION__, 		'className' => 'Institution.NetworkConnectivities']); }
	public function StaffPositionGrades() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Institution.StaffPositionGrades']); }
	public function StaffPositionTitles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Institution.StaffPositionTitles']); }
	public function AllergyTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'Health.AllergyTypes']); }
	public function ConsultationTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Health.ConsultationTypes']); }
	public function Relationships() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'Health.Relationships']); }
	public function Conditions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Health.Conditions']); }
	public function ImmunizationTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Health.ImmunizationTypes']); }
	public function TestTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Health.TestTypes']); }
	public function QualityVisitTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'FieldOption.QualityVisitTypes']); }
	public function InfrastructureOwnerships() { $this->ControllerAction->process(['alias' => __FUNCTION__, 	'className' => 'FieldOption.InfrastructureOwnerships']); }
	public function InfrastructureConditions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 	'className' => 'FieldOption.InfrastructureConditions']); }
	public function QualificationSpecialisations() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'FieldOption.QualificationSpecialisations']); }
	public function QualificationLevels() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'FieldOption.QualificationLevels']); }
	public function FeeTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'FieldOption.FeeTypes']); }
}
