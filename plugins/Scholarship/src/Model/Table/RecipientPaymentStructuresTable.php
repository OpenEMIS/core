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

class RecipientPaymentStructuresTable extends ControllerActionTable
{
    use IdGeneratorTrait;

    private $currency = [];

    public function initialize(array $config)
    {
        $this->table('scholarship_recipient_payment_structures');
        parent::initialize($config);

        $this->belongsTo('ScholarshipRecipients', ['className' => 'Scholarship.ScholarshipRecipients', 'foreignKey' => ['recipient_id', 'scholarship_id']]);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Recipients', ['className' => 'User.Users', 'foreignKey' => 'recipient_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->hasMany('RecipientPaymentStructureEstimates', ['className' => 'Scholarship.RecipientPaymentStructureEstimates', 'foreignKey' => 'scholarship_recipient_payment_structure_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->currency = TableRegistry::get('Configuration.ConfigItems')->value('currency');
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
        $this->controller->set('contentHeader', $recipientName . ' - ' . __('Payment Structures'));
        // set tabs
        $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'PaymentStructures');
        
        $this->field('recipient_id', ['type' => 'hidden']);
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
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
        $query->contain(['RecipientPaymentStructureEstimates']);
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

         $this->field('approved_amount', [
            'type' => 'disabled',
            'fieldName' => 'scholarship_recipient.approved_amount',
            'after' => 'scholarship_name',
            'attr' => ['label' => $this->addCurrencySuffix('Approved Amount')]
        ]);

        $this->field('balance_amount', [
            'type' => 'disabled',
            'after' => 'approved_amount',
            'attr' => ['label' => $this->addCurrencySuffix('Balance Amount')],
            'entity' => $entity
        ]);
       
        $this->field('disbursement_category_id', [
            'type' => 'custom_disbursement_category',
            'after' => 'balance_amount'
        ]);
    }

    public function addEditOnSelectDisbursementCategory(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $fieldKey = 'recipient_payment_structure_estimates';

        if (!isset($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if (isset($data[$this->alias()]['disbursement_category_id']) && !empty($data[$this->alias()]['disbursement_category_id'])) {
            $selectedDisbursementCategory = $data[$this->alias()]['disbursement_category_id'];
            $disbursementCategoryEntity = TableRegistry::get('Scholarship.DisbursementCategories')->get($selectedDisbursementCategory);
            $data[$this->alias()][$fieldKey][] = [
                'scholarship_disbursement_category_id' => $disbursementCategoryEntity->id,
                'scholarship_disbursement_category_name' => $disbursementCategoryEntity->name,
                'estimated_disbursement_date' => '',
                'estimated_amount' => null,
                'comments' => '',
                'recipient_id' => '',
                'scholarship_id' => ''
            ];
        }
        $data[$this->alias()]['disbursement_category_id'] = '';

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'RecipientPaymentStructureEstimates' => ['validate' => false]
        ];
    }

    public function onGetCustomDisbursementCategoryElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            // No implementation yet
        } elseif ($action == 'view') {
            $tableHeaders = [__('Category') , __('Estimated Disbursement Date'), __('Estimated Amount'), __('Comments')];
            $tableCells = [];

            if ($entity->has('recipient_payment_structure_estimates')) {
                foreach ($entity->recipient_payment_structure_estimates as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj->scholarship_recipient_payment_structure_id;
                    $rowData[] = $obj->estimated_disbursement_date;
                    $rowData[] = $obj->estimated_amount;
                    $rowData[] = $obj->comments;

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        } elseif ($action == 'add' || $action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($attr['model'] . '.recipient_payment_structure_estimates');

            $cellCount = 0;
            $tableHeaders = [__('Category') , __('Estimated Disbursement Date'), __('Estimated Amount'), __('Comments'), ''];
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

            $recipientId = $this->ControllerAction->getQueryString('recipient_id');
            $scholarshipId = $this->ControllerAction->getQueryString('scholarship_id');
            
            // options
            $DisbursementCategory = TableRegistry::get('Scholarship.DisbursementCategories');
            $disbursementCategoryOptions = $DisbursementCategory->getList()->toArray();

            if (!empty($arrayPaymentStructureEstimates)) {
                foreach ($arrayPaymentStructureEstimates as $key => $obj) {
                    $fieldPrefix = $attr['model'] . '.recipient_payment_structure_estimates.' . $cellCount++;
                    
                    $cellData = $obj['scholarship_disbursement_category_name'];
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_disbursement_category_id", ['value' => $obj['scholarship_disbursement_category_id']]);
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_disbursement_category_name", ['value' => $obj['scholarship_disbursement_category_name']]);
                    $cellData .= $form->hidden($fieldPrefix.".recipient_id", ['value' => $recipientId]);
                    $cellData .= $form->hidden($fieldPrefix.".scholarship_id", ['value' => $scholarshipId]);
                
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

            $query = $this->RecipientPaymentStructureEstimates->find();
            $query->select([
                    'total_amount_used' => $query->func()->sum('estimated_amount')
                ]);

            if (!$entity->isNew()) {
                $query->where([$this->RecipientPaymentStructureEstimates->aliasField('id <> ') => $entity->id]);
            }

            $RecipientPaymentStructureEstimates = $query->first();

            $amountUsed = $RecipientPaymentStructureEstimates->total_amount_used;
            
            $balance = $approvedAmt - $amountUsed;
            $attr['attr']['value'] =  $balance;
        }

        return $attr;
    }


    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
        $includes['datepicker']['include'] = true;
    }

    public function addCurrencySuffix($label)
    {
        return __($label) . ' (' . $this->currency . ')';
    }

    // public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) 
    // {
    //     $totalAmt = 0;
    //     if ($entity->has('recipient_payment_structure_estimates')) {
    //         foreach ($entity->recipient_payment_structure_estimates as $key => $obj) {
    //             $totalAmt += $obj['estimated_amount'];
    //         }
    //     }

    //     $query = $this->RecipientPaymentStructureEstimates->find();
    //     $RecipientPaymentStructureEstimates = $query
    //         ->select([
    //             'total_amount_used' => $query->func()->sum('estimated_amount')
    //         ])
    //         ->first();

    //     $totalAmt += $RecipientPaymentStructureEstimates->total_amount_used;

    // }

}
