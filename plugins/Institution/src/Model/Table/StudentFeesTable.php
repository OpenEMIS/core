<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18N\Time;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class StudentFeesTable extends AppTable {
	public $institutionId = 0;
	private $_selectedAcademicPeriodId = -1;
	private $_academicPeriodOptions = [];
	private $_selectedEducationGradeId = -1;
	private $_gradeOptions = [];

	protected $InstitutionSiteFeeEntity = null;


/******************************************************************************************************************
**
** CakePHP default methods
**
******************************************************************************************************************/
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteFees', ['className' => 'Institution.InstitutionSiteFees']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

		/**
		 * Shortcuts
		 */
		$this->AcademicPeriods = $this->InstitutionSiteFees->AcademicPeriods;
		$this->InstitutionSiteGrades = $this->InstitutionSiteFees->Institutions->InstitutionSiteGrades;	
		$this->InstitutionGradeStudents = $this->InstitutionSiteFees->Institutions->InstitutionGradeStudents;

	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {
    	$this->ControllerAction->field('amount', ['type' => 'float', 					'visible' => ['index'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('payment_date', ['type' => 'date', 				'visible' => ['edit'=>true]]);
    	$this->ControllerAction->field('comments', ['type' => 'string', 				'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	// $this->ControllerAction->field('security_user_id', ['type' => 'select', 		'visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('institution_site_fee_id', ['type' => 'select', 	'visible' => ['edit'=>true]]);

    	$this->ControllerAction->field('openemis_no', ['type' => 'string', 				'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('name', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('total', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('outstanding', ['type' => 'string', 				'visible' => ['index'=>true]]);

		$session = $this->request->session();
		$this->institutionId = $session->read('Institutions.id');

		// $ConfigItems = TableRegistry::get('ConfigItems');
  //   	$currency = $ConfigItems->value('currency');
  //   	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $currency, 'visible' => ['view'=>true, 'edit'=>true]]);

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		// pr('indexBeforeAction');die;
		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'name', 'total', 'amount', 'outstanding'
		]);

		$settings['model'] = 'Institution.InstitutionGradeStudents';

		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $this->institutionId
		);
		$academicPeriodOptions = $this->InstitutionSiteFees->Institutions->InstitutionSiteProgrammes->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$institutionId = $this->institutionId;
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId);

		$gradeOptions = $this->InstitutionSiteGrades->getInstitutionSiteGradeOptions($this->institutionId, $this->_selectedAcademicPeriodId);
		$this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId);

		$toolbarElements = [
            ['name' => 'Institution.StudentFees/controls', 
             'data' => [
	            	'academicPeriodOptions'=>$academicPeriodOptions,
	            	'gradeOptions'=>$gradeOptions,
	            ],
	         'options' => []
            ]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
		
		$this->InstitutionSiteFeeEntity = $this->InstitutionSiteFees
			->find()
			->where([
				'InstitutionSiteFees.education_grade_id' => $this->_selectedEducationGradeId,
				'InstitutionSiteFees.academic_period_id' => $this->_selectedAcademicPeriodId,
				'InstitutionSiteFees.institution_site_id' => $this->institutionId
			])
			->first()
			;
		// pr($this->InstitutionSiteFeeEntity);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $paginateOptions) {
		// pr('indexBeforePaginate');die;
		$paginateOptions['finder'] = ['byGrades' => [
			'institution_id'=>$this->institutionId,
			'education_grade_id'=>$this->_selectedEducationGradeId,
			'academic_period_id'=>$this->_selectedAcademicPeriodId
		]];
	// 	$paginateOptions['conditions'][][$this->aliasField('academic_period_id')] = $this->_selectedAcademicPeriodId;
		// $paginateOptions['contain'][] = [
		// $paginateOptions['conditions'][] = [
		// 	'InstitutionGradeStudents.education_grade_id' => $this->_selectedEducationGradeId
		// ];
		// $paginateOptions['conditions'][] = [
		// 				'InstitutionSiteFees.institution_site_id' => $this->institutionId,
		// 				'InstitutionSiteFees.academic_period_id' => $this->_selectedAcademicPeriodId,
		// 				'InstitutionSiteFees.education_grade_id' => $this->_selectedEducationGradeId
		// 			];
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
    public function addBeforeAction(Event $event) {
   		$this->ControllerAction->setFieldOrder([
			'institution_site_fee_id', 'security_user_id', 'payment_date', 'amount', 'comments'
		]);
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction(Event $event) {
		// if ($model->alias() == 'StudentFees') {
	    	// $this->ControllerAction->models[$model->alias()]['actions'][] = 'add';
	    	// $this->ControllerAction->defaultActions = $this->ControllerAction->models[$model->alias()]['actions'];

			// $this->ControllerAction->model($model->alias(), $this->ControllerAction->models[$model->alias()]['actions']);
	  //   	pr($this->ControllerAction->models[$model->alias()]['actions']);
		// }

		// $this->ControllerAction->vars()['toolbarButtons']['edit'] = [
  //                   'url' => [],
  //                   'type' => 'button',
  //                   'label' => '',
  //                   'attr' => [
  //                           'class' => 'btn btn-xs btn-default',
  //                           'data-toggle' => 'tooltip',
  //                           'data-placement' => 'bottom',
  //                           'escape' => '',
  //                           'title' => 'Edit'
  //                       ]
  //               	];
  //   	pr($this->ControllerAction->vars()['toolbarButtons']);
   		// $this->ControllerAction->setFieldOrder([
			// 'institution_site_fee_id', 'security_user_id', 'payment_date', 'amount', 'comments'
		// ]);
	}

	private function setupViewEdit($id) {
		$this->ControllerAction->model('Institution.InstitutionGradeStudents');
		$this->ControllerAction->model->ControllerAction = $this->ControllerAction;
		$entity = $this->ControllerAction->model->get($id, [
			'contain'=> array_merge($this->ControllerAction->model->allAssociations(), [])
		]);

		$idKey = $this->ControllerAction->model->aliasField('id');
		if (is_object($entity)) {
			$this->Session->write($idKey, $id);

			$this->InstitutionSiteFeeEntity = $this->InstitutionSiteFees
				->find()
				->contain('InstitutionSiteFeeTypes.FeeTypes')
				->where([
					'InstitutionSiteFees.education_grade_id' => $entity->education_grade_id,
					'InstitutionSiteFees.academic_period_id' => $entity->academic_period_id,
					'InstitutionSiteFees.institution_site_id' => $entity->institution_id
				])
				->first()
				;

	    	$this->ControllerAction->field('security_user_id', 	['type' => 'string', 'visible' => false]);
	    	$this->ControllerAction->field('institution_id', 	['type' => 'string', 'visible' => false]);

	    	$this->ControllerAction->field('education_grade_id', ['type' => 'string', 'visible' => true]);
	    	$this->ControllerAction->field('education_programme', ['type' => 'string', 'visible' => true]);
	    	$this->ControllerAction->field('openemis_no', ['type' => 'string', 'visible' => true]);
	    	$this->ControllerAction->field('name', ['type' => 'string', 'visible' => true]);
	    	$this->ControllerAction->field('outstanding', ['type' => 'string', 'visible' => true]);

			$ConfigItems = TableRegistry::get('ConfigItems');
	    	$currency = $ConfigItems->value('currency');
	    	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $currency, 'non-editable'=>true, 'visible' => ['view'=>true, 'edit'=>true]]);
	    	$this->ControllerAction->field('payments', ['type' => 'element', 'element' => 'Institution.Fees/payments', 'currency' => $currency, 'visible' => ['view'=>true, 'edit'=>true]]);

			$entity->education_programme = $this->getEducationProgramme($entity);
			$entity->openemis_no = $this->getOpenemisNo($entity);
			$entity->name = $this->getName($entity);
			$entity->outstanding = $this->getOutstanding($entity);

			$feeTypes = [];
	    	foreach ($this->InstitutionSiteFeeEntity->institution_site_fee_types as $key=>$obj) {
	    		$feeTypes[] = [
	    			'id' => $obj->id,
	    			'type' => $obj->fee_type->name,
					'fee_type_id' => $obj->fee_type_id,
					'amount' => $obj->amount
				];
	    	}
			$this->ControllerAction->model->fields['fee_types']['data'] = $feeTypes;
			$this->ControllerAction->model->fields['fee_types']['total'] = $this->getTotal($entity);

			$modal = $this->ControllerAction->getModalOptions('remove');
			$this->controller->set('modal', $modal);

			$this->ControllerAction->setFieldOrder([
	   			'academic_period_id', 'education_programme', 'education_grade_id', 'openemis_no', 'name', 'outstanding', 'fee_types', 'payments'
			]);

			return $entity;
		} else {
			$this->Session->delete($idKey);
			$this->ControllerAction->Alert->warning('general.notExists');
			$action = $this->ControllerAction->buttons['index']['url'];
			return $this->ControllerAction->controller->redirect($action);
		}
	}

	public function view($id=0) {
		$entity = $this->setupViewEdit($id);
		if (is_object($entity)) {
			$this->controller->set('data', $entity);
		} else {
			return $entity;
		}
	   		
	}

	public function edit($id=0) {
		$entity = $this->setupViewEdit($id);
		if (is_object($entity)) {
			$this->ControllerAction->model->fields['name']['type'] = 'readonly';
			$this->ControllerAction->model->fields['openemis_no']['type'] = 'readonly';
			$this->ControllerAction->model->fields['outstanding']['type'] = 'readonly';
			$this->ControllerAction->model->fields['education_grade_id']['type'] = 'readonly';
			$this->ControllerAction->model->fields['academic_period_id']['type'] = 'readonly';
			$this->ControllerAction->model->fields['education_programme']['type'] = 'readonly';

			$this->ControllerAction->model->fields['academic_period_id']['attr']['value'] = $entity->academic_period->name;
			$this->ControllerAction->model->fields['education_grade_id']['attr']['value'] = $entity->education_grade->name;
			
			// pr($this->request->data);
			$paymentField = '';
			$payments = [];
			$data = $this->request->data;
			if (isset($data['submit'])) {
				if ($data['submit'] == 'add') {
					$paymentField = $this->createVirtualPaymentEntity($entity);
				} else {
					
				}
			}
	    	$this->ControllerAction->model->fields['payments']['data'] = $payments;
	    	$this->ControllerAction->model->fields['payments']['paymentField'] = $paymentField;
	    	// pr($this->fields);
	    	// $this->fields['payment_date']['label'] = false;
	    	// $this->fields['comments']['label'] = false;
	    	// $this->fields['amount']['label'] = false;
	    	// $this->fields['id']['type'] = 'hidden';
	    	// $this->fields['security_user_id']['label'] = 'hidden';
	    	// $this->fields['institution_site_fee_id']['label'] = 'hidden';

	    	$this->ControllerAction->model->fields['payments']['fields'] = [
	    		'id' 					  => ['type' => 'hidden', 'field' => 'id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'attr'=>['label' => false, 'name'=>'']],
	    		'payment_date' 			  => ['type' => 'date', 'field' => 'payment_date', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'label' => false, 'attr'=>['name'=>'']],
	    		'comments' 				  => ['type' => 'string', 'field' => 'comments', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'attr'=>['label' => false, 'name'=>'']],
	    		'amount' 				  => ['type' => 'string', 'field' => 'amount', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'attr'=>['label' => false, 'name'=>'']],
	    		'security_user_id' 		  => ['type' => 'hidden', 'field' => 'security_user_id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'attr'=>['label' => false, 'name'=>'']],
	    		'institution_site_fee_id' => ['type' => 'hidden', 'field' => 'institution_site_fee_id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'attr'=>['label' => false, 'name'=>'']]
			];

			$this->controller->set('data', $entity);
		} else {
			return $entity;
		}
	   		
	}

	protected function createVirtualPaymentEntity($entity) {
		$data = [
			'id' => '',
			'amount' => '',
			'payment_date' => '',
			'comments' => '',
			'security_user_id' => $entity->security_user_id,
			'institution_site_fee_id' => $this->InstitutionSiteFeeEntity->id,
		];
		$studenFee = $this->newEntity();
		$studenFee = $this->patchEntity($studenFee, $data);
		return $studenFee;
	}

	protected function getExistingRecordId($securityId, $entity) {
		$id = '';
		foreach ($entity->institution_site_section_students as $student) {
			if ($student->security_user_id == $securityId) {
				$id = $student->id;
			}
		}
		return $id;
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $this->getOpenemisNo($entity);
	}

	public function getOpenemisNo(Entity $entity) {
		// pr($entity->toArray());die;
		return $entity->student->openemis_no;
	}

	public function onGetEducationProgramme(Event $event, Entity $entity) {
		return $this->getEducationProgramme($entity);
	}

	public function getEducationProgramme(Entity $entity) {
		return $entity->education_grade->programme_name;
	}

	public function onGetName(Event $event, Entity $entity) {
		return $this->getName($entity);
	}

	public function getName(Entity $entity) {
		return $entity->student->name;
	}

	public function onGetTotal(Event $event, Entity $entity) {
		return $this->getTotal($entity);
	}

	public function getTotal(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			return $this->InstitutionSiteFeeEntity->total;
		} else {
			return 'No fee is set';
		}
	}

	public function onGetOutstanding(Event $event, Entity $entity) {
		return $this->getOutstanding($entity);
	}

	public function getOutstanding(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			$query = $this->find();
			$entityRecord = $query->where([
					$this->aliasField('institution_site_fee_id') => $this->InstitutionSiteFeeEntity->id,
					$this->aliasField('security_user_id') => $entity->security_user_id,
				])
			->select([
				'paid' => $query->func()->sum($this->aliasField('amount'))
			])
			->first()
			;
			return ($this->InstitutionSiteFeeEntity->total - $entityRecord->paid);
		}
	}

	public function onGetAmount(Event $event, Entity $entity) {
		return $this->getAmount($entity);
	}

	public function getAmount(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			$query = $this->find();
			$entityRecord = $query->where([
					$this->aliasField('institution_site_fee_id') => $this->InstitutionSiteFeeEntity->id,
					$this->aliasField('security_user_id') => $entity->security_user_id,
				])
			->select([
				'paid' => $query->func()->sum($this->aliasField('amount'))
			])
			->first()
			;
			return $entityRecord->paid;
		}
	}

	// public function onGetInstitutionSiteFeeId(Event $event, Entity $entity) {
	// 	die('onGetInstitutionSiteFeeId');
	// 	return 'chak';
	// }

	// public function onUpdateInstitutionSiteFeeId(Event $event, array $attr, $action, $request) {
	// 	die('chak');
	// 	$attr['value'] = 'chak';
	// 	$attr['type'] = 'string';
	// 	return $attr;
	// }

	// public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
	// 	$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
	// 	$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $this->_academicPeriodOptions);
	// 	$attr['options'] = $this->_academicPeriodOptions;
	// 	return $attr;
	// }

	// public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request) {
	// 	if ($this->_selectedAcademicPeriodId ==0) {
	// 		$this->_academicPeriodOptions = $this->AcademicPeriods->getAvailableAcademicPeriods(true);
	// 		$this->_selectedAcademicPeriodId = $this->postString('academic_period_id', $this->_academicPeriodOptions);
	// 	}
 // 		$institutionsId = $this->Session->read('Institutions.id');
	// 	$this->_gradeOptions = $this->Institutions->InstitutionSiteGrades->getInstitutionSiteGradeOptions($institutionsId, $this->_selectedAcademicPeriodId);
	// 	$attr['options'] = $this->_gradeOptions;
	// 	return $attr;
	// }


}
