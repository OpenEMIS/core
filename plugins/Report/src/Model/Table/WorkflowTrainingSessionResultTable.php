<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;

class WorkflowTrainingSessionResultTable extends AppTable  
{
    public function initialize(array $config): void 
    {
        $this->setTable("training_session_results");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('TrainingSession', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);


        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function implementedEvents(): array {
        $events = parent::implementedEvents();
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery';
        return $events;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query) {
        $query
            ->contain([
                'TrainingSession' => [
                    'fields' => [
                        'TrainingSession.name'
                    ]
                ]   
            ]);
    }
}
