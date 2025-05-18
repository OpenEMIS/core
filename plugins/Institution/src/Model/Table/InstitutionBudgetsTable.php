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

class InstitutionBudgetsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config): void
    {
        $this->setTable('institution_budgets');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('BudgetTypes', ['className' => 'FieldOption.BudgetTypes', 'foreignKey' => 'budget_type_id']);
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Excel', ['pages' => ['index']]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Budget'=>['id']]
        ]);
    }

    public function beforeAction($event) {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('budget_type_id', ['attr' => ['label' => __('Type')],'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'budget_type_id', 'amount','file_name', 'file_content', 'description']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('file_content');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data) {
        //$entity->institution_id = $this->request->getSession()->read('Institution.Institutions.id');
        $entity->institution_id = $this->getInstitutionID();
    }

    public function indexBeforeAction($event) {

        unset($this->fields['academic_period_id']);
        unset($this->fields['description']);

        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'budget_type_id') {
            return __('Type');
        } else if ($field == 'academic_period_id') {
            return __('Academic Period');
        } else if ($field == 'amount') {
            return __('Amount (PM)');
        } else if ($field == 'file_name') {
            return __('Attachment');
        } else if ($field == 'description') {
            return __('Description');
        } else if ($field == 'file_content') {
            return __('Attachment');
        } else if ($field == 'modified_user_id') {
            return __('Modified By');
        } else if ($field == 'modified') {
            return __('Modified On');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else if ($field == 'created') {
            return __('Created On');
        } else if ($field == 'amount' && $this->action == 'index') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    private function setupFields($entity = null)
    {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('budget_type_id', ['attr' => ['label' => __('Type')],'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'budget_type_id', 'amount','file_name', 'file_content', 'description']);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        //$institutionId = $session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();
        $academyPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $query
		->select(['amount' => 'InstitutionBudgets.amount','type' => 'BudgetTypes.name'])

        ->LeftJoin([$this->AcademicPeriods->getAlias() => $this->AcademicPeriods->getTable()],[
            $this->AcademicPeriods->aliasField('id').' = ' . 'InstitutionBudgets.academic_period_id'
        ])

		->LeftJoin([$this->BudgetTypes->getAlias() => $this->BudgetTypes->getTable()],[
			$this->BudgetTypes->aliasField('id').' = ' . 'InstitutionBudgets.budget_type_id'
        ])
        ->where([
            $this->aliasField('academic_period_id = ') . $academyPeriodId,
            $this->aliasField('institution_id = ') . $institutionId,
        ]);

    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraField[] = [
            'key' => 'BudgetTypes.name',
            'field' => 'type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key' => 'InstitutionBudgets.amount',
            'field' => 'amount',
            'type' => 'integer',
            'label' => __('Amount')
        ];

        $fields->exchangeArray($extraField);
    }
}
