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

App::uses('AppModel', 'Model');

class DatawarehouseIndicator extends DataProcessingAppModel {
	public $actsAs = array('ControllerAction');

	public $paginateLimit = 25;

	public $belongsTo = array(
		'DatawarehouseUnit',
		'DatawarehouseField',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
		'DatawarehouseIndicatorCondition' => array(
			'className' => 'DatawarehouseIndicatorCondition',
			'foreignKey' => 'datawarehouse_indicator_id',
			'dependent' => true
		)
	);

	public $headerDefault = 'Indicators';

	public function indicator($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);
	
		if ($controller->request->is('post')) {
			if (isset($controller->request->data['sortdir']) && isset($controller->request->data['order'])) {
				if ($controller->request->data['sortdir'] != $controller->Session->read('Datawarehouse.Search.sortdir')) {
					$controller->Session->delete('Datawarehouse.Search.sortdir');
					$controller->Session->write('Datawarehouse.Search.sortdir', $controller->request->data['sortdir']);
				}
				if ($controller->request->data['order'] != $controller->Session->read('Datawarehouse.Search.order')) {
					$controller->Session->delete('Datawarehouse.Search.order');
					$controller->Session->write('Datawarehouse.Search.order', $controller->request->data['order']);
				}
			}
		}
		

		$fieldordername = ($controller->Session->read('Datawarehouse.Search.order')) ? $controller->Session->read('Datawarehouse.Search.order') : array('DatawarehouseIndicator.name');
		$fieldorderdir = ($controller->Session->read('Datawarehouse.Search.sortdir')) ? $controller->Session->read('Datawarehouse.Search.sortdir') : 'asc';
		$order = $fieldordername;
		if($controller->Session->check('Datawarehouse.Search.order')){
			$order = array($fieldordername => $fieldorderdir);
		}

		$controller->Paginator->settings = array(
	        'fields' => array('DatawarehouseIndicator.*', 'DatawarehouseUnit.name', 'DatawarehouseModule.name'),
	        'joins' => array(
		        array(
					'type' => 'INNER',
					'table' => 'datawarehouse_units',
					'alias' => 'DatawarehouseUnit',
					'conditions' => array('DatawarehouseUnit.id = DatawarehouseIndicator.datawarehouse_unit_id')
				),
				array(
					'type' => 'INNER',
					'table' => 'datawarehouse_fields',
					'alias' => 'DatawarehouseField',
					'conditions' => array('DatawarehouseField.id = DatawarehouseIndicator.datawarehouse_field_id')
				),
				array(
					'type' => 'INNER',
					'table' => 'datawarehouse_modules',
					'alias' => 'DatawarehouseModule',
					'conditions' => array('DatawarehouseModule.id = DatawarehouseField.datawarehouse_module_id')
				)
		    ),
	        'limit' => $this->paginateLimit,
	        'recursive'=> -1,
	        'order' => $order
	    );
		
		$data = $controller->paginate('DatawarehouseIndicator');

		if (empty($data) && !$controller->request->is('ajax')) {
			$controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}

