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

class InstitutionExpendituresTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('institution_expenditures');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('BudgetTypes', ['className' => 'FieldOption.BudgetTypes', 'foreignKey' => 'budget_type_id']);
        $this->belongsTo('ExpenditureTypes', ['className' => 'FieldOption.ExpenditureTypes', 'foreignKey' => 'expenditure_type_id']);
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function beforeAction($event) {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('budget_type_id', ['attr' => ['label' => __('Budget')],'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('expenditure_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'date', 'budget_type_id', 'expenditure_type_id', 'amount','file_name', 'file_content', 'description']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('file_content');
    }

	public function beforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$entity->institution_id = $this->request->session()->read('Institution.Institutions.id');
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

    public function indexBeforeAction($event) {
        unset($this->fields['academic_period_id']);
        unset($this->fields['description']);
        $this->setFieldOrder(['date', 'budget_type_id', 'expenditure_type_id', 'amount']);
    }

    public function viewBeforeAction($event) {
        unset($this->fields['attachment']);
        unset($this->fields['description']);
        $this->setFieldOrder(['date', 'budget_type_id', 'expenditure_type_id', 'amount']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'budget_type_id') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } else if ($field == 'expenditure_type_id') {
            return  __('Type');
        } else if ($field == 'amount' && $this->action == 'index') {
            if (!empty($module) && $module == 'InstitutionExpenditures') {
                return __('Amount');
            } else {
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
            }
            //return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    private function setupFields($entity = null)
    {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('budget_type_id', ['attr' => ['label' => __('Budget')],'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('expenditure_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('amount', ['attr' => ['label' => __('Amount')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'date', 'budget_type_id', 'expenditure_type_id', 'amount', 'file_name', 'file_content', 'description']);

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $academyPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

		$query
		->select(['date' => 'InstitutionExpenditures.date','budget' => 'BudgetTypes.name', 'type' => 'ExpenditureTypes.name', 'amount' =>'InstitutionExpenditures.amount'])

        ->LeftJoin([$this->BudgetTypes->alias() => $this->BudgetTypes->table()],[
            $this->BudgetTypes->aliasField('id').' = ' . 'InstitutionExpenditures.budget_type_id'
        ])

		->LeftJoin([$this->ExpenditureTypes->alias() => $this->ExpenditureTypes->table()],[
			$this->ExpenditureTypes->aliasField('id').' = ' . 'InstitutionExpenditures.expenditure_type_id'
        ])

        ->where([
            $this->aliasField('academic_period_id = ') . $academyPeriodId,
            $this->aliasField('institution_id = ') . $institutionId,
        ]);

    }

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key' => 'InstitutionIncomes.date',
            'field' => 'date',
            'type' => 'date',
            'label' => __('Date')
        ];

        $extraField[] = [
            'key' => 'BudgetTypes.name',
            'field' => 'budget',
            'type' => 'string',
            'label' => __('Budget')
        ];

        $extraField[] = [
            'key' => 'ExpenditureTypes.name',
            'field' => 'type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key' => 'InstitutionExpenditures.amount',
            'field' => 'amount',
            'type' => 'integer',
            'label' => __('Amount')
        ];

        $fields->exchangeArray($extraField);
    }
}
