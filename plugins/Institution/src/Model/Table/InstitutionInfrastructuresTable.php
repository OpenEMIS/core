<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionInfrastructuresTable extends AppTable {
	private $_fieldOrder = [
		'institution_site_id', 'infrastructure_level_id', 'code', 'name', 'infrastructure_type_id', 'size', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'infrastructure_condition_id', 'comment'
	];

	public function initialize(array $config) {
		$this->table('institution_site_infrastructures');
		parent::initialize($config);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->belongsTo('Types', ['className' => 'Infrastructure.InfrastructureTypes', 'foreignKey' => 'infrastructure_type_id']);
		$this->belongsTo('InfrastructureOwnerships', ['className' => 'FieldOption.InfrastructureOwnerships']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);

		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null,
			'fieldKey' => 'infrastructure_custom_field_id',
			'formKey' => 'infrastructure_level_id',
			'tableColumnKey' => 'infrastructure_custom_table_column_id',
			'tableRowKey' => 'infrastructure_custom_table_row_id',
			'recordKey' => 'institution_site_infrastructure_id',
			'fieldValueKey' => ['className' => 'Institution.InstitutionInfrastructureCustomFieldValues', 'foreignKey' => 'institution_site_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellKey' => ['className' => 'Institution.InstitutionInfrastructureCustomTableCells', 'foreignKey' => 'institution_site_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('infrastructure_level_id');
		$this->ControllerAction->field('infrastructure_type_id');
		$this->ControllerAction->field('year_acquired');
		$this->ControllerAction->field('year_disposed');

		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Infrastructure.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);

		$this->fields['infrastructure_level_id']['visible'] = false;
		$this->fields['size']['visible'] = false;
		$this->fields['infrastructure_ownership_id']['visible'] = false;
		$this->fields['year_acquired']['visible'] = false;
		$this->fields['year_disposed']['visible'] = false;
		$this->fields['infrastructure_condition_id']['visible'] = false;
		$this->fields['comment']['visible'] = false;
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());

        $options['conditions'][] = [
        	$this->aliasField('infrastructure_level_id') => $selectedLevel
        ];

		$this->controller->set(compact('levelOptions', 'selectedLevel'));
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$this->fields['infrastructure_ownership_id']['type'] = 'select';
		$this->fields['infrastructure_condition_id']['type'] = 'select';
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedLevel) = array_values($this->getSelectOptions());
		$entity->infrastructure_level_id = $selectedLevel;
	}

	public function onUpdateFieldInfrastructureLevelId(Event $event, array $attr, $action, Request $request) {
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());

		$attr['options'] = $levelOptions;
		$attr['default'] = $selectedLevel;
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldInfrastructureTypeId(Event $event, array $attr, $action, Request $request) {
		list(, $selectedLevel) = array_values($this->getSelectOptions());
		if ($request->is('post')) {
			$selectedLevel = $request->data($this->aliasField('infrastructure_level_id'));
		}

		$typeOptions = $this->Types
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->Types->aliasField('infrastructure_level_id') => $selectedLevel])
			->toArray();
		$attr['options'] = $typeOptions;

		return $attr;
	}

	public function onUpdateFieldYearAcquired(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getYearOptionsByConfig();
		return $attr;
	}

	public function onUpdateFieldYearDisposed(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getYearOptionsByConfig();
		return $attr;
	}

	public function getYearOptionsByConfig() {
		$ConfigItems = TableRegistry::get('ConfigItems');
		$lowestYear = $ConfigItems->value('lowest_year');
		$currentYear = date("Y");
		
		for($i=$currentYear; $i >= $lowestYear; $i--){
			$yearOptions[$i] = $i;
		}

		return $yearOptions;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$levelId = $this->request->query('level');
		$levelOptions = $this->Levels
			->find('list')
			->toArray();
		$selectedLevel = !is_null($levelId) ? $levelId : key($levelOptions);

		return compact('levelOptions', 'selectedLevel');
	}
}
