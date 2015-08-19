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
	public $currency = '';

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
		$this->belongsTo('CreatedBy', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);

		/**
		 * Shortcuts
		 */
		$this->AcademicPeriods = $this->InstitutionSiteFees->AcademicPeriods;
		$this->InstitutionSiteGrades = $this->InstitutionSiteFees->Institutions->InstitutionSiteGrades;	
		$this->StudentPromotion = $this->InstitutionSiteFees->Institutions->StudentPromotion;

	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {
    	$this->ControllerAction->field('amount', ['type' => 'float', 					'visible' => ['index'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('payment_date', ['type' => 'date', 				'visible' => ['edit'=>true]]);
    	$this->ControllerAction->field('comments', ['type' => 'string', 				'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	$this->ControllerAction->field('security_user_id', ['type' => 'select', 		'visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('institution_site_fee_id', ['type' => 'select', 	'visible' => ['edit'=>true]]);

    	$this->ControllerAction->field('openemis_no', ['type' => 'string', 				'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('name', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('total', ['type' => 'string', 					'visible' => ['index'=>true]]);
    	$this->ControllerAction->field('outstanding', ['type' => 'string', 				'visible' => ['index'=>true]]);

		$session = $this->request->session();
		$this->institutionId = $session->read('Institutions.id');

		$ConfigItems = TableRegistry::get('ConfigItems');
    	$this->currency = $ConfigItems->value('currency');

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'name', 'total', 'amount', 'outstanding'
		]);

		$settings['model'] = 'Institution.StudentPromotion';

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
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->find('byGrades', [
			'institution_id'=>$this->institutionId,
			'education_grade_id'=>$this->_selectedEducationGradeId,
			'academic_period_id'=>$this->_selectedAcademicPeriodId
		]);
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
    public function viewBeforeAction(Event $event) {}

	private function setupViewEdit($id) {
		$this->ControllerAction->model('Institution.StudentPromotion');
		$this->ControllerAction->model->ControllerAction = $this->ControllerAction;
		$idKey = $this->ControllerAction->model->aliasField('id');

		if ($this->ControllerAction->model->exists([$idKey => $id])) {

			$entity = $this->ControllerAction->model->get($id, [
				'contain'=> array_merge($this->ControllerAction->model->allAssociations(), [])
			]);

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

	    	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'non-editable'=>true, 'visible' => ['view'=>true, 'edit'=>true]]);
	    	$this->ControllerAction->field('payments', ['type' => 'element', 'element' => 'Institution.Fees/payments', 'currency' => $this->currency, 'visible' => ['view'=>true, 'edit'=>true]]);

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

	    	$this->ControllerAction->model->fields['payments']['fields'] = [
	    		'id' 					  => ['type' => 'hidden', 'field' => 'id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']],
	    		'payment_date' 			  => ['type' => 'date', 'field' => 'payment_date', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'label' => false, 'tableHeader' => __('Payment Date'), 'attr'=>['name'=>'']],
	    		'created_user_id'		  => ['type' => 'disabled', 'field' => 'created_user_id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'tableHeader' => __('Created By'), 'attr'=>['label' => false, 'name'=>'']],
	    		'amount' 				  => ['type' => 'string', 'field' => 'amount', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'tableHeader' => __('Amount ('.$this->currency.')'), 'attr'=>['label' => false, 'name'=>'']],
	    		'comments' 				  => ['type' => 'string', 'field' => 'comments', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'tableHeader' => __('Comments'), 'attr'=>['label' => false, 'name'=>'']],
	    		'security_user_id' 		  => ['type' => 'hidden', 'field' => 'security_user_id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']],
	    		'institution_site_fee_id' => ['type' => 'hidden', 'field' => 'institution_site_fee_id', 'model' => 'StudentFees', 'className' => 'Institution.StudentFees', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]
			];

			$modal = $this->ControllerAction->getModalOptions('remove');
			$this->controller->set('modal', $modal);

			$this->ControllerAction->setFieldOrder([
	   			'academic_period_id', 'education_programme', 'education_grade_id', 'openemis_no', 'name', 'outstanding', 'fee_types', 'payments'
			]);

			return $entity;
		} else {
			$this->Session->delete($idKey);
			return false;
		}
	}

	public function view($id=0) {
		$entity = $this->setupViewEdit($id);
		if (is_object($entity)) {
			$payments = $this->getPaymentRecords($entity);
			$this->ControllerAction->model->fields['payments']['data'] = $payments;
			$this->ControllerAction->model->fields['payments']['total'] = $this->getRecordsTotalAmount($payments);
			$this->controller->set('data', $entity);
		} else {
			$this->ControllerAction->Alert->warning('general.notExists');
			// $action = $this->ControllerAction->buttons['index']['url'];
			$action = $this->ControllerAction->url('index');
			return $this->controller->redirect($action);
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
			
			$paymentField = '';
			$payments = $this->getPaymentRecords($entity);
			$data = $this->request->data;
			if (isset($data['submit'])) {
				if ($data['submit'] == 'add') {
					$paymentField = $this->createVirtualPaymentEntity($entity);
				} else {
					$totalAmount = $this->getRecordsTotalAmount($data[$this->alias()]);
					if ($totalAmount > $data['StudentPromotion']['outstanding']) {
						$this->ControllerAction->Alert->error('StudentFees.totalAmountExceeded');
					} else {

						$studentFees = $this->newEntities($data[$this->alias()]);
						$updatedPayments = [];
						$error = false;
						foreach ($studentFees as $studentFee) {
							$id = ($studentFee->id) ? $studentFee->id : 'new';
							$updatedPayments[$id] = $studentFee;
						    if ($studentFee->errors()) {
						    	$error[$id] = $studentFee->errors();
						    }
						}

						if (!$error) {
							$ids = [];
							foreach ($payments as $payment) {
								if (!array_key_exists($payment->id, $updatedPayments)) {
									$ids[] = $payment->id;
								}
							}
							if (!empty($ids)) {
								$this->deleteAll(['id IN' => $ids]);
							}
							foreach ($studentFees as $studentFee) {
						    	$this->save($studentFee);
							}
							$this->ControllerAction->Alert->success('general.edit.success');
							// $action = $this->ControllerAction->buttons['view']['url'];
							$action = $this->ControllerAction->url('view');
							return $this->controller->redirect($action);
						} else {
							$payments = $updatedPayments;
							$this->log($error, 'debug');
							$this->ControllerAction->Alert->error('general.edit.failed');
						}

					}
				}
			}
	    	$this->ControllerAction->model->fields['payments']['data'] = $payments;
	    	$this->ControllerAction->model->fields['payments']['paymentField'] = $paymentField;
	    	$this->ControllerAction->model->fields['payments']['fields']['created_user_id']['type'] = 'hidden';

			$this->controller->set('data', $entity);
		} else {
			$this->ControllerAction->Alert->warning('general.notExists');
			// $action = $this->ControllerAction->buttons['index']['url'];
			$action = $this->ControllerAction->url('index');
			return $this->controller->redirect($action);
		}
	}

	private function createVirtualPaymentEntity($entity) {
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


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $this->getOpenemisNo($entity);
	}

	public function getOpenemisNo(Entity $entity) {
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
			return floatval($this->InstitutionSiteFeeEntity->total);
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
			return floatval(floatval($this->InstitutionSiteFeeEntity->total) - floatval($entityRecord->paid));
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
			return floatval($entityRecord->paid);
		}
	}

	public function getRecordsTotalAmount($data) {
		$totalAmount = 0;
		foreach ($data as $key=>$value) {
			if (is_array($value)) {
				$totalAmount = floatval($totalAmount) + floatval($value['amount']);
			} else if (is_object($value)) {
				$totalAmount = floatval($totalAmount) + floatval($value->amount);
			}
		};
		return floatval($totalAmount);
	}

	public function getPaymentRecords(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			$query = $this->find('all');
			$entityRecords = $query->contain(['CreatedBy'])->where([
					$this->aliasField('institution_site_fee_id') => $this->InstitutionSiteFeeEntity->id,
					$this->aliasField('security_user_id') => $entity->security_user_id,
				])
			->toArray()
			;
			return $entityRecords;
		}
	}

}
