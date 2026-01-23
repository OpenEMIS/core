<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

use Cake\Log\Log;
use Cake\ORM\Locator\TableLocator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class StudentFeesTable extends ControllerActionTable
{
    use MessagesTrait;

    private $institutionId = 0;
    private $_selectedAcademicPeriodId = -1;
    private $_academicPeriodOptions = [];
    private $_selectedEducationGradeId = -1;
    private $_gradeOptions = [];
    public $currency = '';

    protected $InstitutionFees = null;
    protected $InstitutionFeeEntity = null;
    protected $StudentFeesAbstract = null;

    public function initialize(array $config): void
    {
        $this->setTable('institution_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);

        if ($this->behaviors()->has('ControllerAction')) {
            // $this->behaviors()->get('ControllerAction')->config([
            //     'actions' => [
            //         'index' => true,
            //         'view' => true,
            //         'edit' => true,
            //         'add' => false,
            //         'remove' => false,
            //         'reorder' => false
            //     ],
            // ]);
            $this->behaviors()->get('ControllerAction')->setConfig([
                'actions' => [
                    'index' => true,
                    'view' => true,
                    'edit' => true,
                    'add' => false,
                    'remove' => false,
                    'reorder' => false
                ],
            ]);
            $this->addBehavior('Institution.InstitutionTab');
        }

        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Excel', ['pages' => ['index']]);//POCOR-6165
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $session = $this->request->getSession();
        //$this->institutionId = $session->read('Institution.Institutions.id');
        $this->institutionId = $this->getQueryString('institution_id');//POCOR-8360

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $this->currency = $ConfigItems->value('currency');
        $this->StudentFeesAbstract = TableRegistry::getTableLocator()->get('Institution.StudentFeesAbstract');
        $this->InstitutionFees = TableRegistry::getTableLocator()->get('Institution.InstitutionFees');

        $this->field('institution_id', ['visible' => false]);
        $this->field('student_status_id', ['visible' => false]);
        $this->field('education_grade_id', ['visible' => ['view'=>true, 'edit'=>true]]);
        $this->field('academic_period_id', ['visible' => ['view'=>true, 'edit'=>true]]);
        $this->field('start_date', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('previous_institution_student_id', ['visible' => false]);

        $this->field('openemis_no', ['type' => 'string', 	'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('student_id', ['type' => 'string',	'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('total_fee', ['type' => 'float',		'visible' => ['index'=>true, 'edit'=>true]]);
        $this->field('amount_paid', ['type' => 'float', 	'visible' => ['index'=>true, 'edit'=>true]]);
        $this->field('outstanding_fee', ['type' => 'float', 	'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

        $this->field('education_programme', ['type' => 'string', 	'visible' => ['view'=>true, 'edit'=>true]]);
        $this->field('fee_types', ['type' => 'element', 'element' => 'Institution.Fees/fee_types', 'currency' => $this->currency, 'non-editable'=>true, 'visible' => ['view'=>true, 'edit'=>true]]);
        $this->field('payments', ['type' => 'element', 'element' => 'Institution.Fees/payments', 'currency' => $this->currency, 'visible' => ['view'=>true, 'edit'=>true]]);

        $this->StudentFeesAbstract->fields['id'] = array_merge($this->StudentFeesAbstract->fields['id'], ['type' => 'hidden', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]);
        $this->StudentFeesAbstract->fields['created_user_id'] = array_merge($this->StudentFeesAbstract->fields['created_user_id'], ['type' => 'disabled', 'tableHeader' => __('Created By'), 'attr'=>['label' => false, 'name'=>'']]);
        $this->StudentFeesAbstract->fields['comments'] = array_merge($this->StudentFeesAbstract->fields['comments'], ['type' => 'string', 'tableHeader' => __('Comments'), 'attr'=>['label' => false, 'name'=>'']]);
        $this->StudentFeesAbstract->fields['student_id'] = array_merge($this->StudentFeesAbstract->fields['student_id'], ['type' => 'hidden', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]);
        $this->StudentFeesAbstract->fields['institution_fee_id'] = array_merge($this->StudentFeesAbstract->fields['institution_fee_id'], ['type' => 'hidden', 'tableHeader' => __(''), 'attr'=>['label' => false, 'name'=>'']]);

        $this->StudentFeesAbstract->fields['payment_date'] = array_merge($this->StudentFeesAbstract->fields['payment_date'], [
            'type' => 'date',
            'label' => false,
            'tableHeader' => __('Payment Date'),
            'attr'=>[
                'name'=>''
            ],
            'inputWrapperStyle'=>'margin-bottom:0;'
        ]);
        $this->StudentFeesAbstract->fields['amount'] = array_merge($this->StudentFeesAbstract->fields['amount'], [
            'type' => 'float',
            'length'=>'14',
            'tableHeader' => __('Amount ('.$this->currency.')'),
            'attr'=>[
                'label' => false,
                'name'=>'',
                'class' => "form-control inputs_total_payments",
                'computetype' => "total_payments",
                'onblur' => "jsTable.computeTotalForMoney('total_payments'); jsForm.compute(this); return fees.checkDecimal(this, 2);",
                'onclick' => "fees.selectAll(this); ",
                'onkeypress' => "return utility.floatCheck(event)",
                'allownull' => "0",
                'onfocus' => "$(this).select();",
                'data-compute-variable' => "true",
                'data-compute-operand' => "plus"
            ]
        ]);

        unset($this->StudentFeesAbstract->fields['modified_user_id']);
        unset($this->StudentFeesAbstract->fields['modified']);
        $this->StudentFeesAbstract->fields['created'] = false;

        $this->StudentFeesAbstract->setFieldOrder(['payment_date', 'amount', 'comments']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Fees','Finance');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function onUpdateIncludes(EventInterface $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit' || $action == 'add') {
            $includes['fees'] = [
                'include' => true,
                'js' => ['Institution.../js/fees']
            ];
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'student_id';
    }


    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'openemis_no', 'student_id', 'total_fee', 'amount_paid', 'outstanding_fee'
        ]);
        $conditions = array(
            'InstitutionGrades.institution_id' => $this->institutionId
        );
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        if (empty($academicPeriodOptions)) {
            $this->Alert->warning('InstitutionQualityVisits.noPeriods');
        }
        if (empty($this->request->getQuery('academic_period_id'))) {
            //$this->request->getQuery['academic_period_id'] = $this->AcademicPeriods->getCurrent();
            $this->request = $this->request->withQueryParams(['academic_period_id' => $this->AcademicPeriods->getCurrent()]);
        }
        $institutionId = $this->institutionId;
        $this->_selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
        $selectedOption = $this->queryString('academic_period_id', $academicPeriodOptions);
        $Fees = $this;
        $this->advancedSelectOptions($academicPeriodOptions, $selectedOption, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudentFees')),
            'callable' => function ($id) use ($Fees, $institutionId) {
                return $Fees->find()->where(['institution_id'=>$institutionId, 'academic_period_id'=>$id])->count();
            }
        ]);

        $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($institutionId, $this->_selectedAcademicPeriodId);
        $this->_selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
        $this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId);

        $query = $this->InstitutionFees->find()
                ->contain(['InstitutionFeeTypes.FeeTypes']);

        if ($this->_selectedEducationGradeId !== null) {
            $query->where(['InstitutionFees.education_grade_id' => $this->_selectedEducationGradeId]);
        }

        if ($this->_selectedAcademicPeriodId !== null) {
            $query->where(['InstitutionFees.academic_period_id' => $this->_selectedAcademicPeriodId]);
        }

        if ($this->institutionId !== null) {
            $query->where(['InstitutionFees.institution_id' => $this->institutionId]);
        }

        $this->InstitutionFeeEntity = $query->first();


            $queryString = $this->getQueryString();
            $encodedQueryString = $this->paramsEncode($queryString);
            $extra['elements']['custom'] = [
                'name' => 'Institution.StudentFees/controls',
                'data' => [
                    'encodedQueryString' => $encodedQueryString,
                    'academicPeriodOptions'=>$academicPeriodOptions,
                    'gradeOptions'=>$gradeOptions,
                ],
                'options' => [],
                 'order' => 0
            ];
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $conditions = [];

        if ($this->institutionId !== null) {
            $conditions[$this->aliasField('institution_id')] = $this->institutionId;
        }

        if ($this->_selectedAcademicPeriodId !== null) {
            $conditions[$this->aliasField('academic_period_id')] = $this->_selectedAcademicPeriodId;
        }

        if ($this->_selectedEducationGradeId !== null) {
            $conditions[$this->aliasField('education_grade_id')] = $this->_selectedEducationGradeId;
        }

        // Apply conditions to the query
        $query->where($conditions);


        if (!$this->InstitutionFeeEntity) {
            $this->Alert->warning('InstitutionFees.noProgrammeGradeFees');
            $query->where([
                ' EXISTS (
  					SELECT `id`
  					FROM `student_fees`
  					WHERE `student_fees`.`student_id` = ' . $this->aliasField('student_id') . '
  					AND `student_fees`.`institution_fee_id` = 0)'
            ])
            ;
        }

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }


    /******************************************************************************************************************
    **
    ** view action methods
    **
    ******************************************************************************************************************/
    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        if (isset($toolbarButtonsArray['edit'])) {
            $toolbarButtonsArray['addPayment'] = $toolbarButtonsArray['edit'];
            $toolbarButtonsArray['addPayment']['url'][0] = 'add';
            $toolbarButtonsArray['addPayment']['label'] = '<i class="fa kd-add"></i>';
            $toolbarButtonsArray['addPayment']['attr']['title'] = __('Add Payment');
            unset($toolbarButtonsArray['edit']);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->InstitutionFees = TableRegistry::getTableLocator()->get('Institution.InstitutionFees');
        $this->InstitutionFeeEntity = $this->InstitutionFees
        ->find()
        ->select([
            'InstitutionFees.id',
            'InstitutionFees.total',
            'InstitutionFees.institution_id',
            'InstitutionFees.academic_period_id',
            'InstitutionFees.education_grade_id',
            'InstitutionFees.modified_user_id',
            'InstitutionFees.modified',
            'InstitutionFees.created_user_id',
            'InstitutionFees.created'
        ])
        ->contain([
            'InstitutionFeeTypes' => function ($q) {
                return $q  //POCOR-8024 and POCOR-8255
                    ->select([
                        'InstitutionFeeTypes.id',
                        'InstitutionFeeTypes.amount',
                        'InstitutionFeeTypes.institution_fee_id',
                        'FeeTypes.id',
                        'FeeTypes.name'
                    ])
                    ->contain(['FeeTypes'])
                    ->where(['InstitutionFeeTypes.amount >' => 0]) // POCOR-8177 fetch only records where amount > 0
                    ->order(['FeeTypes.order ASC']);
            }
        ])
        ->where([
            'InstitutionFees.education_grade_id' => $entity->education_grade_id,
            'InstitutionFees.academic_period_id' => $entity->academic_period_id,
            'InstitutionFees.institution_id' => $entity->institution_id
        ])
        ->first();
        $feeTypes = [];
        foreach ($this->InstitutionFeeEntity->institution_fee_types as $key=>$obj) {
            $feeTypes[] = [
                'id' => $obj->id,
                'type' => $obj->fee_type->name,
                'fee_type_id' => $obj->fee_type_id,
                'amount' => number_format(floatval($obj->amount), 2)
            ];
        }
        $this->fields['fee_types']['data'] = $feeTypes;
        $this->fields['fee_types']['total'] = $this->onGetTotalFee($event, $entity);

        $this->fields['payments']['fields'] = $this->StudentFeesAbstract->fields;
        $this->fields['payments']['data'] = $this->_getPaymentRecords($entity);
        $this->fields['payments']['total'] = $this->onGetAmountPaid($event, $entity);

        $this->setFieldOrder([
            'academic_period_id', 'education_programme', 'education_grade_id', 'openemis_no', 'student_id', 'fee_types', 'payments', 'outstanding_fee'
        ]);
    }


    /******************************************************************************************************************
    **
    ** add action methods
    **
    ******************************************************************************************************************/
    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $requestData = $this->request->getData();
        if (isset($requestData[$this->getAlias()]['id'])) {
            // pr($requestData);//die;
            // if ($requestData['submit']=='reload') {
            $idKey = $this->aliasField($this->getPrimaryKey());
            if ($this->exists([$idKey => $requestData[$this->getAlias()]['id']])) {
                $entity = $this->find()
                        ->contain($this->_allAssociations())
                        ->where([$idKey => $requestData[$this->getAlias()]['id']])
                        ->first();
                if ($entity) {
                    $this->_addActionSetup($event, $entity);
                    if (isset($requestData['StudentFeesAbstract'])) {
                        foreach ($requestData['StudentFeesAbstract'] as $key=>$record) {
                            /**
                             * these are the rows from the form
                             */
                            $this->fields['payments']['paymentFields'][] = $this->_createVirtualPaymentEntity($entity, $record);
                        }
                    }
                    /**
                     * this is the new blank row
                     */
                    $this->fields['payments']['paymentFields'][] = $this->_createVirtualPaymentEntity($entity, false);
                }
            }
            // }
        } elseif (isset($this->request->getAttribute('params')['pass'][1])) {
            $ids = empty($this->paramsPass(0)) ? [] : $this->paramsDecode($this->paramsPass(0));
            $idKeys = $this->getIdKeys($this, $ids);

            if ($this->exists($idKeys)) {
                $entity = $this
                    ->find()
                    ->contain($this->_allAssociations())
                    ->where($idKeys)
                    ->first();
                if ($entity) {
                    $this->_addActionSetup($event, $entity);
                    $this->fields['payments']['paymentFields'] = $this->_getPaymentRecords($entity);
                }
            }
        } else {
            /**
    		 * should be something else here...
    		 */
            return false;
        }
    }

    private function _addActionSetup(EventInterface $event, Entity $entity)
    {
        $this->fields['student_id']['type'] = 'readonly';
        $this->fields['openemis_no']['type'] = 'readonly';
        $this->fields['education_grade_id']['type'] = 'readonly';
        $this->fields['academic_period_id']['type'] = 'readonly';
        $this->fields['education_programme']['type'] = 'readonly';
        $this->fields['outstanding_fee']['type'] = 'readonly';

        $this->InstitutionFeeEntity = $this->InstitutionFees
        ->find()
        ->select([
            'InstitutionFees.id',
            'InstitutionFees.total',
            'InstitutionFees.institution_id',
            'InstitutionFees.academic_period_id',
            'InstitutionFees.education_grade_id',
            'InstitutionFees.modified_user_id',
            'InstitutionFees.modified',
            'InstitutionFees.created_user_id',
            'InstitutionFees.created'
        ])
        ->contain([
            'InstitutionFeeTypes' => function ($q) {
                return $q  //POCOR-8024 and POCOR-8255
                    ->select([
                        'InstitutionFeeTypes.id',
                        'InstitutionFeeTypes.amount',
                        'InstitutionFeeTypes.institution_fee_id',
                        'FeeTypes.id',
                        'FeeTypes.name'
                    ])
                    ->contain(['FeeTypes'])
                    ->where(['InstitutionFeeTypes.amount >' => 0]) // POCOR-8177 fetch only records where amount > 0
                    ->order(['FeeTypes.order ASC']);
            }
        ])
        ->where([
            'InstitutionFees.education_grade_id' => $entity->education_grade_id,
            'InstitutionFees.academic_period_id' => $entity->academic_period_id,
            'InstitutionFees.institution_id' => $entity->institution_id
        ])
        ->first();
        /**
         * Hidden fields value
         */
        $this->fields['id']['value'] = $entity->id;
        $this->fields['student_id']['value'] = $entity->student_id;
        $this->fields['openemis_no']['value'] = $entity->user->openemis_no;
        $this->fields['education_grade_id']['value'] = $entity->education_grade_id;
        $this->fields['academic_period_id']['value'] = $entity->academic_period_id;
        $this->fields['outstanding_fee']['value'] = $this->getOutstandingFee($entity);

        /**
         * Readonly fields value
         */
        $this->fields['student_id']['attr']['value'] = $entity->user->name;
        $this->fields['openemis_no']['attr']['value'] = $entity->user->openemis_no;
        $this->fields['education_grade_id']['attr']['value'] = $entity->education_grade->name;
        $this->fields['academic_period_id']['attr']['value'] = $entity->academic_period->name;
        $this->fields['education_programme']['attr']['value'] = $entity->education_grade->programme_name;
        $this->fields['outstanding_fee']['attr']['value'] = $this->fields['outstanding_fee']['value'];

        $this->fields['total_fee']['type'] = 'hidden';
        $this->fields['total_fee']['value'] = $this->getTotalFee($entity);

        $this->fields['amount_paid']['type'] = 'hidden';
        $this->fields['amount_paid']['value'] = $this->getAmountPaid($entity);

        $feeTypes = [];
        foreach ($this->InstitutionFeeEntity->institution_fee_types as $key=>$obj) {
            $feeTypes[] = [
                'id' => $obj->id,
                'type' => $obj->fee_type->name,
                'fee_type_id' => $obj->fee_type_id,
                'amount' => number_format(floatval($obj->amount), 2)
            ];
        }
        $this->fields['fee_types']['data'] = $feeTypes;
        $this->fields['fee_types']['total'] = $this->currency.' '.number_format($this->fields['total_fee']['value'], 2);

        $this->StudentFeesAbstract->fields['created_user_id']['type'] = 'hidden';
        $this->StudentFeesAbstract->fields['amount']['type'] = 'string';
        $this->fields['payments']['fields'] = $this->StudentFeesAbstract->fields;
        $this->fields['payments']['amount_paid'] = $this->fields['amount_paid']['value'];
        $this->fields['payments']['currency'] = $this->currency;

        $this->setFieldOrder([
            'academic_period_id', 'education_programme', 'education_grade_id', 'openemis_no', 'student_id', 'fee_types', 'outstanding_fee', 'payments'
        ]);
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $model = $this;
        $StudentFees = $this->StudentFeesAbstract;
        $student_id = $entity->student_id;
        $institution_fee_id = $this->InstitutionFeeEntity->id;
        // die('addBeforeSave');
        $process = function ($model, $entity) use ($event, $data, $StudentFees, $student_id, $institution_fee_id) {
            if (isset($data['StudentFeesAbstract'])) {
                $fees = $StudentFees->newEntities($data['StudentFeesAbstract']);
                $error = false;
                $totalPaid = 0.00;
                foreach ($fees as $key=>$fee) {
                    // if ($fee->getErors()) {
                    //     $error = $fee->getErors();
                    //     $data['StudentFeesAbstract'][$key]['errors'] = $error;
                    // }
                    $totalPaid = (float)$totalPaid + (float)$fee->amount;
                    //    $fees[$key]->amount = number_format($data['StudentFeesAbstract'][$key]['amount'], 2);
                 //    if ($error) {
                    //     $fees[$key]->errors($error);
                    // }
                }
                if (!$error) {
                    if ($totalPaid > $data['StudentFees']['total_fee']) {
                        $error = ['amount_paid'=>'Total amount paid exceeds total fee amount'];
                        $data['StudentFees']['amount_paid'] = ['error'=>$error['amount_paid']];
                    }
                }
                if (!$error) {
                    $count = $StudentFees->find('all')->where([
                        'institution_fee_id' => $institution_fee_id,
                        'student_id' => $student_id
                    ])->count();
                    if ($count>0) {
                        $StudentFees->deleteAll([
                            'institution_fee_id' => $institution_fee_id,
                            'student_id' => $student_id
                        ]);
                    }
                    foreach ($fees as $fee) {
                        $StudentFees->save($fee);
                    }
                    return true;
                } else {
                    $errorMessage='';
                    foreach ($error as $key=>$value) {
                        $errorMessage .= Inflector::classify($key);
                    }
                    $model->log(print_r($error, true), 'debug');
                    /**
                     * unset all field validation except for "academic_period_id" to trigger validation error in ControllerActionComponent
                     */
                    foreach ($model->fields as $value) {
                        if ($value['field'] != 'academic_period_id') {
                            $model->getValidator()->remove($value['field']);
                        }
                    }
                    $model->fields['payments']['paymentFields'] = $fees;
                    $data['hasError'] = true;
                    //$model->request->data = $data->getArrayCopy();
                    $model->request = $model->request->withParsedBody($data->getArrayCopy());
                    return false;
                }
            } else {
                $StudentFees->deleteAll([
                    $StudentFees->aliasField('institution_fee_id') => $institution_fee_id,
                    $StudentFees->aliasField('student_id') => $student_id
                ]);
                return true;
            }
        };
        return $process;
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!isset($data['hasError'])) {
            $this->Alert->success('general.edit.success', ['reset' => true]);
            return $this->controller->redirect($this->url('view', true));
        }
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $extra['toolbarButtons']['back']['url'] = $extra['indexButtons']['view']['url'];
        $extra['toolbarButtons']['back']['label'] = '<i class="fa kd-back"></i>';
        $extra['toolbarButtons']['back']['attr'] = [
                            'class' => 'btn btn-xs btn-default',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'bottom',
                            'escape' => false,
                            'title' => 'Back'
                        ];
        //$this->controller->viewVars['backButton']['url'] = $extra['indexButtons']['view']['url'];
        $this->controller->viewBuilder()->getVars()['backButton']['url'] = $extra['indexButtons']['view']['url'];
    }


    /******************************************************************************************************************
    **
    ** field specific methods
    **
    ******************************************************************************************************************/
    public function onGetOpenemisNo(EventInterface $event, Entity $entity)
    {
        return $this->getOpenemisNo($entity);
    }

    public function getOpenemisNo(Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetTotalFee(EventInterface $event, Entity $entity)
    {
        return $this->currency.' '.number_format($this->getTotalFee($entity), 2);
    }

    public function getTotalFee(Entity $entity)
    {
        if (!is_null($this->InstitutionFeeEntity)) {
            /**
             * PHPOE-1414
             * Not using $this->InstitutionFeeEntity->total anymore since it only saves till 11 digits with 2 decimal places
             * and when a feeType is for example, 999,999,999.99, the rest of the fee types cannot be added saved into the "total" record.
             * Implements a manual count of the extracted feeTypes
             */
            // return $this->currency.' '.(number_format(floatval($this->InstitutionFeeEntity->total), 2));
            $amount = 0.00;
            foreach ($this->InstitutionFeeEntity->institution_fee_types as $key=>$feeType) {
                $amount = (float)$amount + (float)$feeType->amount;
            }
            return $amount;
        } else {
            return 'No fee is set';
        }
    }

    public function onGetAmountPaid(EventInterface $event, Entity $entity)
    {
        return $this->currency.' '.number_format($this->getAmountPaid($entity), 2);
    }

    public function getAmountPaid(Entity $entity)
    {
        if (!is_null($this->InstitutionFeeEntity)) {
            $query = $this->StudentFeesAbstract->find();
            $entityRecord = $query->where([
                    $this->StudentFeesAbstract->aliasField('institution_fee_id') => $this->InstitutionFeeEntity->id,
                    $this->StudentFeesAbstract->aliasField('student_id') => $entity->student_id,
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

    public function onGetOutstandingFee(EventInterface $event, Entity $entity)
    {
        return $this->currency.' '.$this->getOutstandingFee($entity);
    }

    public function getOutstandingFee(Entity $entity)
    {
        if (!is_null($this->InstitutionFeeEntity)) {
            $totalFee = $this->getTotalFee($entity);
            $amountPaid = $this->getAmountPaid($entity);
            return number_format(($totalFee - $amountPaid), 2);
        }
    }

    public function onGetEducationProgramme(EventInterface $event, Entity $entity)
    {
        return $entity->education_grade->programme_name;
    }

    private function _getPaymentRecords(Entity $entity)
    {
        if (!is_null($this->InstitutionFeeEntity)) {
            $query = $this->StudentFeesAbstract->find('all');
            $entityRecords = $query->contain(['CreatedBy'])->where([
                    $this->StudentFeesAbstract->aliasField('institution_fee_id') => $this->InstitutionFeeEntity->id,
                    $this->StudentFeesAbstract->aliasField('student_id') => $entity->student_id,
                ])
            ->toArray()
            ;
            // foreach ($entityRecords as $key => $value) {
            // 	$entityRecords[$key]->amount = number_format((float)$entityRecords[$key]->amount, 2);
            // }
            return $entityRecords;
        }
    }

    private function _createVirtualPaymentEntity($entity, $requestData)
    {
        $data = [
            'id' => ($requestData) ? $requestData['id'] : '',
            'amount' => ($requestData) ? $requestData['amount'] : '',
            'payment_date' => ($requestData) ? $requestData['payment_date'] : '',
            'comments' => ($requestData) ? $requestData['comments'] : '',
            'student_id' => $entity->student_id,
            'institution_fee_id' => $this->InstitutionFeeEntity->id,
        ];
        $studenFee = $this->StudentFeesAbstract->newEmptyEntity();
        $studenFee = $this->StudentFeesAbstract->patchEntity($studenFee, $data, ['validate' => false]);
        if (!$requestData) {
            $studenFee->amount = number_format((float)$studenFee->amount, 2);
        }
        return $studenFee;
    }

    private function _allAssociations()
    {
        $omitForeignKeys = ['modified_user_id', 'created_user_id'];
        $associations = [];
        foreach ($this->associations() as $assoc) {
            if (!in_array($assoc->getForeignKey(), $omitForeignKeys)) {
                $associations[] = $assoc->getTarget()->getAlias();
            }
        }
        return $associations;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $newButtons = [];
        if (isset($buttons['view'])) {
            $newButtons['view'] = $buttons['view'];
        }
        $institutionId = $this->getQueryString('institution_id');
        if (isset($buttons['edit'])) {
            $addPayment = $buttons['edit'];
            $addPayment['label'] = '<i class="fa kd-add"></i>' . __('Add Payment');
            $newButtons['addPayment'] = $addPayment;
            $newButtons['addPayment']['url'] = [
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'add',
                $this->paramsEncode(['id' => $entity->id, 'institution_id'=> $institutionId])
            ];
        }

        return $newButtons;
    }
    //POCOR-6165 start
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
     {
        //added if acacdemic_period is not received
        if (empty($this->request->getQuery('academic_period_id'))) {
            //$this->request->getQuery('academic_period_id') = $this->AcademicPeriods->getCurrent();
            $this->request = $this->request->withQueryParams(['academic_period_id' => $this->AcademicPeriods->getCurrent()]);
        }
        //$institutionId = $this->Session->read('Institution.Institutions.id');
        //$institutionId  = $this->getInstitutionID();

        $id = $this->request->getAttribute('params')['pass'][1];
            $DecodedQueryString = $this->paramsDecode($id);
            // print_r($DecodedQueryString);exit;
            $institutionId = $DecodedQueryString['institution_id'];

        //$institutionId = $this->request->getQuery('institution_id');
        $academicPeriod = $this->request->getQuery('academic_period_id');
        $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptions($institutionId,  $academicPeriod);
        $educationGradeId = $this->queryString('education_grade_id', $gradeOptions);
        $this->advancedSelectOptions($gradeOptions, $this->_selectedEducationGradeId);
        $query->select([
            'student_id' => $this->Users->aliasField('id'),
            'student' => $this->Users->find()->func()->concat([
                'first_name' => 'literal',
                 " ",
                'last_name' => 'literal'
            ]),
            'openemis' =>$this->Users->aliasField('openemis_no')
           ])

        ->LeftJoin([$this->Users->getAlias() => $this->Users->getTable()],[
            $this->Users->aliasField('id').' = ' . 'StudentFees.student_id'
        ])

        ->where(['StudentFees.academic_period_id' =>  $academicPeriod,
             'StudentFees.institution_id' =>  $institutionId,
             'StudentFees.education_grade_id' =>   $educationGradeId
        ]);
        //echo"<pre>";print_r($query);exit;

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {

                    $InstitutionFees= TableRegistry::getTableLocator()->get('Institution.InstitutionFees');
                    
                    $InstitutionFeeEntity = $InstitutionFees
                                             ->find()
                                             ->contain('InstitutionFeeTypes.FeeTypes')
                                             ->where([
                                                     'InstitutionFees.education_grade_id' => $row['education_grade_id'],
                                                     'InstitutionFees.academic_period_id' => $row['academic_period_id'],
                                                     'InstitutionFees.institution_id' => $row['institution_id']
                                             ])
                                             ->first();

                    $tableLocator = new TableLocator();
                    $StudentFees= $tableLocator->get('student_fees');

                    //  $StudentFees= TableRegistry::getTableLocator()->get('Student.StudentFees');
                    $StudentFeeEntity = $StudentFees
                            ->find()
                            ->select([
                                "amount"=> $StudentFees->find()->func()->sum('amount')
                            ])
                            ->where([
                                $StudentFees->aliasField('institution_fee_id') =>$InstitutionFeeEntity->id,
                                $StudentFees->aliasField('student_id') => $row['student_id']

                                ])
                                ->toArray();

                    //total fee
                    $row->total_fee='00';
                    if(isset($InstitutionFeeEntity->total)){
                        $row->total_fee=$InstitutionFeeEntity->total;
                    }
                    //amount paid
                    $row->amount_paid="00";
                    if(!empty($InstitutionFeeEntity)){
                        $tableLocator = new TableLocator();
                        $StudentFees= $tableLocator->get('student_fees');
                        $StudentFeeEntity = $StudentFees
                                                ->find()
                                                ->select([
                                                    "amount"=> $StudentFees->find()->func()->sum('amount')
                                                ])
                                                ->where([
                                                    $StudentFees->aliasField('institution_fee_id') =>$InstitutionFeeEntity->id,
                                                    $StudentFees->aliasField('student_id IS') => $row['student_id']

                                                 ])
                                                 ->toArray();
                        if(!empty($StudentFeeEntity)){
                            if($StudentFeeEntity[0]['amount']){
                                $row->amount_paid=$StudentFeeEntity[0]['amount'];
                            }
                        }
                    }
                    //outstanding fee
                    $row['outstanding_fee']="00";
                    $row['outstanding_fee']= $row['total_fee']-$row['amount_paid'];

                return $row;

           });
        });


   }

   public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key' => 'Users.openemis',
            'field' => 'openemis',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraField[] = [
            'key' => 'Users.name',
            'field' => 'student',
            'type' => 'string',
            'label' => __('Student')
        ];

        $extraField[] = [
            'key' => 'InstitutionFees.total',
            'field' => 'total_fee',
            'type' => 'integer',
            'label' => __('Total Fee')
        ];

        $extraField[] = [
            'key' => 'StudentFees.amount',
            'field' => 'amount_paid',
            'type' => 'integer',
            'label' => __('Amount Paid')
        ];

        $extraField[] = [
            'key' => 'outstanding_fee',
            'field' => 'outstanding_fee',
            'type' => 'string',
            'label' => __('Outstanding Fee')
        ];
        $fields->exchangeArray($extraField);
    }
    //POCOR-6165 end

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'total_fee':
                return __('Total Fee');
            case 'registration_number':
                return __('Registration Number');
            case 'openemis_no':
                return __('OpenEMIS ID');
            case 'student_id':
                return __('Student');
            case 'date_of_birth':
                return __('Date Of Birth');
            case 'registration_start_date':
                return __('Registration Start Date');
            case 'registration_end_date':
                return __('Registration End Date');
            case 'description':
                    return __('Description');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
