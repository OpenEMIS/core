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
		$this->belongsTo('ShiftOptions', 			['className' => 'Institution.ShiftOptions']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions']);
		$this->belongsTo('LocationInstitutions',	['className' => 'Institution.LocationInstitutions']);

		$this->hasMany('InstitutionClasses', 		['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_shift_id']);

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
    	// $events['ControllerAction.Model.replicate'] = 'replicate';
    	return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
     	// //logic to cater for replicate action
     	// $action = $this->action;
      // 	switch ($action) {
      //       case 'replicate':
      //           $extra['config']['form'] = true;
      //           $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit', 'order' => 5];
      //           break;
      //       default:
      //           break;
      //   }
 	}

 	public function afterAction(Event $event, ArrayObject $extra)
 	{
 		if ($this->action == 'remove') {
 			$shiftName = $this->ShiftOptions->get($extra['entity']->shift_option_id); //since institution_shifts does not have field 'name', then need to pass shift name that will be use on remove action
 			$extra['entity']->name = $shiftName->name;
 		}
 	}

 	// To disable the replicate logic for now, because we need to finalise the trigger point for the replication to happen
 	// Proposed implementation is to trigger from new Academic Period
   //  public function replicate() //logic to handle replicate action.
   //  {
   //  	$request = $this->request;
   //  	$previousAcademicPeriod = $request->query['prevPeriod'];
	  //   $latestAcademicPeriod = $this->AcademicPeriods->getLatest();

	  //   $shiftDetails = $this->Institutions->getViewShiftDetail($this->Session->read('Institution.Institutions.id'), $previousAcademicPeriod);

	  //   if (($request->is(['post', 'put'])) && ($request->data['submit'] == 'save')) { //when replicate form is submitted

   //  		foreach ($shiftDetails as $row) {
			// 	$newShift = [
			// 		'start_time' => $row->StartTime,
			// 		'end_time' => $row->EndTime,
			// 		'academic_period_id' => $latestAcademicPeriod,
			// 		'institution_id' => $row->OwnerId,
			// 		'location_institution_id' => $row->OccupierId,
			// 		'shift_option_id' => $row->ShiftId
			// 	];
			// 	$data = $this->newEntity($newShift, ['validate' => 'RemoveLocation']); //remove validation of location during replication.
			// 	$this->save($data);
			// }

			// $url = $this->url('index');
			// unset($url['prevPeriod']);
			// $url['replicate'] = 1;
			// return $this->controller->redirect($url);

   //  	} else {

   //  		$this->Alert->warning('InstitutionShifts.replicateShifts');

	  //   	$this->fields = []; //remove all the fields

	  //   	$this->field('previous_academic_period_id', [
	  //   		'type' 	=> 'readonly',
	  //   		'attr' => [
			// 		'value' => [$this->AcademicPeriods->get($previousAcademicPeriod)->name]
			// 	]]);

	  //   	$this->field('shift_details', [
			//  	'type' => 'element',
	  //           'element' => 'Institution.Shifts/details',
	  //           'data' => $shiftDetails,
	  //           'visible' => true
	  //      	]);

	  //   	$this->field('latest_academic_period_id', [
	  //   		'type' 	=> 'readonly',
	  //   		'attr' => [
			// 		'value' => [$this->AcademicPeriods->get($latestAcademicPeriod)->name]
			// 	]]);

   //  	}

   //  	$this->controller->set('data', $this->newEntity());//to remove warning that data is not exist.

   //  	return true;
   //  }

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

		$this->field('institution_id', ['type' => 'integer']); //this is to show owner (set in label table), by default the default is hidden

		$this->setFieldOrder([
			'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'institution_id', 'location_institution_id'
		]);

		//this logic is to check whether need to offer user to replicate shift for the latest academic period
		// $latestAcademicPeriod = $this->AcademicPeriods->getLatest();

		// if ($extra['selectedAcademicPeriodOptions'] == $latestAcademicPeriod) { //if latest acad period selected
		// 	$isOwner = false;
		// 	$isOwner = $this->isOwner($institutionId, $latestAcademicPeriod);

		// 	if (!$isOwner) { // if not, perhaps the latest acad = current acad. then need to check the previous period.
		// 		$prevPeriodWithShift = $this->getPreviousPeriodWithShift($institutionId, $latestAcademicPeriod); //if not this period, we should check the prev period. //ensure that it has shift previously
		// 		if ($prevPeriodWithShift) { //if owner of previous academic period
		// 			$isOwner = true;
		// 		}
		// 	}

		// 	if ($isOwner) { //all the offer shift replication is for owner only
		// 		$request = $this->request;

		// 		if (isset($request->query['replicate'])) { //if has replicate variable, it means replication has been offered before.

		// 			if ($request->query['replicate']) { //if replication accepted / success
		// 				$this->Alert->success('InstitutionShifts.replicateShiftsSuccess');
		// 			} else { //if user did not choose to replicate
		// 				$this->Alert->warning('InstitutionShifts.replicateShiftsNotChosen');
		// 			}

		// 		} else { //no offer of replication yet. then redirect to replicate page

		// 			if (!($this->checkShiftExist($institutionId, $latestAcademicPeriod))) { //if there is no shift exist for the latest academic period

		// 				if (!empty($prevPeriodWithShift)) { //if has, then get the acad period

		// 					$prevPeriodWithShift = $prevPeriodWithShift->academicPeriodId;
		// 					$url = $this->url('replicate');
		// 					$url['prevPeriod'] = $prevPeriodWithShift;
		// 					$event->stopPropagation();
		// 					return $this->controller->redirect($url); //redirect to page that offer shift application based on the previous academic period

		// 				}
		// 			}
		// 		}
		// 	}
		// }
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
		if (($entity->institution->id) != ($entity->location_institution->id)) {
			if (($entity->institution->id) != $currentInstitutionId) {
				unset($buttons['remove']);
				unset($buttons['edit']);
			}
		}

		return $buttons;
	}

	//public function addBeforeAction(Event $event)
	//{
		// unset($this->request->query['replicate']);
	//}

	public function addEditBeforeAction(Event $event)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');

		if ($this->isOccupier($institutionId, $this->getSelectedAcademicPeriod())) { //if occupier, then redirect from trying to access add/edit page
			$url = $this->url('index');
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
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

		$this->field('institution_id', ['type' => 'integer']); //this is to show owner (set in label table), by default the default is hidden

		$this->setFieldOrder([
			'academic_period_id', 'shift_option_id', 'start_time', 'end_time', 'institution_id', 'location_institution_id'
		]);
	}

