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

	const SINGLE_OWNER = 1;
    const SINGLE_OCCUPIER = 2;
    const MULTIPLE_OWNER = 3;
    const MULTIPLE_OCCUPIER = 4;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_id']);
		$this->belongsTo('LocationInstitutions',	['className' => 'Institution.LocationInstitutions']);
		$this->hasMany('InstitutionClasses', 		['className' => 'Institution.InstitutionClasses', 	'foreignKey' => 'institution_shift_id']);

		$this->belongsTo('ShiftOptions', 			['className' => 'Institution.ShiftOptions']);
		
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'restrict');
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

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['ControllerAction.Model.ajaxInstitutionsAutocomplete'] = 'ajaxInstitutionsAutocomplete';
    	return $events;
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		//to show list of academic period for selection
		$academicPeriodOptions = $this->AcademicPeriods->getYearList();

		$extra['selectedAcademicPeriodOptions'] = $this->queryString('toggle', $academicPeriodOptions);
		$extra['elements']['control'] = [
			'name' => 'Institution.Shifts/controls', 
			'data' => [
				'toggleOptions'=> $academicPeriodOptions,
				'selectedToggleOption'=> $extra['selectedAcademicPeriodOptions']
			],
			'order' => 3
		];

		//logic to remove 'add' button if the institution has received shift from other based on the academic period
		$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
		if ($this->checkShiftOccupier($extra['selectedAcademicPeriodOptions'])) { //if occupier, then remove the 'add' button
			unset($toolbarButtonsArray['add']);
		}
		$extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

		$this->field('location');
		$this->setFieldOrder([
			'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'location', 'location_institution_id'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) 
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');
		if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
			
			$query->where([
						'OR' => [
							[$this->aliasField('location_institution_id') => $institutionId],
							[$this->aliasField('institution_id') => $institutionId]
						],
						$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
					], [], true); //this parameter will remove all where before this and replace it with new where.
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$currentInstitutionId = $this->Session->read('Institution.Institutions.id');
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		//logic that if the owner != occupier then if the active session is the occupier, then remove edit and delete button.
		if (($entity->institution_id) != ($entity->location_institution_id)) {
			if (($entity->institution_id) != $currentInstitutionId) {
				unset($buttons['remove']);
				unset($buttons['edit']);
			}
		}

		return $buttons;
	}

	public function transferOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) 
	{
		$query->where([
			'institution_id' => $entity->institution_id,
			'academic_period_id' => $entity->academic_period_id
		]);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) 
	{
		$this->setupFields($entity);
	}

