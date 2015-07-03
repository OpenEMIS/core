<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AssessmentsTable extends AppTable {
	public function initialize(array $config) {
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		// $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		// $this->belongsTo('Sections', ['className' => 'Institution.InstitutionSiteSections', 'foreignKey' => 'institution_site_section_id']);
		// $this->belongsTo('Classes', ['className' => 'Institution.InstitutionSiteClasses', 'foreignKey' => 'institution_site_class_id']);
	}

	// Event: ControllerAction.Model.onGetOpenemisNo
	

	// Event: ControllerAction.Model.index.beforeAction
	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('visible', ['visible' => false]);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('type', ['visible' => false]);
		$this->ControllerAction->field('academic_period');

		$this->ControllerAction->setFieldOrder([]);
	}
		
	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$options['finder'] = ['visible' => []];
		return $options;
	}

	public function indexOnInitializeButtons(Event $event, array $buttons) {
		$buttons['view']['url']['action'] = 'StudentResults';
		$buttons['view']['url'][0] = 'index';
		return $buttons;
	}
}
