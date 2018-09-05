<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Controller\Component;
use Cake\View\Helper\IdGeneratorTrait;
use App\Model\Table\ControllerActionTable;

class RecipientPaymentStructuresTable extends ControllerActionTable
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

        $this->addBehavior('Excel', [
            'pages' => ['index'],
            'autoFields' => false
        ]);
        
        $this->setDeleteStrategy('restrict');
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
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');

        // set header
        $recipientName = $this->Recipients->get($recipientId)->name;
        $this->controller->set('contentHeader', $recipientName . ' - ' . __('Payment Structures'));
        // set tabs
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'PaymentStructures');
        
        $entity = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId]);   
        if(empty($entity->approved_amount)) {
            $this->toggle('add', false);
            $this->Alert->warning($this->aliasField('noApprovedAmount'));
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');

        $query
            ->select([
                'name' => $this->aliasField('name'),
                'recipient_id' => $this->aliasField('recipient_id'),
                'scholarship_id' => $this->aliasField('scholarship_id'),
                'academic_period_id' => $this->aliasField('academic_period_id')
            ])
            ->contain([
                'Recipients' => [
                    'fields' => [
                        'Recipients.id',
                        'Recipients.first_name',
                        'Recipients.middle_name',
                        'Recipients.third_name',
                        'Recipients.last_name',
                        'Recipients.preferred_name'
                    ]
                ],
                'Scholarships' => [
                    'fields' => [
                        'Scholarships.id',
                        'Scholarships.name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.id',
                        'AcademicPeriods.name'
                    ]
                ],
            ])
            ->matching('ScholarshipRecipients', function ($q) {
                return $q->select([
                    'approved_amount' => 'ScholarshipRecipients.approved_amount',
                ]);
            })
            ->matching('RecipientPaymentStructureEstimates.DisbursementCategories', function ($q) {
                return $q->select([
                    'estimated_disbursement_date' => 'RecipientPaymentStructureEstimates.estimated_disbursement_date',
                    'estimated_amount' => 'RecipientPaymentStructureEstimates.estimated_amount',
                    'comments' => 'RecipientPaymentStructureEstimates.comments',
                    'disbursement_category' => 'DisbursementCategories.name'
                ]);
            })
            ->where([
                $this->aliasField('recipient_id') => $recipientId,
                $this->aliasField('scholarship_id') => $scholarshipId
            ])
            ->order(['academic_period_id', 'estimated_disbursement_date']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {
        $newArray = [];
        $newArray[] = [
            'key' => 'RecipientPaymentStructures.recipient_id',
            'field' => 'recipient_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructures.scholarship_id',
            'field' => 'scholarship_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructures.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructures.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Payment Structure Name')
        ];

        $newArray[] = [
            'key' => 'DisbursementCategories.name',
            'field' => 'disbursement_category',
            'type' => 'string',
            'label' => __('Disbursement Category')
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructureEstimates.estimated_amount',
            'field' => 'estimated_amount',
            'type' => 'integer',
            'label' => $this->Scholarships->addCurrencySuffix('Estimated Amount')
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructureEstimates.estimated_disbursement_date',
            'field' => 'estimated_disbursement_date',
            'type' => 'date',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'RecipientPaymentStructureEstimates.comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'ScholarshipRecipients.approved_amount',
            'field' => 'approved_amount',
            'type' => 'string',
            'label' => 'Approved Award Amount'
        ];

        $fields->exchangeArray($newArray);
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $title = __('Payment Structures');

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
        $this->field('recipient_id', ['type' => 'hidden']);
        $this->field('scholarship_id', ['type' => 'hidden']);
        $this->field('estimated_amount', ['after' => 'academic_period_id']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
        $entity->recipient_id = $recipientId;
        $entity->scholarship_recipient = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId]);
        $entity->scholarship_id = $scholarshipId;
        $entity->scholarship = $this->Scholarships->get($scholarshipId);
   
        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'RecipientPaymentStructureEstimates.DisbursementCategories'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function setupFields($entity = null)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'options' => $this->AcademicPeriods->getYearList(['isEditable' => true]),
            'after' => 'name'
        ]);

        $this->field('scholarship_name', [
            'type' => 'disabled',
            'fieldName' => 'scholarship.name',
            'attr' => ['label' => __('Scholarship')],
            'after' => 'academic_period_id'
        ]);

        $this->field('scholarship_id', [
            'type' => 'hidden'
        ]);

        $this->field('recipient_id', [
            'type' => 'hidden'
        ]);

        $this->field('approved_amount', [
            'type' => 'disabled',
            'fieldName' => 'scholarship_recipient.approved_amount',
            'after' => 'scholarship_name',
            'attr' => ['label' => $this->Scholarships->addCurrencySuffix('Approved Award Amount')]
        ]);

        $this->field('balance_amount', [
            'type' => 'disabled',
            'after' => 'approved_amount',
            'visible' => ['view' => false, 'add' => true, 'edit' => true],
            'attr' => ['label' => $this->Scholarships->addCurrencySuffix('Balance Amount')],
            'entity' => $entity
        ]);

        $this->field('annual_award_amount', [
            'type' => 'disabled',
            'after' => 'balance_amount',
            'visible' => ['view' => false, 'add' => true, 'edit' => true],
            'attr' => ['label' => $this->Scholarships->addCurrencySuffix('Annual Award Amount')],
            'entity' => $entity
        ]);
       
        $this->field('disbursement_category_id', [
            'type' => 'custom_disbursement_category',
            'after' => 'annual_award_amount'
        ]);
    }

    public function addEditOnSelectDisbursementCategory(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $fieldKey = 'recipient_payment_structure_estimates';

        if (!isset($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');

        if (isset($data[$this->alias()]['disbursement_category_id']) && !empty($data[$this->alias()]['disbursement_category_id'])) {
            $selectedDisbursementCategory = $data[$this->alias()]['disbursement_category_id'];
            $disbursementCategoryEntity = TableRegistry::get('Scholarship.DisbursementCategories')->get($selectedDisbursementCategory);
            $data[$this->alias()][$fieldKey][] = [
                'scholarship_disbursement_category_id' => $disbursementCategoryEntity->id,
                'scholarship_disbursement_category_name' => $disbursementCategoryEntity->name,
                'estimated_disbursement_date' => '',
                'estimated_amount' => null,
                'comments' => '',
                'recipient_id' => $recipientId,
                'scholarship_id' => $scholarshipId
            ];
        }
        $data[$this->alias()]['disbursement_category_id'] = '';

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'RecipientPaymentStructureEstimates' => ['validate' => false]
        ];
    }

    // index
    public function onGetEstimatedAmount(Event $event, Entity $entity)
    {
        $value = $this->RecipientPaymentStructureEstimates->getEstimatedAmount($entity->id);
        return $value;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'estimated_amount') {
            return $this->Scholarships->addCurrencySuffix('Estimated Amount');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //view
    public function onGetScholarshipName(Event $event, Entity $entity)
    {
        return $entity->scholarship->name;
    }

    public function onGetApprovedAmount(Event $event, Entity $entity)
    {
         return $entity->scholarship_recipient->approved_amount;
    }

    public function onGetCustomDisbursementCategoryElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            // No implementation yet
        } elseif ($action == 'view') {
            $tableHeaders = [__('Category') , __('Estimated Disbursement Date'), $this->Scholarships->addCurrencySuffix('Estimated Amount'), __('Comments')];
            $tableCells = [];

            $totalAmt = 0;
            if ($entity->has('recipient_payment_structure_estimates')) {
                foreach ($entity->recipient_payment_structure_estimates as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj->disbursement_category->name;
                    $rowData[] = $this->formatDate($obj->estimated_disbursement_date);
                    $rowData[] = $obj->estimated_amount;
                    $rowData[] = nl2br($obj->comments);

                    $tableCells[] = $rowData;
                    $totalAmt += $obj->estimated_amount;
                }
            }

            $tableFooters = [$this->Scholarships->addCurrencySuffix('Total'), '', $totalAmt, ''];

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['tableFooters'] = $tableFooters;
        } elseif ($action == 'add' || $action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($attr['model'] . '.recipient_payment_structure_estimates');

            $cellCount = 0;
            $tableHeaders = [__('Category') , __('Estimated Disbursement Date'), $this->Scholarships->addCurrencySuffix('Estimated Amount'), __('Comments'), ''];
            $tableCells = [];

            $arrayPaymentStructureEstimates = [];
            if ($this->request->is(['get'])) {
                // edit
                if (!$entity->isNew() && $entity->has('recipient_payment_structure_estimates')) {
                    foreach ($entity->recipient_payment_structure_estimates as $key => $obj) {

                        $disbursementCategoryEntity = TableRegistry::get('Scholarship.DisbursementCategories')->get($obj->scholarship_disbursement_category_id);

                        $arrayPaymentStructureEstimates[] = [
                            'scholarship_disbursement_category_id' => $obj->scholarship_disbursement_category_id,
                            'scholarship_disbursement_category_name' => $disbursementCategoryEntity->name,
                            'estimated_disbursement_date' => $obj->estimated_disbursement_date,
                            'estimated_amount' => $obj->estimated_amount,
                            'comments' => $obj->comments,
                            'recipient_id' => $obj->recipient_id,
                            'scholarship_id' => $obj->scholarship_id
                        ];
                    }
                }
            } elseif ($this->request->is(['post', 'put'])) {
            
                $requestData = $this->request->data;

                if (isset($requestData[$this->alias()]['recipient_payment_structure_estimates'])) {
                    foreach ($requestData[$this->alias()]['recipient_payment_structure_estimates'] as $key => $obj) {
                        $arrayPaymentStructureEstimates[] = $obj;
                    }
                
                }

            }
            
            // options
            $DisbursementCategory = TableRegistry::get('Scholarship.DisbursementCategories');
            $disbursementCategoryOptions = $DisbursementCategory->getList()->toArray();

            if (!empty($arrayPaymentStructureEstimates)) {
                foreach ($arrayPaymentStructureEstimates as $key => $obj) {
                    $fieldPrefix = $attr['model'] . '.recipient_payment_structure_estimates.' . $cellCount++;
                    
                    $cellData = $obj['scholarship_disbursement_category_name'];
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_disbursement_category_id", ['value' => $obj['scholarship_disbursement_category_id']]);
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_disbursement_category_name", ['value' => $obj['scholarship_disbursement_category_name']]);
                    $cellData .= $form->hidden($fieldPrefix.".recipient_id", ['value' => $obj['recipient_id']]);
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_id", ['value' => $obj['scholarship_id']]);
                
                    $value = $obj['estimated_disbursement_date'] ? $obj['estimated_disbursement_date'] : null;
                    $_options = [
                        'format' => 'dd-mm-yyyy',
                        'todayBtn' => 'linked',
                        'orientation' => 'auto',
                        'autoclose' => true,
                    ];
                    $attr['date_options'] = $_options;

                    $attr['fieldName'] = $fieldPrefix.".".'estimated_disbursement_date';
                    
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

                    $amountCellData = $form->input("$fieldPrefix.estimated_amount", ['type' => 'number']);
                    $commentCellData = $form->input("$fieldPrefix.comments", ['type' => 'textarea']);

                    $rowData = [];
                    $rowData[] = $cellData;
                    $rowData[] = $cellInput;
                    $rowData[] = $amountCellData;
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

    public function onUpdateFieldBalanceAmount(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
           
            $approvedAmt = $entity->scholarship_recipient->approved_amount;
            
            $conditions = [
                'recipient_id' => $entity->recipient_id,
                'scholarship_id' => $entity->scholarship_id
            ];

            if (!$entity->isNew()) {
                $conditions[('scholarship_recipient_payment_structure_id <> ')] = $entity->id;
            }

            $query = $this->RecipientPaymentStructureEstimates->find();
            $RecipientPaymentStructureEstimates = $query->where([$conditions])
                ->select([
                    'total_amount_used' => $query->func()->sum('estimated_amount')
                ])
                ->first();

            $balance = $approvedAmt - ($RecipientPaymentStructureEstimates->total_amount_used);
             
            $attr['attr']['value'] =  $balance;
        }

        return $attr;
    }

    public function onUpdateFieldAnnualAwardAmount(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $annualAmount = $entity->scholarship->maximum_award_amount;
            $attr['attr']['value'] =  $annualAmount;

            return $attr;
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if (array_key_exists($this->alias(), $requestData)) {
            if (!array_key_exists('recipient_payment_structure_estimates', $requestData[$this->alias()])) {
                    $requestData[$this->alias()]['recipient_payment_structure_estimates'] = []; 
            } 
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data) 
    {  
        $recipientId = $this->ControllerAction->getQueryString('recipient_id');
        $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');

        $approvedAmt = $this->ScholarshipRecipients->get(['recipient_id' => $recipientId, 'scholarship_id' => $scholarshipId])->approved_amount;        

        $conditions = [
            'recipient_id' => $recipientId,
            'scholarship_id' => $scholarshipId
        ];

        if (!$entity->isNew()) {
            $conditions[('scholarship_recipient_payment_structure_id <> ')] = $entity->id;
        }

        $query = $this->RecipientPaymentStructureEstimates->find();
        $RecipientPaymentStructureEstimates = $query->where([$conditions])
            ->select([
                'total_amount_used' => $query->func()->sum('estimated_amount')
            ])
            ->first();

        $balance = $approvedAmt - ($RecipientPaymentStructureEstimates->total_amount_used);

        $totalAmt = 0;
        if ($entity->has('recipient_payment_structure_estimates')) {
            foreach ($entity->recipient_payment_structure_estimates as $key => $obj) {
                $totalAmt += $obj['estimated_amount'];
            }
        }
            
        if($totalAmt > $balance) {
            $entity->errors('balance_amount', __('Total Amount must not exceed Balance Amount'));
            return false;
        }
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
        $includes['datepicker']['include'] = true;
    }
}
