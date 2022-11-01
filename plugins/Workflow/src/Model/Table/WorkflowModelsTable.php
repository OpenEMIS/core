<?php
namespace Workflow\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

use App\Model\Table\AppTable;

class WorkflowModelsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('Workflows', ['className' => 'Workflow.Workflows', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('WorkflowStatuses', ['className' => 'Workflow.WorkflowStatuses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('WorkflowTransitions', ['className' => 'Workflow.WorkflowTransitions', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function getWorkflowStatusSteps($modelName, $code)
    {
        return $this
            ->find('list', [
                'keyField' => 'step_id',
                'valueField' => 'step_id'
            ])
            ->matching('WorkflowStatuses.WorkflowSteps')
            ->where([
                $this->aliasField('model') => $modelName,
                'WorkflowStatuses.code' => $code
            ])
            ->select(['step_id' => 'WorkflowSteps.id'])
            ->toArray();
    }

    public function getWorkflowStatuses($model)
    {
        return $this
            ->find('list', [
                'keyField' => 'workflow_status_id',
                'valueField' => 'workflow_status_name'
            ])
            ->matching('WorkflowStatuses')
            ->where([$this->aliasField('model') => $model])
            ->select(['workflow_status_id' => 'WorkflowStatuses.id', 'workflow_status_name' => 'WorkflowStatuses.name'])
            ->toArray();
    }

    public function getWorkflowStatusesCode($model)
    {
        return $this
            ->find('list', [
                'keyField' => 'workflow_status_id',
                'valueField' => 'workflow_status_code'
            ])
            ->matching('WorkflowStatuses')
            ->where([$this->aliasField('model') => $model])
            ->select(['workflow_status_id' => 'WorkflowStatuses.id', 'workflow_status_code' => 'WorkflowStatuses.code'])
            ->toArray();
    }

    public function getFeatureOptions()
    {
        $records = $this->find()->distinct(['model'])->all();

        $featureOptions = [];
        foreach ($records as $obj) {
            $model = TableRegistry::get($obj->model);
            $feature = Inflector::humanize(Inflector::underscore($model->alias()));
            $featureOptions[$model->alias()] = __($feature);
        }

        return $featureOptions;
    }
}
