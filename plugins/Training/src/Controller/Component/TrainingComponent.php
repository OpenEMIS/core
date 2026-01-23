<?php
namespace Training\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Controller\Component;

class TrainingComponent extends Component
{
    private $controller;

    public function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();
    }

    public function getCourseList($params = [])
    {
        $Courses = TableRegistry::getTableLocator()->get('Training.TrainingCourses');

        $query = $Courses->find('list', ['keyField' => 'id', 'valueField' => 'code_name']);

        // excludes
        $excludes = isset($params['excludes']) ? $params['excludes'] : false;
        if ($excludes) {
            $query->where([
                $Courses->aliasField('id NOT IN') => $excludes
            ]);
        }
        // End

        // Filter by Approved
        $steps = $this->controller->Workflow->getStepsByModelCode($Courses->getRegistryAlias(), 'APPROVED');
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

    public function getSessionList($params = [])
    {
        $listAll = isset($params['listAll']) ? $params['listAll'] : false;
        $courseId = isset($params['training_course_id']) ? $params['training_course_id'] : false;

        $Sessions = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
        $query = $Sessions->find('list', ['keyField' => 'id', 'valueField' => 'code_name']);

        if (!$listAll) {
            // Filter by Approved
            $steps = $this->controller->Workflow->getStepsByModelCode($Sessions->getRegistryAlias(), 'APPROVED');
            if (!empty($steps)) {
                $query->where([
                    $Sessions->aliasField('status_id IN') => $steps
                ]);
            } else {
                // Return empty list if approved steps not found
                return [];
            }
            // End
        }

        if ($courseId && $courseId != -1) { //POCOR-6595 one condition add
            $query->where([
                $Sessions->aliasField('training_course_id') => $courseId
            ]);
        }

        return $query->toArray();
    }
}
