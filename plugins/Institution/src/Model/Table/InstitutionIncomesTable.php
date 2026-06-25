<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
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

    public function initialize(array $config): void
    {
        $this->setTable('institution_incomes');
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Income'=>['id']]
        ]);
    }

    public function beforeAction($event) {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('income_source_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('income_type_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'income_source_id', 'income_type_id', 'amount', 'file_name','file_content', 'description']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('file_content');
    }

	public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data) {
		//$entity->institution_id = $this->request->getSession()->read('Institution.Institutions.id');
        $entity->institution_id = $this->getInstitutionID();
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        /*if ($field == 'income_source_id') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }*/ 
        if ($field == 'income_source_id') {
            return  __('Source');
        } else if ($field == 'academic_period_id') {
            return  __('Academic Period');
        } else if ($field == 'date') {
            return  __('Date');
        } else if ($field == 'income_type_id') {
            return  __('Type');
        } else if ($field == 'file_content') {
            return  __('Attachment');
        } else if ($field == 'description') {
            return  __('Description');
        } else if ($field == 'amount' && $this->action == 'index') {
            if (!empty($module) && $module == 'InstitutionIncomes') {
                return __('Amount');
            } else {
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
            }
            //return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        } else if ($field == 'modified_user_id') {
            return __('Modified By');
        } else if ($field == 'modified') {
            return __('Modified On');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else if ($field == 'created') {
            return __('Created On');
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

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        //$institutionId = $session->read('Institution.Institutions.id');
        $institutionId = $this->getInstitutionID();
        $academyPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();


		$query
		->select(['date' => 'InstitutionIncomes.date','amount' => 'InstitutionIncomes.amount', 'source' =>'IncomeSources.name', 'type' => 'IncomeTypes.name'])

        ->LeftJoin([$this->IncomeTypes->getAlias() => $this->IncomeTypes->getTable()],[
            $this->IncomeTypes->aliasField('id').' = ' . 'InstitutionIncomes.income_type_id'
        ])

		->LeftJoin([$this->IncomeSources->getAlias() => $this->IncomeSources->getTable()],[
			$this->IncomeSources->aliasField('id').' = ' . 'InstitutionIncomes.income_source_id'
        ]);
    }

	public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
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
