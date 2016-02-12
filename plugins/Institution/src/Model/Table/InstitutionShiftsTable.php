<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionShiftsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_id']);
		$this->belongsTo('LocationInstitutions',	['className' => 'Institution.LocationInstitutions']);
		$this->hasMany('InstitutionSections', 		['className' => 'Institution.InstitutionSections', 	'foreignKey' => 'institution_shift_id']);
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function validationDefault(Validator $validator) {
		$validator
 	        ->add('start_time', 'ruleCompareDate', [
		            'rule' => ['compareDate', 'end_time', true]
	    	    ])
 	        ->add('institution_name', 'ruleCheckLocationInstitutionId', [
 	        		'rule' => ['checkInstitutionLocation']
 	        	])
			->add('location', 'ruleCheckShiftAvailable', [
        		'rule' => ['checkShiftAvailable'],
        		])
			;
		return $validator;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('academic_period_id', ['type' => 'select']);
		$this->field('name', ['type' => 'string']);
		$this->field('period', ['type' => 'string']);	
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra){
		$this->field('period', ['visible' => false]);

		$toggleOptions = [
			'OurShifts' => $this->getMessage('InstitutionShifts.our_shifts'),
			'ExternalShifts' => $this->getMessage('InstitutionShifts.external_shifts')
		];
		$extra['selectedToggleOption'] = $this->queryString('toggle', $toggleOptions);
		$extra['elements']['control'] = [
			'name' => 'Institution.Shifts/controls', 
			'data' => [
				'toggleOptions'=> $toggleOptions,
				'selectedToggleOption'=> $extra['selectedToggleOption']
			],
			'order' => 3
		];
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		if (array_key_exists('selectedToggleOption', $extra)) {
			switch ($extra['selectedToggleOption']) {
				case 'OurShifts': //institution_id == current school id
					$this->field('location', ['visible' => false]);
					// already automatically done in controller
					break;

				case 'ExternalShifts': //location == current school id 
					$this->field('location_institution_id', ['visible' => false]);
					$query->where([
						$this->aliasField('location_institution_id') => $institutionId
					], [], true); // undoing all where before this
					$query->where([$this->aliasField('institution_id') . ' != ' .$institutionId]);
					$extra['indexButtons'] = [];
					break;
				
				default:
					# code...
					break;
			}
		}
	}

	public function transferOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
		$query->where([
			'institution_id' => $entity->institution_id,
			'academic_period_id' => $entity->academic_period_id
		]);
	}

	public function afterAction(Event $event, ArrayObject $extra) {
		$this->field('location', ['after' => 'end_time', 'attr' => ['label' => $this->getMessage('InstitutionShifts.location')]]);
		$this->field('location_institution_id', ['after' => 'location']);
	}

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/

	public function viewBeforeAction($event) {
		$this->field('period', ['visible' => false]);
		$this->field('location', ['visible' => false]);
	}

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
    	$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
    	if (!empty($entity->modified_user_id)) {
	    	$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
	    }
		return $entity;
    }

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('period', ['visible' => false]);
		$this->fields['start_time']['visible'] = true;
		$this->fields['start_time']['type'] = 'time';
		$this->fields['end_time']['visible'] = true;
		$this->fields['end_time']['type'] = 'time';
	}
	
	public function addEditOnChangeLocation(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		unset($data[$this->alias()]['location_institution_id']);
	}

	public function onGetLocation(Event $event, Entity $entity) {
		return $entity->institution->name;
	}

	public function onUpdateFieldLocation(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = ['CURRENT' => __('This Institution'), 'OTHER' => __('Other Institution')];
			$attr['onChangeReload'] = 'changeLocation';

			if($action == 'edit'){
				$params = $request->params;
				if($params['pass'][0] == $action && !empty($params['pass'][1])){
					$entity = $this->get($params['pass'][1]);
					if($entity->institution_id != $entity->location_institution_id) {
						$attr['default'] = 'OTHER';
					} 
				}
			}
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		$academicPeriodOptions = $this->AcademicPeriods->getlist(['isEditable'=>true]);
		$attr['options'] = $academicPeriodOptions;
		if ($action == 'add' && empty($request->data)) {
			$attr['attr']['value'] = $this->AcademicPeriods->getCurrent();
		}
		return $attr;
	}

	public function onUpdateFieldLocationInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'location_institution_id', 'name' => $this->aliasField('location_institution_id')];
			$attr['noResults'] = __('No Institutions found');
			$attr['attr'] = ['placeholder' => __('Institution Code or Name')];
			$attr['url'] = ['controller' => $this->controller->name, 'action' => 'ajaxInstitutionAutocomplete'];
			$attr['attr']['value'] = '';
			$attr['attr']['label'] = $this->getMessage('InstitutionShifts.institution');
			// pr('ere');
			if($request->data){
				$data = $request->data[$this->alias()];
				if($data['location'] == 'CURRENT'){
					// pr('here');
					$attr['type'] = 'hidden';
					$institutionId = $this->Session->read('Institution.Institutions.id');
					$attr['value'] = $institutionId;
				} else {
					if($action == 'edit') {
						if ($request->is(['post', 'put']) && !empty($request->data)) {
							$Institutions = TableRegistry::get('Institution.Institutions');
							$entity = $Institutions->findById($request->data[$this->alias()]['location_institution_id'])->first(); 
							$attr['attr']['value'] = $entity->name;
						}
					}
					$attr['fieldName'] = $this->aliasField('institution_name');
				}
			} else {
				if($action == 'edit') {
					$params = $request->params;
					if($params['pass'][0] == $action && !empty($params['pass'][1])){
						$entity = $this->findById($params['pass'][1])->contain(['LocationInstitutions'])->first(); 
						if($entity->institution_id != $entity->location_institution_id) {
							$attr['visible'] = true;
							$attr['attr']['value'] = $entity->location_institution->name;
							$attr['fieldName'] = $this->aliasField('institution_name');
						} 
						else {
							$attr['visible'] = false;
						}
					}
				} else {
					$attr['type'] = 'hidden';
					$institutionId = $this->Session->read('Institution.Institutions.id');
					$attr['value'] = $institutionId;
				}
			}
		}
		return $attr;
	}

