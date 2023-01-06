<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class BehaviourClassificationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('behaviour_classifications');
        parent::initialize($config);

        $this->hasMany('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');

        $search = sprintf('%%"%s":"%s"%%', 'behaviour_classification_id', $entity->id);
        $WorkflowRuleResults = $WorkflowRules
            ->find()
            ->where([
                $WorkflowRules->aliasField('rule LIKE ') => $search
            ])
            ->all();
        $workflowRuleCount = $WorkflowRuleResults->count();

        $extra['associatedRecords'][] = ['model' => $WorkflowRules->alias(), 'count' => $workflowRuleCount];
    }

    public function getThresholdOptions()
    {
        return $this
            ->find('list')
            ->find('visible')
            ->order([$this->aliasField('order')])
            ->toArray()
        ;
    }
}
