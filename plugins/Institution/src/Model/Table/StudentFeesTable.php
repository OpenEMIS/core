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
use Cake\Utility\Inflector;

use Cake\Log\Log;

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

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);

        if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
                    'index' => true,
                    'view' => true,
                    'edit' => true,
                    'add' => false,
                    'remove' => false,
                    'reorder' => false
                ],
            ]);
        }

        $this->addBehavior('User.AdvancedNameSearch');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $this->institutionId = $session->read('Institution.Institutions.id');

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $this->currency = $ConfigItems->value('currency');
        $this->StudentFeesAbstract = TableRegistry::get('Institution.StudentFeesAbstract');
        $this->InstitutionFees = TableRegistry::get('Institution.InstitutionFees');

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
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit' || $action == 'add') {
            $includes['fees'] = [
                'include' => true,
                'js' => ['Institution.../js/fees']
            ];
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'openemis_no';
        $searchableFields[] = 'student_id';
    }


    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
        if (empty($this->request->query['academic_period_id'])) {
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
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

        $this->InstitutionFeeEntity = $this->InstitutionFees
            ->find()
            ->contain(['InstitutionFeeTypes.FeeTypes'])
            ->where([
                'InstitutionFees.education_grade_id' => $this->_selectedEducationGradeId,
                'InstitutionFees.academic_period_id' => $this->_selectedAcademicPeriodId,
                'InstitutionFees.institution_id' => $this->institutionId
            ])
            ->first()
            ;

        $extra['elements']['custom'] = [
            'name' => 'Institution.StudentFees/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'gradeOptions'=>$gradeOptions,
            ],
            'options' => [],
             'order' => 0
        ];
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
        ->where([
            $this->aliasField('institution_id') => $this->institutionId,
            $this->aliasField('academic_period_id') => $this->_selectedAcademicPeriodId,
            $this->aliasField('education_grade_id') => $this->_selectedEducationGradeId,
        ])
        ;

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
    public function viewBeforeAction(Event $event, ArrayObject $extra)
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->InstitutionFeeEntity = $this->InstitutionFees
                ->find()
                ->contain(['InstitutionFeeTypes.FeeTypes'])
                ->where([
                    'InstitutionFees.education_grade_id' => $entity->education_grade_id,
                    'InstitutionFees.academic_period_id' => $entity->academic_period_id,
                    'InstitutionFees.institution_id' => $entity->institution_id
                ])
                ->first()
                ;

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
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $requestData = $this->request->data;
        if (isset($requestData[$this->alias()]['id'])) {
            // pr($requestData);//die;
            // if ($requestData['submit']=='reload') {
            $idKey = $this->aliasField($this->primaryKey());
            if ($this->exists([$idKey => $requestData[$this->alias()]['id']])) {
                $entity = $this->find()
                        ->contain($this->_allAssociations())
                        ->where([$idKey => $requestData[$this->alias()]['id']])
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
        } elseif (isset($this->request->params['pass'][1])) {
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

    private function _addActionSetup(Event $event, Entity $entity)
    {
        $this->fields['student_id']['type'] = 'readonly';
        $this->fields['openemis_no']['type'] = 'readonly';
        $this->fields['education_grade_id']['type'] = 'readonly';
        $this->fields['academic_period_id']['type'] = 'readonly';
        $this->fields['education_programme']['type'] = 'readonly';
        $this->fields['outstanding_fee']['type'] = 'readonly';

        $this->InstitutionFeeEntity = $this->InstitutionFees
                ->find()
                ->contain('InstitutionFeeTypes.FeeTypes')
                ->where([
                    'InstitutionFees.education_grade_id' => $entity->education_grade_id,
                    'InstitutionFees.academic_period_id' => $entity->academic_period_id,
                    'InstitutionFees.institution_id' => $entity->institution_id
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

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $model = $this;
        $StudentFees = $this->StudentFeesAbstract;
        $student_id = $entity->student_id;
        $institution_fee_id = $this->InstitutionFeeEntity->id;
        // die('addBeforeSave');
        $process = function ($model, $entity) use ($event, $data, $StudentFees, $student_id, $institution_fee_id) {
            if (array_key_exists('StudentFeesAbstract', $data)) {
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
                    $data['hasError'] = true;
                    $model->request->data = $data->getArrayCopy();
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

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if (!array_key_exists('hasError', $data)) {
            $this->Alert->success('general.edit.success', ['reset' => true]);
            return $this->controller->redirect($this->url('view', true));
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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
        $this->controller->viewVars['backButton']['url'] = $extra['indexButtons']['view']['url'];
    }


    /******************************************************************************************************************
    **
    ** field specific methods
    **
    ******************************************************************************************************************/
    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $this->getOpenemisNo($entity);
    }

    public function getOpenemisNo(Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetTotalFee(Event $event, Entity $entity)
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

    public function onGetAmountPaid(Event $event, Entity $entity)
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

    public function onGetOutstandingFee(Event $event, Entity $entity)
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

    public function onGetEducationProgramme(Event $event, Entity $entity)
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
        $studenFee = $this->StudentFeesAbstract->newEntity();
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
            if (!in_array($assoc->foreignKey(), $omitForeignKeys)) {
                $associations[] = $assoc->target()->alias();
            }
        }
        return $associations;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $newButtons = [];
        if (array_key_exists('view', $buttons)) {
            $newButtons['view'] = $buttons['view'];
        }

        if (array_key_exists('edit', $buttons)) {
            $addPayment = $buttons['edit'];
            $addPayment['label'] = '<i class="fa kd-add"></i>' . __('Add Payment');
            $newButtons['addPayment'] = $addPayment;
            $newButtons['addPayment']['url'] = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'add',
                $this->paramsEncode(['id' => $entity->id])
            ];
        }

        return $newButtons;
    }
}
