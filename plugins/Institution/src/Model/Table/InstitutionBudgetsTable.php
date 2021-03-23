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

    public function initialize(array $config)
    {
        $this->table('institution_budgets');
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

    }

    public function beforeAction($event) {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('budget_type_id', ['attr' => ['label' => __('Type')],'type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'budget_type_id', 'amount','file_name', 'file_content', 'description']);
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

}