/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/



/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function createInstitutionDefaultShift($institutionsId, $academicPeriodId){
		$data = $this->getShifts($institutionsId, $academicPeriodId);

		if (empty($data)) {			
			$schoolAcademicPeriod = $this->AcademicPeriods->get($academicPeriodId);

			$ConfigItem = TableRegistry::get('ConfigItems');
			$settingStartTime = $ConfigItem->value('start_time');
			$hoursPerDay = intval($ConfigItem->value('hours_per_day'));
			if ($hoursPerDay > 1) {
				$endTimeStamp = strtotime('+' . $hoursPerDay . ' hours', strtotime($settingStartTime));
			} else {
				$endTimeStamp = strtotime('+' . $hoursPerDay . ' hour', strtotime($settingStartTime));
			}
			$endTime = date('h:i A', $endTimeStamp);

			$defaultShift = [
				'name' => __('Default') . ' ' . __('Shift') . ' ' . $schoolAcademicPeriod->name,
				'academic_period_id' => $academicPeriodId,
				'start_time' => $settingStartTime,
				'end_time' => $endTime,
				'institution_id' => $institutionsId,
				'location_institution_id' => $institutionsId,
				'location_institution_name' => 'Institution Site Name (Shift Location)'
			];

			$data = $this->newEntity();
			$data = $this->patchEntity($data, $defaultShift);
			$this->save($data);
		}
	}

	public function getShifts($institutionsId, $periodId) {
		$conditions = [
			'institution_id' => $institutionsId,
			'academic_period_id' => $periodId
		];
		$query = $this->find('all');
		// $query->contain(['Institutions', 'LocationInstitutions', 'AcademicPeriods']);
		$query->where($conditions);
		$data = $query->toArray();
		return $data;
	}
	
	public function getShiftOptions($institutionsId, $periodId) {
		$query = $this->find('all')
					->where([
						'institution_id' => $institutionsId,
						'academic_period_id' => $periodId
					])
					;
		$data = $query->toArray();

		$list = [];
		foreach ($data as $key => $obj) {
			$list[$obj->id] = $obj->name;
		}

		return $list;
	}
}
