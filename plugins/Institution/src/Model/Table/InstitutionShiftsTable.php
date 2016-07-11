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
		$validator = parent::validationDefault($validator);

		$validator
 	        ->add('start_time', 'ruleCompareDate', [
		            'rule' => ['compareDate', 'end_time', true]
	    	    ])
 	        ->add('start_time', 'ruleCheckShiftAvailable', [
					'rule' => ['checkShiftAvailable'],
        		])
 	        ->add('institution_name', 'ruleCheckLocationInstitutionId', [
 	        		'rule' => ['checkInstitutionLocation']
 	        	])
 	        ->requirePresence('location');

		return $validator;
	}

	//this is to remove virtual field validation during duplicating institution shift
	public function validationRemoveLocation(Validator $validator)
	{
		$validator->requirePresence('location', false);
		return $validator;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['ControllerAction.Model.ajaxInstitutionsAutocomplete'] = 'ajaxInstitutionsAutocomplete';
    	$events['ControllerAction.Model.replicate'] = 'replicate';
    	return $events;
    }

     public function beforeAction(Event $event, ArrayObject $extra) {
     	$action = $this->action;
        switch ($action) {
            case 'replicate':
                $extra['config']['form'] = true;
                $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit', 'order' => 5];
                break;
            default:
                break;
        }
 	}

    public function replicate() //logic to handle replicate action.
    {
    	$request = $this->request;
    	$previousAcademicPeriod = $request->query['prevPeriod'];
	    $latestAcademicPeriod = $this->AcademicPeriods->getLatest();

	    $shiftDetails = $this->Institutions->getViewShiftDetail($this->Session->read('Institution.Institutions.id'), $previousAcademicPeriod);

	    if (($request->is(['post', 'put'])) && ($request->data['submit'] == 'save')) { //when replicate form is submitted

    		foreach ($shiftDetails as $row) {
				$newShift = [
					'start_time' => $row->StartTime,
					'end_time' => $row->EndTime,
					'academic_period_id' => $latestAcademicPeriod,
					'institution_id' => $row->OwnerId,
					'location_institution_id' => $row->OccupierId,
					'shift_option_id' => $row->ShiftId
				];
				$data = $this->newEntity($newShift, ['validate' => 'RemoveLocation']); //remove validation of location during replication.
				$this->save($data);
			}

			$url = $this->url('index');
			unset($url['prevPeriod']);
			$url['replicate'] = 1;
			return $this->controller->redirect($url);

    	} else {

    		$this->Alert->warning(__("Should the system replicate the existing shifts for the latest academic period?."), ['type' => 'text']);

	    	$this->fields = []; //remove all the fields

	    	$this->field('previous_academic_period_id', [
	    		'type' 	=> 'readonly',
	    		'attr' => [
					'value' => [$this->AcademicPeriods->get($previousAcademicPeriod)->name]
				]]);

	    	$this->field('shift_details', [
			 	'type' => 'element',
	            'element' => 'Institution.Shifts/details',
	            'data' => $shiftDetails,
	            'visible' => true
	       	]);

	    	$this->field('latest_academic_period_id', [
	    		'type' 	=> 'readonly',
	    		'attr' => [
					'value' => [$this->AcademicPeriods->get($latestAcademicPeriod)->name]
				]]);

    	}
    	
    	$this->controller->set('data', $this->newEntity());//to remove warning that data is not exist.
    	
    	return true;   
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{

		$academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
		$institutionId = $this->Session->read('Institution.Institutions.id');

		$extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod();
		
		$extra['elements']['control'] = [
			'name' => 'Institution.Shifts/controls', 
			'data' => [
				'periodOptions'=> $academicPeriodOptions,
				'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions']
			],
			'order' => 3
		];

		//logic to remove 'add' button if the institution has received shift from other based on the academic period
		$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
		if ($this->isOccupier($institutionId, $extra['selectedAcademicPeriodOptions'])) { //if occupier, then remove the 'add' button
			unset($toolbarButtonsArray['add']);
		}
		$extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

		$this->field('location');
		$this->setFieldOrder([
			'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'location', 'location_institution_id'
		]);

		//this logic is to check whether need to offer user to replicate shift for the latest academic period
		$latestAcademicPeriod = $this->AcademicPeriods->getLatest();

		if ($extra['selectedAcademicPeriodOptions'] == $latestAcademicPeriod) { //if latest acad period selected
			$isOwner = false;
			$isOwner = $this->isOwner($institutionId, $latestAcademicPeriod);
			
			if (!$isOwner) { // if not, perhaps the latest acad = current acad. then need to check the previous period.
				$prevPeriodWithShift = $this->getPreviousPeriodWithShift($institutionId, $latestAcademicPeriod); //if not this period, we should check the prev period. //ensure that it has shift previously

				if ($prevPeriodWithShift) { //if owner of previous academic period
					$isOwner = true;
				}
			}

			if ($isOwner) { //all the offer shift replication is for owner only

				$request = $this->request;

				if (isset($request->query['replicate'])) { //if has replicate variable, it means replication has been offered before.

					if ($request->query['replicate']) { //if replication accepted / success
						$this->Alert->success(__("Shifts has been successfully replicated"), ['type' => 'text']);
					} else { //if user did not choose to replicate
						$this->Alert->warning(__("Replication was not chosen, please setup the shifts manually."), ['type' => 'text']);
					}

				} else { //no offer of replication yet. then redirect to replicate page

					if (!($this->checkShiftExist($institutionId, $latestAcademicPeriod))) { //if there is no shift exist for the latest academic period
						
						if (!empty($prevPeriodWithShift)) { //if has, then get the acad period
							
							$prevPeriodWithShift = $prevPeriodWithShift->academicPeriodId;
							$url = $this->url('replicate');
							$url['prevPeriod'] = $prevPeriodWithShift;
							$event->stopPropagation();
							return $this->controller->redirect($url); //redirect to page that offer shift application based on the previous academic period

						}
					}
				}
			}
		}
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

	public function addBeforeAction(Event $event) {
		unset($this->request->query['replicate']);
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
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

		if ($this->isOccupier($institutionId, $this->getSelectedAcademicPeriod())) { //if occupier, then remove the 'edit / remove' button
			unset($toolbarButtonsArray['edit']);
			unset($toolbarButtonsArray['remove']);
		}
		$extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

		$this->field('location');

		$this->setFieldOrder([
			'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'location', 'location_institution_id'
		]);
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
		if (($this->action == 'view') || ($this->action == 'index')) {
			if ($this->Session->read('Auth.User.super_admin')) { //for super admin, link to institution will be generated.
				return $event->subject()->Html->link($entity->institution->name , [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'dashboard',
					$entity->institution_id
				]);
			} else {
				return $entity->institution->name;
			}
		}
	}

	public function onGetLocationInstitutionId(Event $event, Entity $entity) {
		if (($this->action == 'view') || ($this->action == 'index')) {
			if ($this->Session->read('Auth.User.super_admin')) { //for super admin, link to institution will be generated.
				return $event->subject()->Html->link($entity->location_institution->name , [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'dashboard',
					$entity->location_institution_id
				]);
			} else {
				return $entity->location_institution->name;
			}
		}
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

		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedAcademicPeriod = $this->getSelectedAcademicPeriod();

		if (!empty($selectedAcademicPeriod)) { 
			//this is default condition to get the unused shift of institution on specific academic period.
			$whereConditions = [
				$this->aliasField('shift_option_id').' = '.$this->ShiftOptions->aliasField('id'),
				$this->aliasField('institution_id').' = '.$institutionId,
				$this->aliasField('academic_period_id') . ' = ' . $selectedAcademicPeriod
			];

			if ($action == "edit") {

				$editInstitutionShiftId = $request->params['pass']['1']; //get the current institution shift id.
				$whereConditions[] = $this->aliasField('id') . " != " . $editInstitutionShiftId; //additional condition to exclude current institution shift id while editing.
			}

			$availableShiftOptions = $this->ShiftOptions->find('list')
										->where([
											'NOT EXISTS ('.
												$this->find('list')
													->where($whereConditions)
											.')'
										]);

			if ($availableShiftOptions->count() < 1) { //when all shift has been used, then show warning.
				$this->Alert->warning(__("All shifts has been used for the selected academic period"), ['type' => 'text']);
			}

			$attr['options'] = $availableShiftOptions->toArray();
			$attr['onChangeReload'] = 'changeShiftOption';
		} else { //to anticipate academic period being change to -- select --
			$attr['options'] = '';
		}
		return $attr;
	}

	public function onUpdateFieldStartTime(Event $event, array $attr, $action, Request $request) {
		if ($request->data) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
			if ($submit == 'changeShiftOption') {
				if (!empty($request->query['shiftoption'])) {
					$shiftOption = $request->query['shiftoption'];
					$attr['value'] = $this->ShiftOptions->getStartEndTime($shiftOption, 'start')->format('H:i');
					return $attr;
				}
			}
		}
	}

	public function onUpdateFieldEndTime(Event $event, array $attr, $action, Request $request) {
		if ($request->data) {
			$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
			if ($submit == 'changeShiftOption') {
				if (!empty($request->query['shiftoption'])) {
					$shiftOption = $request->query['shiftoption'];
					$attr['value'] = $this->ShiftOptions->getStartEndTime($shiftOption, 'end')->format('H:i');
					return $attr;
				}
			}
		}
	}

	public function onUpdateFieldLocation(Event $event, array $attr, $action, $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = ['CURRENT' => __('This Institution'), 'OTHER' => __('Other Institution')];
			$attr['onChangeReload'] = 'changeLocation';

			$attr['default'] = 'CURRENT'; //set the default selected occupier as Current Institution

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
		unset($request->query['period']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
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

			//owner need to be updated for all operation
			$shiftType = 0;
			$ownerOwnedShift = $this->find()
								->where([
									$this->aliasField('institution_id').' = '.$owner,
									$this->aliasField('academic_period_id').' = '.$entity->academic_period_id
								])
								->count();

			if ($ownerOwnedShift > 1) {
				$shiftType = self::MULTIPLE_OWNER;
			} else if ($ownerOwnedShift == 1) {
				$shiftType = self::SINGLE_OWNER;
			}
			$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $owner]);

			//update new occupier
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
				} else if ($occupierOccupiedShift == 1) {
					$shiftType = self::SINGLE_OCCUPIER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
			}

			if (!$entity->isNew()) { //this logic is for edit operation need to update the previous occupier.
				$shiftType = 0;
	    		$previousOccupier = $this->Session->read('Institution.Shifts.previousOccupier');

	    		if (($owner != $previousOccupier) || ($owner == $occupier)) { //if prev occupier = owner then no need to update because owner already updated above.
	    			$previousOccupierOccupiedShift = $this->find()
													->where([
														$this->aliasField('institution_id').' != '.$previousOccupier,
														$this->aliasField('location_institution_id').' = '.$previousOccupier,
														$this->aliasField('academic_period_id').' = '.$entity->academic_period_id
													])
													->count();

					if ($previousOccupierOccupiedShift > 1) {
						$shiftType = self::MULTIPLE_OCCUPIER;
					} else if ($previousOccupierOccupiedShift == 1) {
						$shiftType = self::SINGLE_OCCUPIER;
					}
					$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $previousOccupier]);
	    		}
	    	}
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		if ($this->AcademicPeriods->getCurrent() == $entity->academic_period_id) { //update of shift_type only if deletion is done on the current academic period shift
			$owner = $entity->institution_id;
			$occupier = $entity->location_institution_id;

			//update owner
			$shiftType = 0;
			$ownerOwnedShift = $this->find()
								->where([
									$this->aliasField('institution_id').' = '.$owner,
									$this->aliasField('academic_period_id').' = '.$entity->academic_period_id
								])
								->count();

			if ($ownerOwnedShift > 1) {
				$shiftType = self::MULTIPLE_OWNER;
			} else if ($ownerOwnedShift == 1) {
				$shiftType = self::SINGLE_OWNER;
			}
			$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $owner]);

			//update occupier if not equal to owner
			if ($owner != $occupier) {	
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
				} else if ($occupierOccupiedShift == 1) {
					$shiftType = self::SINGLE_OCCUPIER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
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
	// public function createInstitutionDefaultShift($institutionsId, $academicPeriodId){
	// 	$data = $this->getShifts($institutionsId, $academicPeriodId);

	// 	if (empty($data)) {			
	// 		$schoolAcademicPeriod = $this->AcademicPeriods->get($academicPeriodId);

	// 		$ConfigItem = TableRegistry::get('ConfigItems');
	// 		$settingStartTime = $ConfigItem->value('start_time');
	// 		$hoursPerDay = intval($ConfigItem->value('hours_per_day'));
	// 		if ($hoursPerDay > 1) {
	// 			$endTimeStamp = strtotime('+' . $hoursPerDay . ' hours', strtotime($settingStartTime));
	// 		} else {
	// 			$endTimeStamp = strtotime('+' . $hoursPerDay . ' hour', strtotime($settingStartTime));
	// 		}
	// 		$endTime = date('h:i A', $endTimeStamp);

	// 		$defaultShift = [
	// 			'name' => __('Default') . ' ' . __('Shift') . ' ' . $schoolAcademicPeriod->name,
	// 			'academic_period_id' => $academicPeriodId,
	// 			'start_time' => $settingStartTime,
	// 			'end_time' => $endTime,
	// 			'institution_id' => $institutionsId,
	// 			'location_institution_id' => $institutionsId,
	// 			'location_institution_name' => 'Institution Site Name (Shift Location)'
	// 		];

	// 		$data = $this->newEntity();
	// 		$data = $this->patchEntity($data, $defaultShift);
	// 		$this->save($data);
	// 	}
	// }

	public function checkShiftExist($institutionId, $academicPeriodId) //if not exist then return false, else return array of shifts
	{
		$query = $this->find()
					->where([
						'academic_period_id' => $academicPeriodId,
						'OR' => [
							'location_institution_id' => $institutionId,
							'institution_id' => $institutionId
						]
					]);
		if ($query->count() > 0) {
			return $query->toArray();
		} else {
			return false;
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		
		if ($this->action == 'replicate') {
			$url = $this->url('index');
			$url['replicate'] = 0; //when member cancel, then redirect them back to latest academic period but no need to redirect back to replicate page anymore.
			unset($url['prevPeriod']);
			
			$buttons[1] = [ //for cancel button, create URL to redirect back to index page
				'name' => $buttons[1]['name'],
				'attr' => ['class' => 'btn btn-outline btn-cancel', 'escape' => false], //follow the original attribut of the cancel button
				'url' => $url				
			];
		}
	}

	public function getPreviousPeriodWithShift($institutionId, $latestAcademicPeriod)
	{
		return $query = $this->find()
				->innerJoinWith('AcademicPeriods')
				->select(['academicPeriodId' => 'AcademicPeriods.id'])
				->where([
					'OR' => [
						//'location_institution_id' => $institutionId,
						'institution_id' => $institutionId
					],
					'AcademicPeriods.id'. ' <> ' .$latestAcademicPeriod
				])
				->order(['start_date DESC'])
				->distinct()
				->first();
	}

	// public function getShifts($institutionsId, $periodId) {
	// 	$conditions = [
	// 		'institution_id' => $institutionsId,
	// 		'academic_period_id' => $periodId
	// 	];
	// 	$query = $this->find('all');
	// 	// $query->contain(['Institutions', 'LocationInstitutions', 'AcademicPeriods']);
	// 	$query->where($conditions);
	// 	$data = $query->toArray();
	// 	return $data;
	// }

	//this is to be called by institution class to get the available shift.
	public function getShiftOptions($institutionsId, $periodId) 
	{
		$query = $this->find()
				->innerJoinWith('ShiftOptions')
				->innerJoinWith('Institutions')
				->select([
					'institutionShiftId' => 'InstitutionShifts.id',
					'institutionId' => 'Institutions.id',
					'institutionCode' => 'Institutions.code',
					'institutionName' => 'Institutions.name',
					'shiftOptionName' => 'ShiftOptions.name'
				])
				->where([
						'location_institution_id' => $institutionsId,
						'academic_period_id' => $periodId
				]);

		$data = $query->toArray();

		$list = [];
		foreach ($data as $key => $obj) {

			if ($obj->institutionId == $institutionsId) { //if the shift owned by itself, then no need to show the shift owner
				$shiftName = $obj->shiftOptionName;
			} else {
				$shiftName = $obj->institutionCode . " - " . $obj->institutionName . " - " . $obj->shiftOptionName;
			}

			$list[$obj->institutionShiftId] = $shiftName;
		}

		return $list;
	}

	//to check whether an institution is occupier or not
	public function isOccupier($institutionId, $academicPeriod)
	{
		return $this->find()
					->where([
						'AND' => [
							[$this->aliasField('location_institution_id') . " = " . $institutionId],
							[$this->aliasField('institution_id') . ' != ' . $institutionId],
							[$this->aliasField('academic_period_id') . ' = ' . $academicPeriod]
						]
					])
					->count();
	}

	//to check whether an institution is owner or not
	public function isOwner($institutionId, $academicPeriod)
	{
		return $this->find()
					->where([
						'AND' => [
							[$this->aliasField('institution_id') . ' = ' . $institutionId],
							[$this->aliasField('academic_period_id') . ' = ' . $academicPeriod]
						]
					])
					->count();
	}

	public function getOwnerList($selectedAcademicPeriodOptions)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');

		return $this->find()
					->select([
						'institution_id'
					])
					->where([
						'AND' => [
							[$this->aliasField('location_institution_id') . ' = ' . $institutionId],
							[$this->aliasField('academic_period_id') . ' = ' . $selectedAcademicPeriodOptions]
						]
					])
					->distinct()
					->toArray();
	}

	public function getSelectedAcademicPeriod()
	{
		$request = $this->request;
		if (array_key_exists('period', $request->query)) {
			$selectedAcademicPeriod = $request->query['period'];
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
								'OR' => [ //if owner has shift for themself or for others
									$this->aliasField('institution_id').' != '.$this->aliasField('location_institution_id'),
									$this->aliasField('institution_id').' = '.$this->aliasField('institution_id')
								],
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