		$controller->set('sortedcol', $fieldordername);
		$controller->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');
		
		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		if ($controller->request->is('post')) {
			$controller->set('ajax', true);
		}
	} 

	public function indicatorAdd($controller, $params) {
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
	}

	public function indicatorEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}

	private function setup_add_edit_form($controller, $params){
		$datawarehouseUnitOptions = $this->DatawarehouseUnit->find('list', array('fields'=> array('id', 'name')));

		$DatawarehouseModule = ClassRegistry::init('DatawarehouseModule');
		$datawarehouseModuleOptions = $DatawarehouseModule->find('list', array('fields'=> array('id', 'name')));

		$datawarehouseOperatorFieldOptions = array();
		$datawarehouseFieldOptions = array();
		$controller->set(compact('datawarehouseUnitOptions', 'datawarehouseModuleOptions', 'datawarehouseOperatorFieldOptions', 'datawarehouseFieldOptions'));
		$controller->set('modelName', $this->name);
		/*
		$provider = '';

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			$sessionEditable = '1';
			if(!empty($data)){
				$sessionEditable = $this->getSessionResultStatus($id);
				if(!$sessionEditable){
					return $controller->redirect(array('action' => 'sessionView', $id));
				}

				$provider = $data['TrainingSession']['training_provider_id'];
				$trainingSessionTrainees = $this->TrainingSessionTrainee->find('all',  
					array(
						'fields' => array('TrainingSessionTrainee.*', 'Staff.first_name', 'Staff.last_name'),
						'recursive' => -1, 
						'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$id),
						'joins' => array(
							array(
								'type' => 'INNER',
								'table' => 'staff',
								'alias' => 'Staff',
								'conditions' => array('Staff.id = TrainingSessionTrainee.staff_id')
							)
						)
					)
				);
				$trainingSessionTraineesVal = null;
				if(!empty($trainingSessionTrainees)){
					foreach($trainingSessionTrainees as $val){
						$val['TrainingSessionTrainee']['first_name'] = $val['Staff']['first_name'];
						$val['TrainingSessionTrainee']['last_name'] = $val['Staff']['last_name'];
						$trainingSessionTraineesVal[] = $val['TrainingSessionTrainee'];
					}
				}

				$trainingSessionTrainers = $this->TrainingSessionTrainer->find('all',  
					array(
						'fields' => array('TrainingSessionTrainer.*'),
						'recursive' => -1, 
						'conditions'=>array('TrainingSessionTrainer.training_session_id'=>$id),
					)
				);
				$trainingSessionTrainersVal = null;
				if(!empty($trainingSessionTrainers)){
					foreach($trainingSessionTrainers as $val){
						$trainingSessionTrainersVal[] = $val['TrainingSessionTrainer'];
					}
				}
				$controller->request->data = array_merge($data, array('TrainingSessionTrainee'=>$trainingSessionTraineesVal, 'TrainingSessionTrainer'=>$trainingSessionTrainersVal));
			}
			$controller->request->data['TrainingSession']['sessionEditable'] = $sessionEditable;
		}
		else{
			if ($this->saveAll($controller->request->data, array('validate' => 'only'))){

				if(!isset($controller->request->data['TrainingSession']['sessionEditable']) || $controller->request->data['TrainingSession']['sessionEditable'] == '1'){
					if ($controller->request->data['TrainingSession']['training_status_id']=='1') {
				   	$controller->request->data['TrainingSession']['training_status_id'] = 1; 
					} else if ($controller->request->data['TrainingSession']['training_status_id']=='2') {
				      	$controller->request->data['TrainingSession']['training_status_id'] = 2; 
					}
				}

				$data = $controller->request->data;
				if($data['TrainingSession']['sessionEditable']=='2'){
					$this->TrainingSessionTrainee->bindModel(
				        array('hasMany' => array(
			                 	'TrainingSessionTraineeResult' => array(
									'className' => 'TrainingSessionTraineeResult',
									'foreignKey' => 'training_session_trainee_id',
									'dependent' => true,
									'exclusive' => true
								),
				            )
				        ),
				        false
				    );

					
					$trainingCourseResultType = ClassRegistry::init('TrainingCourseResultType');
					$trainingCourseResultType->bindModel(
				        array('belongsTo' => array(
				                'TrainingResultType' => array(
									'className' => 'FieldOptionValue',
									'foreignKey' => 'training_result_type_id'
								)
				            )
				        )
				    );

				    $trainingCourseResultTypes = $trainingCourseResultType->find('all', array('recursive'=>-1,'conditions'=>array('TrainingCourseResultType.training_course_id'=>$data['TrainingSession']['training_course_id'])));
						
					if(!empty($data['TrainingSessionTrainee'])){
						$trainingSessionTraineeResults = array();
						foreach($data['TrainingSessionTrainee'] as $key=>$value){
							$data['TrainingSessionTrainee'][$key]['training_session_id'] = $data['TrainingSession']['id'];
							if(!isset($data['TrainingSessionTrainee'][$key]['id'])){
								$this->TrainingSessionTrainee->create();
								$this->TrainingSessionTrainee->save($data['TrainingSessionTrainee'][$key]);
								$insertId = $this->TrainingSessionTrainee->getLastInsertId();
								unset($data['TrainingSessionTrainee'][$key]);
								foreach($trainingCourseResultTypes as $key=>$val){
									$trainingSessionTraineeResults[] = array('training_session_trainee_id'=>$insertId, 'training_result_type_id'=>$val['TrainingCourseResultType']['training_result_type_id']);
								}
							}
						}
						$this->TrainingSessionTrainee->saveAll($data['TrainingSessionTrainee']);
						$this->TrainingSessionTrainee->TrainingSessionTraineeResult->saveAll($trainingSessionTraineeResults);
					}

					if(isset($data['DeleteTrainee'])){
						$deletedId = array();
						foreach($data['DeleteTrainee'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						
					 	$this->TrainingSessionTrainee->deleteAll(array('TrainingSessionTrainee.id'=>$deletedId));
					}
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => 'session'));
					
				}else{
					if($this->saveAll($data)){
						if(isset($data['DeleteTrainee'])){
							$deletedId = array();
							foreach($data['DeleteTrainee'] as $key=>$value){
								$deletedId[] = $value['id'];
							}
							$this->TrainingSessionTrainee->deleteAll(array('TrainingSessionTrainee.id' => $deletedId), false);
						}
						if(isset($data['DeleteTrainer'])){
							$deletedId = array();
							foreach($data['DeleteTrainer'] as $key=>$value){
								$deletedId[] = $value['id'];
							}
							$this->TrainingSessionTrainer->deleteAll(array('TrainingSessionTrainer.id' => $deletedId), false);
						}
						if(empty($controller->request->data[$this->name]['id'])){
						  	$controller->Message->alert('general.add.success');
						}
						else{	
						  	$controller->Message->alert('general.edit.success');
						}
						return $controller->redirect(array('action' => 'session'));
					}
				}
			}
		}
		$controller->set('provider', $provider);*/
	}

}
