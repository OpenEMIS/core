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

class InstitutionIncomesTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('institution_incomes');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('IncomeSources', ['className' => 'FieldOption.IncomeSources', 'foreignKey' => 'income_source_id']);
        $this->belongsTo('IncomeTypes', ['className' => 'FieldOption.IncomeTypes', 'foreignKey' => 'income_type_id']);
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
        $this->field('income_source_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('income_type_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'income_source_id', 'income_type_id', 'amount', 'file_name','file_content', 'description']);
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
        $this->setFieldOrder(['date', 'income_source_id', 'income_type_id', 'amount']);
    }

    public function viewBeforeAction($event) {
        unset($this->fields['attachment']);
        unset($this->fields['description']);
        $this->setFieldOrder(['date', 'income_source_id', 'income_type_id', 'amount']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'income_source_id') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } else if ($field == 'income_type_id') {
            return  __('Type');
        } else if ($field == 'amount' && $this->action == 'index') {
            if (!empty($module) && $module == 'InstitutionIncomes') {
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
        $this->field('income_source_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('income_type_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('amount', ['attr' => ['label' => __('Amount')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'income_source_id', 'income_type_id', 'amount','file_name', 'file_content', 'description']);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $academyPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();


		$query
		->select(['date' => 'InstitutionIncomes.date','amount' => 'InstitutionIncomes.amount', 'source' =>'IncomeSources.name', 'type' => 'IncomeTypes.name'])

        ->LeftJoin([$this->IncomeTypes->alias() => $this->IncomeTypes->table()],[
            $this->IncomeTypes->aliasField('id').' = ' . 'InstitutionIncomes.income_type_id'
        ])

		->LeftJoin([$this->IncomeSources->alias() => $this->IncomeSources->table()],[
			$this->IncomeSources->aliasField('id').' = ' . 'InstitutionIncomes.income_source_id'
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
            'key' => 'IncomeSources.name',
            'field' => 'source',
            'type' => 'string',
            'label' => __('Source')
        ];

        $extraField[] = [
            'key' => 'IncomeTypes.name',
            'field' => 'type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key' => 'InstitutionIncomes.amount',
            'field' => 'amount',
            'type' => 'integer',
            'label' => __('Amount')
        ];

        $fields->exchangeArray($extraField);
    }
}
