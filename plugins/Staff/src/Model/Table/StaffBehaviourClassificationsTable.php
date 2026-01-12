<?php
namespace Staff\Model\Table;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
class StaffBehaviourClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('behaviour_classifications');
        parent::initialize($config);
        $this->hasMany('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('FieldOption.FieldOption');
    }
    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
        $search = sprintf('%%"%s":"%s"%%', 'behaviour_classification_id', $entity->id);
        $WorkflowRuleResults = $WorkflowRules
            ->find()
            ->where([
                $WorkflowRules->aliasField('rule LIKE ') => $search
            ])
            ->all();
        $workflowRuleCount = $WorkflowRuleResults->count();
        $extra['associatedRecords'][] = ['model' => $WorkflowRules->getAlias(), 'count' => $workflowRuleCount];
    }
    public function getThresholdOptions()
    {
        return $this
            ->find('list')
            ->find('visible')
            ->order([$this->aliasField('order')])
            ->toArray();
    }
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}