/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/

	public function viewBeforeAction(Event $event, ArrayObject $extra)
	{
		$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
		if ($this->checkShiftOccupier($this->getSelectedAcademicPeriod())) { //if occupier, then remove the 'edit / remove' button
			unset($toolbarButtonsArray['edit']);
			unset($toolbarButtonsArray['remove']);
		}
		$extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
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

	public function onGetLocation(Event $event, Entity $entity) {
		return $entity->institution->name;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		//$academicPeriodOptions = $this->AcademicPeriods->getlist(['isEditable'=>true]);
		$academicPeriodOptions = $this->AcademicPeriods->getYearList();
		$attr['options'] = $academicPeriodOptions;
		if (($action == 'add') || ($action == 'edit')) {

			$attr['attr']['value'] = $this->getSelectedAcademicPeriod();
		}
		$attr['onChangeReload'] = 'changeAcademicPeriod';
		return $attr;
	}

	public function onUpdateFieldShiftOptionId(Event $event, array $attr, $action, $request) {
		$attr['onChangeReload'] = 'changeShiftOption';
		return $attr;
	}

	public function onUpdateFieldStartTime(Event $event, array $attr, $action, Request $request) {
		if ($request->data) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
			if ($submit == 'changeShiftOption') {
				$shiftOption = $request->query['shiftoption'];
				$attr['value'] = $this->ShiftOptions->getStartEndTime($shiftOption, 'start')->format('H:i');
				return $attr;
			}
		};
	}

	public function onUpdateFieldEndTime(Event $event, array $attr, $action, Request $request) {
		if ($request->data) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
				if ($submit == 'changeShiftOption') {
				$shiftOption = $request->query['shiftoption'];
				$attr['value'] = $this->ShiftOptions->getStartEndTime($shiftOption, 'end')->format('H:i');
				return $attr;
			}
		}
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
					} else {
						$attr['default'] = 'CURRENT';
					} 
				}
			}
		}
		return $attr;
	}

	public function onUpdateFieldLocationInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'location_institution_id', 'name' => $this->aliasField('location_institution_id')];
			$attr['noResults'] = __('No Institutions found');
			$attr['attr'] = ['placeholder' => __('Institution Code or Name')];
			$attr['attr']['value'] = '';
			$attr['attr']['label'] = $this->getMessage('InstitutionShifts.institution');

			$attr['url'] = ['academicperiod' => $this->getSelectedAcademicPeriod(), 'controller' => 'Institutions', 'action' => 'Shifts', 'ajaxInstitutionsAutocomplete'];
			
			$institutionId = $this->Session->read('Institution.Institutions.id');

			if($request->data){
				$data = $request->data[$this->alias()];
				if (($data['location'] == 'CURRENT') || (!$data['location'])){
					$attr['type'] = 'hidden';
					//$institutionId = $this->Session->read('Institution.Institutions.id');
					$attr['value'] = $institutionId;
				} else {
					if($action == 'edit') {
						if ($request->is(['post', 'put']) && !empty($data['location_institution_id'])) {
							$Institutions = TableRegistry::get('Institution.Institutions');
							$entity = $Institutions->findById($data['location_institution_id'])->first(); 
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
							$attr['type'] = 'hidden';
							$attr['value'] = '';
						}
					}
				} else {
					$attr['type'] = 'hidden';
					$attr['value'] = $institutionId;
				}
			}
		}
		return $attr;
	}

	public function addEditOnChangeShiftOption(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		$request = $this->request;
		unset($request->query['shiftoption']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('shift_option_id', $request->data[$this->alias()])) {
					$request->query['shiftoption'] = $request->data[$this->alias()]['shift_option_id'];
				}
			}
		}
	}

	public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		$request = $this->request;
		unset($request->query['toggle']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['toggle'] = $request->data[$this->alias()]['academic_period_id'];
				}
			}
		}
	}

	public function addEditOnChangeLocation(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra) {
		//unset($data[$this->alias()]['location_institution_id']);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
	{
    	if (!$entity->isNew()) { //this logic is for edit operation need to store the previous occupier so then the shift_type can be updated later on afterSave.
    		$previousOccupier = $this->findById($entity->id)->toArray()[0]->location_institution_id;			
    		$this->Session->write('Institution.Shifts.previousOccupier', $previousOccupier);		
    	}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) 
	{
		if ($this->AcademicPeriods->getCurrent() == $entity->academic_period_id) { //if the one that being added / edited is the current academic period
			
			$owner = $entity->institution_id;
			$occupier = $entity->location_institution_id;

			if ($owner == $occupier) {
				$ownerEqualOccupier = true;
			} else {
				$ownerEqualOccupier = false;
			}

			if ((!$ownerEqualOccupier) && (!$entity->isNew())){
				$shiftType = 0;
				$ownerOwnedShift = $this->find()
									->where([
										$this->aliasField('institution_id').' = '.$owner,
										$this->aliasField('location_institution_id').' != '.$owner,
										$this->aliasField('academic_period_id').' = '.$entity->academic_period_id
									])
									->count();

				if ($ownerOwnedShift > 1) {
					$shiftType = self::MULTIPLE_OWNER;
				} elseif ($ownerOwnedShift == 1) {
					$shiftType = self::SINGLE_OWNER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $owner]);
			}

			if (!$ownerEqualOccupier) {	
				$shiftType = 0;
				$occupierOccupiedShift = $this->find()
										->where([
											$this->aliasField('institution_id').' != '.$occupier,
											$this->aliasField('location_institution_id').' = '.$occupier,
											$this->aliasField('academic_period_id').' = '.$entity->academic_period_id
										])
										->count();

				if ($occupierOccupiedShift > 1) {
					$shiftType = self::MULTIPLE_OCCUPIER;
				} elseif ($occupierOccupiedShift == 1) {
					$shiftType = self::SINGLE_OCCUPIER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
			}
			

			if (!$entity->isNew()) { //this logic is for edit operation need to updated the previous occupier shift_type.
				$shiftType = 0;
	    		$previousOccupier = $this->Session->read('Institution.Shifts.previousOccupier');

	    		$previousOccupierOccupiedShift = $this->find()
												->where([
													$this->aliasField('institution_id').' != '.$previousOccupier,
													$this->aliasField('location_institution_id').' = '.$previousOccupier,
													$this->aliasField('academic_period_id').' = '.$entity->academic_period_id
												])
												->count();

				if ($previousOccupierOccupiedShift > 1) {
					$shiftType = self::MULTIPLE_OCCUPIER;
				} elseif ($previousOccupierOccupiedShift == 1) {
					$shiftType = self::SINGLE_OCCUPIER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $previousOccupier]);
	    	}
		}
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

	public function checkShiftOccupier($selectedAcademicPeriodOptions)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');

		return $this->find()
					->where([
						'AND' => [
							[$this->aliasField('location_institution_id') . " = " . $institutionId],
							[$this->aliasField('institution_id') . ' != ' . $institutionId],
							[$this->aliasField('academic_period_id') . ' = ' . $selectedAcademicPeriodOptions]
						]
					])
					->count();
	}

	public function getSelectedAcademicPeriod()
	{
		$request = $this->request;
		if (array_key_exists('toggle', $request->query)) {
			$selectedAcademicPeriod = $request->query['toggle'];
		} else {
			$selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
		}
		return $selectedAcademicPeriod;
	}

	public function ajaxInstitutionsAutocomplete(Event $mainEvent, ArrayObject $extra) 
	{
		$this->ControllerAction->autoRender = false;
		$this->controller->autoRender = false;

		if ($this->request->is(['ajax'])) {

			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Institutions = $this->Institutions;

			$term = trim($this->request->query['term']);
			$selectedAcademicPeriod = trim($this->request->query['academicperiod']);
			$search = '%' . $term . '%';

			$query = $Institutions->find('list')
				->where([
					'NOT EXISTS ('.
						$this->find('list')
							->where([
								$this->aliasField('institution_id').' = '.$Institutions->aliasField('id'),
								$this->aliasField('institution_id').' != '.$this->aliasField('location_institution_id'),
								$this->aliasField('academic_period_id') . ' = ' . $selectedAcademicPeriod
							])
					.')',
					$Institutions->aliasField('name') . ' LIKE ' => $search,
					$Institutions->aliasField('id').' IS NOT ' => $institutionId
				]);

			$list = $query->toArray();
			
			$data = [];
			foreach ($list as $id => $value) {
				$label = $value;
				$data[] = ['label' => $label, 'value' => $id];
			}

			echo json_encode($data);
			return true;
		}
	}

	public function setupFields(Entity $entity) {
		$this->field('academic_period_id', ['type' => 'select']);
		$this->field('shift_option_id', ['type' => 'select']);
		$this->field('start_time', ['type' => 'time']);
		$this->field('end_time', ['type' => 'time']);
		$this->field('location', [
			'after' => 'end_time', 
			'attr' => [
				'label' => $this->getMessage('InstitutionShifts.occupier')
			]
		]);
		$this->field('location_institution_id', ['after' => 'location']);
	}
}
