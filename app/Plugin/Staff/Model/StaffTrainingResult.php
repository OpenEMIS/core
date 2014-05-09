<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

class StaffTrainingResult extends AppModel {
	public $actsAs = array('ControllerAction');

	public $useTable = false; // This model uses a database table 'exmp'

	public $headerDefault = 'Training Results';
	
	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
	
	public function trainingResult($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$controller->set('modelName', 'TrainingSessionTrainee');
		$data = $trainingSessionTrainee->find('all',
			array(
				'fields' => array('TrainingSessionTrainee.*', 'TrainingCourse.*', 'TrainingStatus.*'),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'training_session_results',
						'alias' => 'TrainingSessionResult',
						'conditions' => array(
							'TrainingSessionTrainee.training_session_id = TrainingSessionResult.training_session_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_sessions',
						'alias' => 'TrainingSession',
						'conditions' => array(
							'TrainingSession.id = TrainingSessionTrainee.training_session_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_courses',
						'alias' => 'TrainingCourse',
						'conditions' => array(
							'TrainingCourse.id = TrainingSession.training_course_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingStatus',
						'conditions' => array(
							'TrainingStatus.id = TrainingSessionResult.training_status_id'
						)
					)
				),
				'conditions'=> array(
					'identification_id'=> $controller->staffId,
					'identification_table'=> 'staff'
				)
			)
		);

		$controller->set('header', __($this->headerDefault));
		$controller->set('data', $data);
		
	}

	
	public function trainingResultView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', 'TrainingSessionTrainee');
		$id = empty($params['pass'][0])? 0:$params['pass'][0];

		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$data = $trainingSessionTrainee->find('first',
			array(
				'fields' => array('TrainingSessionTrainee.*', 'TrainingCourse.*', 'TrainingResultStatus.*', 'TrainingSession.*', 'TrainingSessionStatus.*', 'TrainingProvider.*', 
					'TrainingSessionResult.*', 'CreatedUser.*', 'ModifiedUser.*'
					),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'training_session_results',
						'alias' => 'TrainingSessionResult',
						'conditions' => array(
							'TrainingSessionTrainee.training_session_id = TrainingSessionResult.training_session_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_sessions',
						'alias' => 'TrainingSession',
						'conditions' => array(
							'TrainingSession.id = TrainingSessionTrainee.training_session_id'
						)
					),
					array(
						'type' => 'LEFT',
						'table' => 'training_providers',
						'alias' => 'TrainingProvider',
						'conditions' => array(
							'TrainingProvider.id = TrainingSession.training_provider_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_courses',
						'alias' => 'TrainingCourse',
						'conditions' => array(
							'TrainingCourse.id = TrainingSession.training_course_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingResultStatus',
						'conditions' => array(
							'TrainingResultStatus.id = TrainingSessionResult.training_status_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingSessionStatus',
						'conditions' => array(
							'TrainingSessionStatus.id = TrainingSession.training_status_id'
						)
					),
					array(
						'type' => 'LEFT',
						'table' => 'security_users',
						'alias' => 'CreatedUser',
						'conditions' => array(
							'CreatedUser.id = TrainingSessionResult.created_user_id'
						)
					),
					array(
						'type' => 'LEFT',
						'table' => 'security_users',
						'alias' => 'ModifiedUser',
						'conditions' => array(
							'ModifiedUser.id = TrainingSessionResult.modified_user_id'
						)
					)
				),
				'conditions'=> array(
					'TrainingSessionTrainee.id' => $id
				)
			)
		);
		if(empty($data)){
			$controller->redirect(array('action'=>'trainingResult'));
		}
		
		$controller->Session->write('TeacherTrainingResultId', $id);
		$controller->set('data', $data);
	}
	


}
?>