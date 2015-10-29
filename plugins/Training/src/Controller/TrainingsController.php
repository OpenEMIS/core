<?php
namespace Training\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class TrainingsController extends AppController
{
    public function initialize() {
        parent::initialize();

        $this->ControllerAction->models = [
            'Courses' => ['className' => 'Training.TrainingCourses'],
            'Sessions' => ['className' => 'Training.TrainingSessions']
        ];
        $this->loadComponent('Paginator');
    }

    public function onInitialize(Event $event, Table $model) {
        $header = __('Training');

        $header .= ' - ' . $model->getHeader($model->alias);
        $this->Navigation->addCrumb('Training', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->alias));

        $this->set('contentHeader', $header);
    }

    public function getCourseList($params=[]) {
        $Courses = TableRegistry::get('Training.TrainingCourses');

        $query = $Courses->find('list', ['keyField' => 'id', 'valueField' => 'code_name']);

        // excludes
        $excludes = array_key_exists('excludes', $params) ? $params['excludes'] : false;
        if ($excludes) {
            $query->where([
                $Courses->aliasField('id NOT IN') => $excludes
            ]);
        }
        // End

        // Filter by Approved
        $steps = $this->Workflow->getStepsByModelCode($Courses->registryAlias(), 'APPROVED');
        if (!empty($steps)) {
            $query->where([
                $Courses->aliasField('status_id IN') => $steps
            ]);
        } else {
            // Return empty list if approved steps not found
            return [];
        }
        // End

        return $query->toArray();
    }
}