/******************************************************************************************************************
**
** addEdit action methods
**
******************************************************************************************************************/

	public function onGetShiftOptionId(Event $event, Entity $entity)
	{
		if ($this->action == 'index') {
			$htmlHelper = $event->subject()->Html;
			$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Shifts', 'view'];
			$url[] = $entity->id;
			return $htmlHelper->link($entity->shift_option->name, $url);
		}
	}

	public function onGetInstitutionId(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->institution->name , [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => 'dashboard',
			$entity->institution_id
		]);
	}

	public function onGetLocationInstitutionId(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->location_institution->name , [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => 'dashboard',
			$entity->location_institution_id
		]);
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) 
	{
		$academicPeriodOptions = $this->AcademicPeriods->getYearList();
		$attr['type'] = 'readonly';
			
		if ($action == 'add') { //set the academic period to thecurrent and readonly
			$attr['attr']['value'] = $academicPeriodOptions[$this->getSelectedAcademicPeriod()];
			$attr['value'] = $this->getSelectedAcademicPeriod();
		} else if ($action == 'edit') {
			$attr['attr']['value'] = $academicPeriodOptions[$attr['entity']->academic_period_id];
			$attr['value'] = $attr['entity']->academic_period_id;
		}

		return $attr;
	}

	public function onUpdateFieldShiftOptionId(Event $event, array $attr, $action, $request)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');
		
		//this is default condition to get the all shift.
		$options = $this->ShiftOptions
			->find('list')
			->find('visible')
			->find('order');

		if ($action == 'add') {
			$selectedAcademicPeriod = $this->getSelectedAcademicPeriod();			
			if (!empty($selectedAcademicPeriod)) {
				//during add then need to exclude used shifts based on school and academic period
				$options = $options
					->find('availableShifts', ['institution_id' => $institutionId, 'academic_period_id' => $selectedAcademicPeriod])
					->toArray();
				$attr['options'] = $options;
				$attr['onChangeReload'] = 'changeShiftOption';

				if (empty($options)) {
					$this->Alert->warning('InstitutionShifts.allShiftsUsed');
				}
			}
		} else if ($action == 'edit') {
			//for edit since it is read only, then no need to put conditions and get the value from the options populated.
			$options = $options->toArray();
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $options[$attr['entity']->shift_option_id];
			$attr['value'] = $attr['entity']->shift_option_id;
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

	public function onUpdateFieldLocation(Event $event, array $attr, $action, $request)
	{
		$attr['options'] = ['CURRENT' => __('This Institution'), 'OTHER' => __('Other Institution')];
		if ($action == 'add') {
			$attr['onChangeReload'] = 'changeLocation';
			$attr['default'] = 'CURRENT'; //set the default selected location as Current Institution
			$attr['select'] = false;
		} else if ($action == 'edit') {
			$attr['type'] = 'hidden';
			if ($attr['entity']->institution_id != $attr['entity']->location_institution_id) {
				$attr['attr']['value'] = $attr['options']['OTHER'];
				$attr['value'] = 'OTHER';
			} else {
				$attr['attr']['value'] = $attr['options']['CURRENT'];
				$attr['value'] = 'CURRENT';
			}
		}
		return $attr;
	}

	public function onUpdateFieldLocationInstitutionId(Event $event, array $attr, $action, $request)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');

		if ($action == 'add') {

			$attr['type'] = 'hidden'; //default is hidden as location default also "CURRENT"
			$attr['value'] = $institutionId; //default is current institution ID

			if($request->data){
				$data = $request->data[$this->alias()];
				if ($data['location'] == 'OTHER') {
					$attr['type'] = 'autocomplete';
					$attr['target'] = ['key' => 'location_institution_id', 'name' => $this->aliasField('location_institution_id')];
					$attr['noResults'] = __('No Institutions found');
					$attr['attr'] = ['placeholder' => __('Institution Code or Name')];
					$attr['attr']['value'] = '';
					$attr['url'] = ['academicperiod' => $this->getSelectedAcademicPeriod(), 'controller' => 'Institutions', 'action' => 'Shifts', 'ajaxInstitutionsAutocomplete'];
				}
			}
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
			$Institutions = TableRegistry::get('Institution.Institutions');
			$occupier = $Institutions->findById($attr['entity']->location_institution_id)->first();
			$attr['attr']['value'] = $occupier->name;
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
				$shiftType = $this->Institutions->MULTIPLE_OWNER;
			} else if ($ownerOwnedShift == 1) {
				$shiftType = $this->Institutions->SINGLE_OWNER;
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
					$shiftType = $this->Institutions->MULTIPLE_OCCUPIER;
				} else if ($occupierOccupiedShift == 1) {
					$shiftType = $this->Institutions->SINGLE_OCCUPIER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
			}
		}
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
	{
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
				$shiftType = $this->Institutions->MULTIPLE_OWNER;
			} else if ($ownerOwnedShift == 1) {
				$shiftType = $this->Institutions->SINGLE_OWNER;
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
					$shiftType = $this->Institutions->MULTIPLE_OCCUPIER;
				} else if ($occupierOccupiedShift == 1) {
					$shiftType = $this->Institutions->SINGLE_OCCUPIER;
				}
				$this->Institutions->updateAll(['shift_type' => $shiftType], ['id' => $occupier]);
			}
		}
	}

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

	//public function onGetFormButtons(Event $event, ArrayObject $buttons) {

		// if ($this->action == 'replicate') {
		// 	$url = $this->url('index');
		// 	$url['replicate'] = 0; //when member cancel, then redirect them back to latest academic period but no need to redirect back to replicate page anymore.
		// 	unset($url['prevPeriod']);

		// 	$buttons[1] = [ //for cancel button, create URL to redirect back to index page
		// 		'name' => $buttons[1]['name'],
		// 		'attr' => ['class' => 'btn btn-outline btn-cancel', 'escape' => false], //follow the original attribut of the cancel button
		// 		'url' => $url
		// 	];
		// }
	//}

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

	private function getSelectedAcademicPeriod()
	{
		$request = $this->request;
		$selectedAcademicPeriod = '';

		if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
			if (array_key_exists('period', $request->query)) {
				$selectedAcademicPeriod = $request->query['period'];
			} else {
				$selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
			}
		} else if ($this->action == 'add') {
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

	private function setupFields(Entity $entity) {
		$this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
		$this->field('shift_option_id', ['type' => 'select', 'entity' => $entity]);
		$this->field('start_time', ['type' => 'time']);
		$this->field('end_time', ['type' => 'time']);
		$this->field('location', [
			'after' => 'end_time',
			'visible' => [
				'index' => false, 'view' => false, 'add' => true, 'edit' => true
			],
			'entity' => $entity
		]);
		$this->field('location_institution_id', [
			'after' => 'institution_id',
			'entity' => $entity
		]);
	}
}
