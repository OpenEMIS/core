<?php
namespace Training\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Controller\Component;

class TrainingComponent extends Component {
	private $controller;

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
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
        $steps = $this->controller->Workflow->getStepsByModelCode($Courses->registryAlias(), 'APPROVED');
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

    public function getSessionList($params=[]) {
        $Sessions = TableRegistry::get('Training.TrainingSessions');
        $query = $Sessions->find('list');

        // Filter by Approved
        $steps = $this->controller->Workflow->getStepsByModelCode($Sessions->registryAlias(), 'APPROVED');
        if (!empty($steps)) {
            $query->where([
                $Sessions->aliasField('status_id IN') => $steps
            ]);
        } else {
            // Return empty list if approved steps not found
            return [];
        }
        // End

        return $query->toArray();
    }
}
