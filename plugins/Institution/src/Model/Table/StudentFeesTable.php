<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;


class StudentFeesTable extends AppTable {
	public $institutionId = 0;
	private $_selectedAcademicPeriodId = -1;
	private $_academicPeriodOptions = [];
	private $_selectedEducationGradeId = -1;
	private $_gradeOptions = [];
	public $currency = '';

	protected $InstitutionSiteFees = null;
	protected $InstitutionSiteFeeEntity = null;
	protected $StudentFeesAbstract = null;

	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);

		// $this->hasMany();

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		// $this->addBehavior('AcademicPeriod.Period');
		// to handle field type (autocomplete)
		// $this->addBehavior('OpenEmis.Autocomplete');
		// $this->addBehavior('User.User');
		// $this->addBehavior('User.AdvancedNameSearch');
		
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function beforeAction(Event $event) {
		$session = $this->request->session();
		$this->institutionId = $session->read('Institutions.id');

		$ConfigItems = TableRegistry::get('ConfigItems');
    	$this->currency = $ConfigItems->value('currency');
    	$this->StudentFeesAbstract = TableRegistry::get('Institution.StudentFeesAbstract');
		$this->InstitutionSiteFees = TableRegistry::get('Institution.InstitutionSiteFees');

    	$this->ControllerAction->field('institution_id', ['visible' => false]);
    	$this->ControllerAction->field('student_status_id', ['visible' => false]);
    	$this->ControllerAction->field('education_grade_id', ['visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('academic_period_id', ['visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('start_date', ['visible' => false]);
    	$this->ControllerAction->field('end_date', ['visible' => false]);

    	$this->ControllerAction->field('amount', ['type' => 'float', 					'visible' => ['index'=>true]]);
    	// $this->ControllerAction->field('payment_date', ['type' => 'date', 			'visible' => ['edit'=>true]]);
    	// $this->ControllerAction->field('comments', ['type' => 'string', 				'visible' => ['view'=>true, 'edit'=>true], 'onChangeReload'=>true]);
    	// $this->ControllerAction->field('security_user_id', ['type' => 'select', 		'visible' => ['view'=>true, 'edit'=>true]]);
    	// $this->ControllerAction->field('institution_site_fee_id', ['type' => 'select', 'visible' => ['edit'=>true]]);

    	$this->ControllerAction->field('openemis_no', ['type' => 'string', 				'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('student_id', ['type' => 'string', 				'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('total', ['type' => 'float', 					'visible' => ['index'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('outstanding', ['type' => 'float', 				'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

    	$this->ControllerAction->field('education_programme', ['type' => 'string', 		'visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'non-editable'=>true, 'visible' => ['view'=>true, 'edit'=>true]]);
    	$this->ControllerAction->field('payments', ['type' => 'element', 'element' => 'Institution.Fees/payments', 'currency' => $this->currency, 'visible' => ['view'=>true, 'edit'=>true]]);

    	$this->StudentFeesAbstract->fields['id'] = array_merge($this->StudentFeesAbstract->fields['id'], ['type' => 'hidden', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]);
    	$this->StudentFeesAbstract->fields['payment_date'] = array_merge($this->StudentFeesAbstract->fields['payment_date'], ['type' => 'date', 'label' => false, 'tableHeader' => __('Payment Date'), 'attr'=>['name'=>'']]);
    	$this->StudentFeesAbstract->fields['created_user_id'] = array_merge($this->StudentFeesAbstract->fields['created_user_id'], ['type' => 'disabled', 'tableHeader' => __('Created By'), 'attr'=>['label' => false, 'name'=>'']]);
    	$this->StudentFeesAbstract->fields['amount'] = array_merge($this->StudentFeesAbstract->fields['amount'], ['type' => 'float', 'length'=>'14', 'tableHeader' => __('Amount ('.$this->currency.')'), 'attr'=>['label' => false, 'name'=>'']]);
    	$this->StudentFeesAbstract->fields['comments'] = array_merge($this->StudentFeesAbstract->fields['comments'], ['type' => 'string', 'tableHeader' => __('Comments'), 'attr'=>['label' => false, 'name'=>'']]);
    	$this->StudentFeesAbstract->fields['security_user_id'] = array_merge($this->StudentFeesAbstract->fields['security_user_id'], ['type' => 'hidden', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]);
    	$this->StudentFeesAbstract->fields['institution_site_fee_id'] = array_merge($this->StudentFeesAbstract->fields['institution_site_fee_id'], ['type' => 'hidden', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]);

    	unset($this->StudentFeesAbstract->fields['modified_user_id']);
    	unset($this->StudentFeesAbstract->fields['modified']);
    	$this->StudentFeesAbstract->fields['created'] = false;

	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	// public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
	// 	$this->ControllerAction->field('academic_period_id', ['visible' => false]);
	// 	$this->ControllerAction->field('class', ['order' => 90]);
	// 	$this->ControllerAction->field('student_status_id', ['order' => 100]);
	// 	$this->fields['start_date']['visible'] = false;
	// 	$this->fields['end_date']['visible'] = false;
	// }

    public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->setFieldOrder([
			'openemis_no', 'student_id', 'total', 'amount', 'outstanding'
		]);

		$conditions = array(
			'InstitutionGrades.institution_site_id' => $this->institutionId
		);
		$academicPeriodOptions = $this->Institutions->InstitutionGrades->getAcademicPeriodOptions($conditions);
		if (empty($academicPeriodOptions)) {
			$this->Alert->warning('Institutions.noProgrammes');
		}
		$institutionId = $this->institutionId;
		$this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->_selectedAcademicPeriodId);

		$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($institutionId, $this->_selectedAcademicPeriodId);
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
			->contain(['InstitutionSiteFeeTypes.FeeTypes'])
			->where([
				'InstitutionSiteFees.education_grade_id' => $this->_selectedEducationGradeId,
				'InstitutionSiteFees.academic_period_id' => $this->_selectedAcademicPeriodId,
				'InstitutionSiteFees.institution_site_id' => $this->institutionId
			])
			->first()
			;

	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query
		->where([
			$this->aliasField('institution_id') => $this->institutionId,
			$this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
			$this->aliasField('education_grade_id') => $this->_selectedEducationGradeId,
		])
		;
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewAfterAction(Event $event, Entity $entity) {
		$this->InstitutionSiteFeeEntity = $this->InstitutionSiteFees
				->find()
				->contain(['InstitutionSiteFeeTypes.FeeTypes'])
				->where([
					'InstitutionSiteFees.education_grade_id' => $entity->education_grade_id,
					'InstitutionSiteFees.academic_period_id' => $entity->academic_period_id,
					'InstitutionSiteFees.institution_site_id' => $entity->institution_id
				])
				->first()
				;

		$feeTypes = [];
    	foreach ($this->InstitutionSiteFeeEntity->institution_site_fee_types as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => $obj->id,
    			'type' => $obj->fee_type->name,
				'fee_type_id' => $obj->fee_type_id,
				'amount' => number_format(floatval($obj->amount), 2)
			];
    	}
		$this->fields['fee_types']['data'] = $feeTypes;
		$this->fields['fee_types']['total'] = $this->onGetTotal($event, $entity);

    	$this->fields['payments']['fields'] = $this->StudentFeesAbstract->fields;
    	$this->fields['payments']['data'] = $this->getPaymentRecords($entity);
    	$this->fields['payments']['total'] = $this->onGetAmount($event, $entity);
		// $this->fields['total'] = ;

		$modal = $this->ControllerAction->getModalOptions('remove');
		$this->controller->set('modal', $modal);

		$this->ControllerAction->setFieldOrder([
   			'academic_period_id', 'education_programme', 'education_grade_id', 'openemis_no', 'student_id', 'fee_types', 'payments', 'outstanding'
		]);

    }


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	public function addBeforeAction(Event $event) {
		$requestData = $this->request->data;
		if (isset($requestData[$this->alias()]['id'])) {
			// if ($requestData['submit']=='reload') {
				$idKey = $this->aliasField($this->primaryKey());
	    		if ($this->exists([$idKey => $requestData[$this->alias()]['id']])) {
	    			$entity = $this->find()
	    				->contain($this->allAssociations())
	    				->where([$idKey => $requestData[$this->alias()]['id']])
						->first();
	    			if ($entity) {
				    	$this->addActionSetup($event, $entity);
						if (isset($requestData['StudentFeesAbstract'])) {
							foreach($requestData['StudentFeesAbstract'] as $key=>$record) {
								/**
								 * these are the rows from the form
								 */
						    	$this->fields['payments']['paymentFields'][] = $this->createVirtualPaymentEntity($entity, $record);
							}
						}
						/**
						 * this is the new blank row
						 */
				    	$this->fields['payments']['paymentFields'][] = $this->createVirtualPaymentEntity($entity, false);
					}
	    		}
	    	// }
    	} elseif (isset($this->request->params['pass'][1])) {
    		$idKey = $this->aliasField($this->primaryKey());
    		if ($this->exists([$idKey => $this->request->params['pass'][1]])) {
    			$entity = $this->find()
    				->contain($this->allAssociations())
    				->where([$idKey => $this->request->params['pass'][1]])
					->first();
    			if ($entity) {
			    	$this->addActionSetup($event, $entity);
			    	$this->fields['payments']['paymentFields'] = $this->getPaymentRecords($entity);
    			}
    		}
    	} else {
    		/**
    		 * should be something else here...
    		 */
	    	return false;
    	}
	}

    private function addActionSetup(Event $event, Entity $entity) {
    	$this->fields['student_id']['type'] = 'readonly';
		$this->fields['openemis_no']['type'] = 'readonly';
		$this->fields['education_grade_id']['type'] = 'readonly';
		$this->fields['academic_period_id']['type'] = 'readonly';
		$this->fields['education_programme']['type'] = 'readonly';
		$this->fields['outstanding']['type'] = 'readonly';

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

		/**
		 * Hidden fields value
		 */
		$this->fields['id']['value'] = $entity->id;
		$this->fields['student_id']['value'] = $entity->student_id;
		$this->fields['openemis_no']['value'] = $entity->user->openemis_no;
		$this->fields['education_grade_id']['value'] = $entity->education_grade_id;
		$this->fields['academic_period_id']['value'] = $entity->academic_period_id;
		$this->fields['outstanding']['value'] = $this->getOutstanding($entity);

		/**
		 * Readonly fields value
		 */
		$this->fields['student_id']['attr']['value'] = $entity->user->name;
		$this->fields['openemis_no']['attr']['value'] = $entity->user->openemis_no;
		$this->fields['education_grade_id']['attr']['value'] = $entity->education_grade->name;
		$this->fields['academic_period_id']['attr']['value'] = $entity->academic_period->name;
		$this->fields['education_programme']['attr']['value'] = $entity->education_grade->programme_name;
		$this->fields['outstanding']['attr']['value'] = $this->fields['outstanding']['value'];

		$this->fields['total']['type'] = 'hidden';
		$this->fields['total']['value'] = $this->getTotal($entity);

		$feeTypes = [];
    	foreach ($this->InstitutionSiteFeeEntity->institution_site_fee_types as $key=>$obj) {
    		$feeTypes[] = [
    			'id' => $obj->id,
    			'type' => $obj->fee_type->name,
				'fee_type_id' => $obj->fee_type_id,
				'amount' => number_format(floatval($obj->amount), 2)
			];
    	}
		$this->fields['fee_types']['data'] = $feeTypes;
		$this->fields['fee_types']['total'] = $this->onGetTotal($event, $entity);

    	$this->StudentFeesAbstract->fields['created_user_id']['type'] = 'hidden';
    	$this->StudentFeesAbstract->fields['amount']['type'] = 'string';
    	$this->fields['payments']['fields'] = $this->StudentFeesAbstract->fields;

		$modal = $this->ControllerAction->getModalOptions('remove');
		$this->controller->set('modal', $modal);

		$this->ControllerAction->setFieldOrder([
   			'academic_period_id', 'education_programme', 'education_grade_id', 'openemis_no', 'student_id', 'fee_types', 'outstanding', 'payments'
		]);
    }

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$StudentFees = $this->StudentFeesAbstract;
		$process = function ($model, $entity) use ($data, $StudentFees) {
			// pr($data);die;
			$fees = $StudentFees->newEntities($data['StudentFeesAbstract']);
			$error = false;
			$totalPaid = 0.00;
			foreach ($fees as $key=>$fee) {
			    if ($fee->errors()) {
			    	$error = $fee->errors();
			    	$data['StudentFeesAbstract'][$key]['errors'] = $error;
			    }
				$totalPaid = (float)$totalPaid + (float)$fee->amount;
			 //    $fees[$key]->amount = number_format($data['StudentFeesAbstract'][$key]['amount'], 2);
			 //    if ($error) {
				//     $fees[$key]->errors($error);
				// }
			}
			if (!$error) {
				if ($totalPaid > $data['StudentFees']['total']) {
					$error = ['amount'=>'Total amount paid exceeds total fee amount'];
					foreach ($fees as $key=>$fee) {
			    		$fees[$key]->errors($error);
			    	}
				}
			}
			if (!$error) {
				// pr($totalPaid);
				// pr($data['StudentFees']['total']);
				// pr($data['StudentFees']['total'] >= $totalPaid);
				// die;
				foreach ($fees as $fee) {
			    	$StudentFees->save($fee);
				}
				return true;
			} else {
				$errorMessage='';
				foreach ($error as $key=>$value) {
					$errorMessage .= Inflector::classify($key);
				}
				$model->log($error, 'debug');
				/**
				 * unset all field validation except for "academic_period_id" to trigger validation error in ControllerActionComponent
				 */
				foreach ($model->fields as $value) {
					if ($value['field'] != 'academic_period_id') {
						$model->validator()->remove($value['field']);
					}
				}
				$model->fields['payments']['paymentFields'] = $fees;
				$model->request->data['StudentFeesAbstract'] = $data['StudentFeesAbstract'];
				return false;
			}
		};
		return $process;
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
		return $entity->user->openemis_no;
	}

	public function onGetTotal(Event $event, Entity $entity) {
		return $this->currency.' '.number_format($this->getTotal($entity), 2);
	}

	public function getTotal(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			/**
			 * PHPOE-1414
			 * Not using $this->InstitutionSiteFeeEntity->total anymore since it only saves till 11 digits with 2 decimal places
			 * and when a feeType is for example, 999,999,999.99, the rest of the fee types cannot be added saved into the "total" record.
			 * Implements a manual count of the extracted feeTypes
			 */
			// return $this->currency.' '.(number_format(floatval($this->InstitutionSiteFeeEntity->total), 2));
			$amount = 0.00;
			foreach ($this->InstitutionSiteFeeEntity->institution_site_fee_types as $key=>$feeType) {
				$amount = (float)$amount + (float)$feeType->amount;
			}
			return $amount;
		} else {
			return 'No fee is set';
		}
	}

	public function onGetAmount(Event $event, Entity $entity) {
		return $this->currency.' '.number_format($this->getTotalPaidAmount($entity), 2);
	}

	public function getTotalPaidAmount(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			$query = $this->StudentFeesAbstract->find();
			$entityRecord = $query->where([
					$this->StudentFeesAbstract->aliasField('institution_site_fee_id') => $this->InstitutionSiteFeeEntity->id,
					$this->StudentFeesAbstract->aliasField('security_user_id') => $entity->student_id,
				])
			->select([
				'paid' => $query->func()->sum($this->StudentFeesAbstract->aliasField('amount'))
			])
			->first()
			;
			if ($entityRecord) {
				return (float)$entityRecord->paid;
			} else {
				return (float)0.00;
			}
		}
	}

	public function onGetOutstanding(Event $event, Entity $entity) {
		return $this->currency.' '.$this->getOutstanding($entity);	
	}

	public function getOutstanding(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			$total = $this->getTotal($entity);
			$paid = $this->getTotalPaidAmount($entity);
			return number_format($total-$paid, 2);
		}
	}

	public function onGetEducationProgramme(Event $event, Entity $entity) {
		return $entity->education_grade->programme_name;
	}

	private function getPaymentRecords(Entity $entity) {
		if (!is_null($this->InstitutionSiteFeeEntity)) {
			$query = $this->StudentFeesAbstract->find('all');
			$entityRecords = $query->contain(['CreatedBy'])->where([
					$this->StudentFeesAbstract->aliasField('institution_site_fee_id') => $this->InstitutionSiteFeeEntity->id,
					$this->StudentFeesAbstract->aliasField('security_user_id') => $entity->student_id,
				])
			->toArray()
			;
			// foreach ($entityRecords as $key => $value) {
			// 	$entityRecords[$key]->amount = number_format((float)$entityRecords[$key]->amount, 2);
			// }
			return $entityRecords;
		}
	}

	private function createVirtualPaymentEntity($entity, $requestData) {
		$data = [
			'id' => ($requestData) ? $requestData['id'] : '',
			'amount' => ($requestData) ? $requestData['amount'] : '',
			'payment_date' => ($requestData) ? $requestData['payment_date'] : '',
			'comments' => ($requestData) ? $requestData['comments'] : '',
			'security_user_id' => $entity->student_id,
			'institution_site_fee_id' => $this->InstitutionSiteFeeEntity->id,
		];
		$studenFee = $this->StudentFeesAbstract->newEntity();
		$studenFee = $this->StudentFeesAbstract->patchEntity($studenFee, $data, ['validate' => false]);
		// $studenFee->amount = number_format((float)$studenFee->amount, 2);
		return $studenFee;
	}

	private function allAssociations() {
		$omitForeignKeys = ['modified_user_id', 'created_user_id'];
		$associations = [];
		foreach ($this->associations() as $assoc) {
			if (!in_array($assoc->foreignKey(), $omitForeignKeys)) {
				$associations[] = $assoc->target()->alias();
			}
		}
		return $associations;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'index') {
			unset($toolbarButtons['add']);
		} else if ($action == 'view') {
			$toolbarButtons['add'] = $toolbarButtons['edit'];
			$toolbarButtons['add']['url'][0] = 'add';
			$toolbarButtons['add']['label'] = '<i class="fa kd-add"></i>';
			$toolbarButtons['add']['attr']['title'] = 'Add New Payment';
			unset($toolbarButtons['edit']);
		} else if ($action == 'add') {
			$toolbarButtons['back']['url'] = $buttons['view']['url'];
		}
	}

}
