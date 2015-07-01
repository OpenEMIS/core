<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;

class RubricStatusesTable extends AppTable {
	private $_contain = ['AcademicPeriods', 'SecurityRoles', 'Programmes'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->belongsToMany('AcademicPeriods', [
			'className' => 'AcademicPeriod.AcademicPeriods',
			'joinTable' => 'rubric_status_periods',
			'foreignKey' => 'rubric_status_id',
			'targetForeignKey' => 'academic_period_id'
		]);
		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'rubric_status_roles',
			'foreignKey' => 'rubric_status_id',
			'targetForeignKey' => 'security_role_id'
		]);
		$this->belongsToMany('Programmes', [
			'className' => 'Education.EducationProgrammes',
			'joinTable' => 'rubric_status_programmes',
			'foreignKey' => 'rubric_status_id',
			'targetForeignKey' => 'education_programme_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('rubric_template_id', ['type' => 'select']);
		$this->ControllerAction->field('academic_period_level');
		$this->ControllerAction->field('academic_periods');
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('security_roles', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Security Roles'),
			'visible' => true
		]);
		$this->ControllerAction->addField('programmes', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Programmes'),
			'visible' => true
		]);

		$this->ControllerAction->setFieldOrder([
			'rubric_template_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods', 'security_roles', 'programmes'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$options['contain'] = array_merge($options['contain'], $this->_contain);
		return $options;
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		//Setup fields
		list($securityRoleOptions, , $programmeOptions) = array_values($this->getSelectOptions());

		$this->fields['security_roles']['options'] = $securityRoleOptions;
		$this->fields['programmes']['options'] = $programmeOptions;
	}

	public function onUpdateFieldAcademicPeriodLevel(Event $event, array $attr, $action, Request $request) {
		$AcademicPeriodLevels = TableRegistry::get('AcademicPeriod.AcademicPeriodLevels');
		$levelOptions = $AcademicPeriodLevels->getList()->toArray();

		$attr['options'] = $levelOptions;
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriods(Event $event, array $attr, $action, Request $request) {
		$selectedLevel = key($this->fields['academic_period_level']['options']);
		if ($request->is('post')) {
			$selectedLevel = $request->data($this->aliasField('academic_period_level'));
		}

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriods
			->find('list')
			->find('visible')
			->find('order')
			->where([$AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
			->toArray();
		
		$attr['type'] = 'chosenSelect';
		$attr['options'] = $periodOptions;
		return $attr;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
        $securityRoleOptions = $SecurityRoles
        	->find('list')
        	->find('visible')
			->find('order')
        	->toArray();
        $selectedSecurityRole = key($securityRoleOptions);

        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $programmeOptions = $EducationProgrammes
        	->find('list')
        	->find('visible')
			->find('order')
        	->toArray();
        $selectedProgramme = key($programmeOptions);

		return compact('securityRoleOptions', 'selectedSecurityRole', 'programmeOptions', 'selectedProgramme');
	}
}
