<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\View\Helper\IdGeneratorTrait;
use App\Model\Table\ControllerActionTable;

class RecipientPaymentsTable extends ControllerActionTable
{
    use IdGeneratorTrait;

    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_payment_structures');
        parent::initialize($config);

        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true,  'saveStrategy' => 'replace']);
        $this->hasMany('RecipientDisbursements', ['className' => 'Scholarship.RecipientDisbursements', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true,  'saveStrategy' => 'replace']);

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.onUpdateIncludes'] = 'onUpdateIncludes';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // set header
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $recipientName = $this->Recipients->get($recipientId)->name;
        $this->controller->set('contentHeader', $recipientName . ' - ' . __('Disbursements'));
        // set tabs
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Disbursements');

        $this->field('recipient_id', ['type' => 'hidden']);
        $this->field('scholarship_id', ['type' => 'hidden']);
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $title = __('Disbursements');

        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $recipientName = $this->Recipients->get($recipientId)->name;

        $Navigation->addCrumb('Recipients', ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipients', 'action' => 'index']);
        $Navigation->addCrumb($recipientName);
        $Navigation->addCrumb($title);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryString = $this->request->query['queryString'];
        $recipientId = $this->paramsDecode($queryString)['recipient_id'];
        $scholarshipId = $this->paramsDecode($queryString)['scholarship_id'];

        $query->where([
            $this->aliasField('scholarship_id') => $scholarshipId,
            $this->aliasField('recipient_id') => $recipientId
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => 'false']);
        $this->field('estimated_amount', ['after' => 'name']);
        $this->field('disbursed_amount', ['after' => 'estimated_amount']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'RecipientDisbursements.DisbursementCategories', 'AcademicPeriods', 'ScholarshipRecipients'
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity) 
    {
        $this->setupFields($entity);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function setupFields($entity = null)
    {
        $this->field('code', ['type' => 'disabled']);
        $this->field('name', ['type' => 'disabled']);

        $this->field('academic_period_id', [
            'type' => 'readonly',
            'attr' => ['value' => $entity->academic_period->name],
            'after' => 'name'
        ]);

         $this->field('estimated_amount', [
            'type' => 'disabled',
            'after' => 'academic_period_id',
            'attr' => ['label' => $this->Scholarships->addCurrencySuffix('Estimated Amount')],
            'entity' => $entity
        ]);

         $this->field('approved_amount', [
            'type' => 'disabled',
            'after' => 'estimated_amount',
            'visible' => ['view' => false, 'edit' => true],
            'attr' => [
                'label' => $this->Scholarships->addCurrencySuffix('Approved Award Amount'), 
                'value' => $entity->scholarship_recipient->approved_amount
            ]
        ]);

        $this->field('balance_amount', [
            'type' => 'disabled',
            'after' => 'approved_amount',
            'visible' => ['view' => false, 'edit' => true],
            'attr' => ['label' => $this->Scholarships->addCurrencySuffix('Balance Amount')],
            'entity' => $entity
        ]);
       
        $this->field('disbursement_category_id', [
            'type' => 'custom_disbursement_category',
            'after' => 'balance_amount'
        ]);
    }

    // index fields
    public function onGetEstimatedAmount(Event $event, Entity $entity)
    {
        $value = $this->RecipientPaymentStructureEstimates->getEstimatedAmount($entity->id);
        return $value;
    }

    public function onGetDisbursedAmount(Event $event, Entity $entity)
    {
        $query = $this->RecipientDisbursements->find();
        $RecipientDisbursements = $query->where([
                $this->RecipientDisbursements->aliasField('scholarship_recipient_payment_structure_id') => $entity->id
            ])
            ->select([
                    'disbursed_amt' => $query->func()->sum('amount')
                ])
            ->first();

        $value = $RecipientDisbursements->disbursed_amt;

        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'estimated_amount') {
            return $this->Scholarships->addCurrencySuffix('Estimated Amount');
        } else if ($field == 'disbursed_amount') {
            return $this->Scholarships->addCurrencySuffix('Disbursed Amount');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
        
    }

    // edit fields
    public function onUpdateFieldEstimatedAmount(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $value = $this->RecipientPaymentStructureEstimates->getEstimatedAmount($entity->id);
            $attr['attr']['value'] =  $value;
        }
        return $attr;
    }

    public function onUpdateFieldBalanceAmount(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
           
            $approvedAmt = $entity->scholarship_recipient->approved_amount;
            
            $conditions = [
                'recipient_id' => $entity->recipient_id,
                'scholarship_id' => $entity->scholarship_id,
                'scholarship_recipient_payment_structure_id <>' => $entity->id
            ];

            $query = $this->RecipientDisbursements->find();
            $RecipientDisbursements = $query->where([$conditions])
                ->select([
                    'disbursed_amt' => $query->func()->sum('amount')
                ])
                ->first();

            $balance = $approvedAmt - ($RecipientDisbursements->disbursed_amt);
             
            $attr['attr']['value'] =  $balance;
        }

        return $attr;
    }

    public function addEditOnSelectDisbursementCategory(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $fieldKey = 'recipient_disbursements';

        if (!isset($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
      
        if (isset($data[$this->alias()]['disbursement_category_id']) && !empty($data[$this->alias()]['disbursement_category_id'])) {
            $selectedDisbursementCategory = $data[$this->alias()]['disbursement_category_id'];
            $disbursementCategoryEntity = TableRegistry::get('Scholarship.DisbursementCategories')->get($selectedDisbursementCategory);
            $semesterOptions = TableRegistry::get('Scholarship.Semesters')->getList()->toArray();

            $data[$this->alias()][$fieldKey][] = [
                'scholarship_disbursement_category_id' => $disbursementCategoryEntity->id,
                'scholarship_disbursement_category_name' => $disbursementCategoryEntity->name,
                'disbursement_date' => '',
                'amount' => '',
                'comments' => '',
                'scholarship_semester_id' => key($semesterOptions),
                'recipient_id' => $recipientId,
                'scholarship_id' => $scholarshipId
            ];
        }

        $data[$this->alias()]['disbursement_category_id'] = '';

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'RecipientDisbursements' => ['validate' => false]
        ];
    }

    public function onGetCustomDisbursementCategoryElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            // No implementation yet
        } elseif ($action == 'view') {
            $tableHeaders = [__('Category') , __('Disbursement Date'), $this->Scholarships->addCurrencySuffix('Amount'), __('Semester'), __('Comments'), ''];
            $tableCells = [];

            $Semesters = TableRegistry::get('Scholarship.Semesters');
            $totalAmt = 0;

            if ($entity->has('recipient_disbursements')) {
                foreach ($entity->recipient_disbursements as $key => $obj) {
                    $semesterEntity = $Semesters->get($obj->scholarship_semester_id);

                    $rowData = [];
                    $rowData[] = $obj->disbursement_category->name;
                    $rowData[] = $this->formatDate($obj->disbursement_date);
                    $rowData[] = $obj->amount;
                    $rowData[] = $semesterEntity['name'];
                    $rowData[] = nl2br($obj->comments);

                    $tableCells[] = $rowData;
                    $totalAmt += $obj->amount;
                }
            }

            $tableFooters = [$this->Scholarships->addCurrencySuffix('Total'), '', $totalAmt, '', '', ''];

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['tableFooters'] = $tableFooters;
        } elseif ($action == 'add' || $action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($attr['model'] . '.recipient_disbursements');

            $cellCount = 0;
            $tableHeaders = [__('Category') , __('Disbursement Date'), $this->Scholarships->addCurrencySuffix('Amount'), __('Semester'), __('Comments'), ''];
            $tableCells = [];

            $arrayRecipientDisbursements = [];
            if ($this->request->is(['get'])) {
                // edit
                if (!$entity->isNew() && $entity->has('recipient_disbursements')) {
                    foreach ($entity->recipient_disbursements as $key => $obj) {

                        $disbursementCategoryEntity = TableRegistry::get('Scholarship.DisbursementCategories')->get($obj->scholarship_disbursement_category_id);

                        $arrayRecipientDisbursements[] = [
                            'scholarship_disbursement_category_id' => $obj->scholarship_disbursement_category_id,
                            'scholarship_disbursement_category_name' => $disbursementCategoryEntity->name,
                            'disbursement_date' => $obj->disbursement_date,
                            'amount' => $obj->amount,
                            'comments' => $obj->comments,
                            'scholarship_semester_id' => $obj->scholarship_semester_id,
                            'recipient_id' => $obj->recipient_id,
                            'scholarship_id' => $obj->scholarship_id
                        ];
                    }
                }
            } elseif ($this->request->is(['post', 'put'])) {
            
                $requestData = $this->request->data;

                if (isset($requestData[$this->alias()]['recipient_disbursements'])) {
                    foreach ($requestData[$this->alias()]['recipient_disbursements'] as $key => $obj) {
                        $arrayRecipientDisbursements[] = $obj;
                    }
                
                }

            }

            // options
            $DisbursementCategory = TableRegistry::get('Scholarship.DisbursementCategories');
            $disbursementCategoryOptions = $DisbursementCategory->getList()->toArray();

            if (!empty($arrayRecipientDisbursements)) {
                foreach ($arrayRecipientDisbursements as $key => $obj) {
                    $fieldPrefix = $attr['model'] . '.recipient_disbursements.' . $cellCount++;
                    
                    $cellData = $obj['scholarship_disbursement_category_name'];
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_disbursement_category_id", ['value' => $obj['scholarship_disbursement_category_id']]);
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_disbursement_category_name", ['value' => $obj['scholarship_disbursement_category_name']]);
                    $cellData .= $form->hidden($fieldPrefix.".recipient_id", ['value' => $obj['recipient_id']]);
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_id", ['value' => $obj['scholarship_id']]);
                
                    $value = $obj['disbursement_date'] ? $obj['disbursement_date'] : null;
                    $_options = [
                        'format' => 'dd-mm-yyyy',
                        'todayBtn' => 'linked',
                        'orientation' => 'auto',
                        'autoclose' => true,
                    ];
                    $attr['date_options'] = $_options;

                    $attr['fieldName'] = $fieldPrefix.".".'disbursement_date';
                    
                    if (array_key_exists('fieldName', $attr)) {
                        $attr['id'] = $this->_domId($attr['fieldName']);
                    }

                    $defaultDate = date('d-m-Y');
                    if (!isset($attr['default_date'])) {
                        $attr['default_date'] = $defaultDate;
                    }   

                   if (!array_key_exists('value', $attr)) {
                        if (!is_null($value)) {
                            if ($value instanceof Time || $value instanceof Date) {
                                $attr['value'] = $value->format('d-m-Y');
                            } else {
                                $attr['value'] = date('d-m-Y', strtotime($value));
                            }
                        } else if ($attr['default_date']) {
                            $attr['value'] = $attr['default_date'];
                        }
                    } else {    
                        if ($attr['value'] instanceof Time || $value instanceof Date) {
                            $attr['value'] = $attr['value']->format('d-m-Y');
                        } else {
                            $attr['value'] = date('d-m-Y', strtotime($attr['value']));
                        }
                    }
                    $attr['class'] = 'no-margin-bottom';
                    $event->subject()->viewSet('datepicker', $attr);
                    $cellInput = $event->subject()->renderElement('ControllerAction.bootstrap-datepicker/datepicker_input', ['attr' => $attr]);   
                    unset($attr['value']);

                    $semesterList = TableRegistry::get('Scholarship.Semesters')->getList()->toArray();
                    if (empty($semesterList)) {
                        $semesterOptions = ['' => $this->getMessage('general.select.noOptions')];
                    } else {
                        $semesterOptions = ['' => '-- '.__('Select').' --'] + $semesterList;
                    }
                    $semesterInputOptions = [
                        'type' => 'select',
                        'label' => false,
                        'options' => $semesterOptions,
                        'default' => $obj['scholarship_semester_id'],
                        'value' => $obj['scholarship_semester_id']
                    ];
                    
                    $semesterCellData = $form->input("$fieldPrefix.scholarship_semester_id", $semesterInputOptions);
                    $amountCellData = $form->input("$fieldPrefix.amount", ['type' => 'number']);
                    $commentCellData = $form->input("$fieldPrefix.comments", ['type' => 'textarea']);

                    $rowData = [];
                    $rowData[] = $cellData;
                    $rowData[] = $cellInput;
                    $rowData[] = $amountCellData;
                    $rowData[] = $semesterCellData;
                    $rowData[] = $commentCellData;
                    
                    $rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

                    $tableCells[] = $rowData;
                }
            }

            $attr['options'] = $disbursementCategoryOptions;
            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('../ControllerAction/table_with_dropdown', ['attr' => $attr]);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if (array_key_exists($this->alias(), $requestData)) {
            if (!array_key_exists('recipient_disbursements', $requestData[$this->alias()])) {
                    $requestData[$this->alias()]['recipient_disbursements'] = []; 
            } 
        }
    }
    
    public function beforeSave(Event $event, Entity $entity, ArrayObject $data) 
    {  
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');

        $approvedAmt = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId])->approved_amount;        

        $conditions = [
                'recipient_id' => $entity->recipient_id,
                'scholarship_id' => $entity->scholarship_id,
                'scholarship_recipient_payment_structure_id <>' => $entity->id
            ];

        $query = $this->RecipientDisbursements->find();
        $RecipientDisbursements = $query->where([$conditions])
            ->select([
                'disbursed_amt' => $query->func()->sum('amount')
            ])
            ->first();

        $conditions = [
                'recipient_id' => $entity->recipient_id,
                'scholarship_id' => $entity->scholarship_id,
                'scholarship_recipient_payment_structure_id <>' => $entity->id
            ];

        $balance = $approvedAmt - ($RecipientDisbursements->disbursed_amt);

        $totalAmt = 0;
        if ($entity->has('recipient_disbursements')) {
            foreach ($entity->recipient_disbursements as $key => $obj) {
                $totalAmt += $obj['amount'];
            }
        }

        if ($totalAmt > $balance) {
            $entity->errors('balance_amount', __('Disbursed Amount must not exceed Balance Amount'));
            return false;
        }
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
        $includes['datepicker']['include'] = true;
    }

}
