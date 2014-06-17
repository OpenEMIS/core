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

class WorkflowComponent extends Component {
    private $controller;
    public $components = array('Session','Auth');

    private $modelMap = array(
        'GroupUser' => 'SecurityGroupUser',
        'Workflow' => 'Workflow',
        'WorkflowStep' => 'WorkflowStep',
        'WorkflowLog' => 'WorkflowLog'
    );

    //called before Controller::beforeFilter()
    public function initialize(Controller $controller) {
        $this->controller =& $controller;
        foreach($this->modelMap as $model => $modelClass) {
            $this->{$model} = ClassRegistry::init($modelClass);
        }
        $this->setWorkflow($this->Auth->user('id'));

        //$this->Workflow->recover();
    }

    //called after Controller::beforeFilter()
    public function startup(Controller $controller) {}
    
    //called after Controller::beforeRender()
    public function beforeRender(Controller $controller) {}
    
    //called after Controller::render()
    public function shutdown(Controller $controller) {}
    
    //called before Controller::redirect()
    public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}
    
    public function init($userId) {
        $this->setUserPermissions($userId);
    }
    
    public function setWorkflow($userId) {
        if($userId > 0) {
            $this->userId = $userId;
            $workflows = $this->getWorkflow($userId);
            $this->Session->write('workflow', $workflows);
        } else {
            $this->Session->delete('workflow');
            $this->Session->delete('workflowGroupUsers');
        }
    }

    public function getWorkflow($userId){
        $groupUsers = $this->GroupUser->find('all', 
            array(
                'fields'=>array('Distinct(security_role_id) as RoleID'),
                'conditions'=>array('security_user_id'=>$userId)
            )
        );

        $this->Session->write('workflowGroupUsers', $groupUsers);

        $securityRoleId = array();
        foreach($groupUsers as $groupUser){
           $securityRoleId[] = $groupUser['SecurityGroupUser']['RoleID'];
        }

        $workflows = $this->Workflow->find('all',
            array(
                'fields'=>array('Workflow.model_name', 'Workflow.parent_id', 'Workflow.id', 'Workflow.workflow_name', 'WorkflowStep.id', 'WorkflowStep.step', 'WorkflowStep.security_role_id'),
                'joins' => array(
                    array(
                        'type' => 'LEFT',
                        'table' => 'workflow_steps',
                        'alias' => 'WorkflowStep',
                        'conditions' => array('Workflow.id = WorkflowStep.workflow_id')
                    )
                ),
                'conditions'=>array('security_role_id'=>$securityRoleId),
                'order'=>array('Workflow.id, WorkflowStep.step')
            )
        );

        return $workflows;
    }

    function getWorkflowIdByModel($model){
        $this->Workflow = ClassRegistry::init('Workflow');
        $workflowModels = $this->Workflow->find('list', array('fields'=>array('id'), 'conditions'=>array('Workflow.model_name'=>$model)));
       
        if(!empty($workflowModels)){

            $modelName[] = $model;
            foreach($workflowModels as $workflowModel){

                if($this->Workflow->childCount($workflowModel)>0){
                    $wfChild = $this->Workflow->children($workflowModel);
                    foreach($wfChild as $wf){
                        $modelName[] = $wf['Workflow']['model_name'];
                    }
                }
            }
            return array_unique($modelName);
        }

        return false;
    }

    function getCurrentModelName($workflowModels, $modelName){
         $model = $workflowModels[key(array_diff($workflowModels, array($modelName)))];
         return $model;
    }

    function validateWorkflow($model, $id){
        if($model == 'StaffTrainingSelfStudyResult'){
            $modelTemp = ClassRegistry::init($model);
            $chkModel = $modelTemp->find('all', array(
                'conditions'=>array(
                    'StaffTrainingSelfStudyResult.staff_training_self_study_id'=>$id, 
                    'StaffTrainingSelfStudyResult.training_status_id'=>2
                )
            ));
            if(empty($chkModel)){
               return false;
            }
        }
        return true;
    }

    public function getApprovalWorkflow($model, $pending, $id){
        $workflowModels = $this->getWorkflowIdByModel($model);
        $this->controller->set('_viewApprovalLog', false);
        $this->controller->set('_approval', false);
        if(!empty($workflowModels)){
            $workflowLog = $this->WorkflowLog->find('first',
                array(
                    'fields'=>array('WorkflowLog.model_name','WorkflowLog.workflow_step_id', 'WorkflowLog.approve', 'WorkflowStep.step'),
                    'joins' => array(
                            array(
                                'type' => 'LEFT',
                                'table' => 'workflow_steps',
                                'alias' => 'WorkflowStep',
                                'conditions' => array('WorkflowLog.workflow_step_id = WorkflowStep.id')
                            )
                    ),
                    'order'=>array('WorkflowLog.record_id, WorkflowLog.created DESC', 'WorkflowLog.workflow_step_id DESC'),
                    'conditions'=>array('WorkflowLog.model_name'=>$workflowModels, 'WorkflowLog.record_id'=>$id)
                )
            );
            $new = false;
            $step = 0;
            if(!empty($workflowLog)){
                $step = $workflowLog['WorkflowStep']['step'];
                if($workflowLog['WorkflowLog']['approve']=='0'){
                    $step = 0;
                    $new = true;
                }else{
                    if($this->getEndOfWorkflow($model, $step, $workflowLog['WorkflowLog']['approve'])){
                        if($pending){
                            if($workflowLog['WorkflowLog']['model_name']!=$workflowModels[key(array_slice($workflowModels, -1, 1, TRUE))]){
                                if($pending){
                                    $modelNext = $this->getCurrentModelName($workflowModels,$workflowLog['WorkflowLog']['model_name']);
                                    $step = 0;
                                    $model = $modelNext;
                                }
                            }
                            if($this->getEndOfWorkflow($model, $step, $workflowLog['WorkflowLog']['approve'])){
                                 $model = $workflowLog['WorkflowLog']['model_name'];
                            }

                        }
                         //if($modelTemp->find('first', array('conditions'=>'')))
                        /*if($workflowLog['WorkflowLog']['model_name']!=$workflowModels[key(array_slice($workflowModels, -1, 1, TRUE))]){
                            $pending = false;

                            if($this->getEndOfWorkflow($workflowLog['WorkflowLog']['model_name'], $step, $workflowLog['WorkflowLog']['approve'])){
                                $step = 0;
                            }
                            $model = $this->getCurrentModelName($workflowModels,$workflowLog['WorkflowLog']['model_name']);
                        }*/
                    }
                }
            }
            if(!$new && !$pending){
                $workflow = $this->getCurrentWorkflowStep($model, $step+1);
                if(!empty($workflow)){
                    $this->controller->set('workflowStatus', $workflow['Workflow']['workflow_name']);
                }
            }
            $this->getAllApprovalRights($model, $workflowModels, $id);
            if($pending){
               
                $workflowRights = $this->Session->read('workflow');
                if(!empty($workflowRights)){
                    $this->getWorkflowApprovalRight($model, $step, $workflowRights, $workflowLog);
                }
            }
            
        }
    }


    private function getWorkflowApprovalRight($model, $step, $workflowRights, $workflowLog){
        $workflows = $this->Workflow->find('list',
            array(
                'fields'=>array('WorkflowStep.id', 'Workflow.action'),
                'joins' => array(
                        array(
                            'type' => 'INNER',
                            'table' => 'workflow_steps',
                            'alias' => 'WorkflowStep',
                            'conditions' => array('WorkflowStep.workflow_id = Workflow.id')
                        )
                ),
                'conditions'=>array('Workflow.model_name'=>$model)
            )
        );
        foreach($workflowRights as $workflowRight){
            if($workflowRight['Workflow']['model_name'] == $model){
                if($workflowRight['WorkflowStep']['step']==($step+1)){
                    $this->controller->set('workflowModel', $model);
                    $this->controller->set('workflowStepId', $workflowRight['WorkflowStep']['id']);
                    $this->controller->set('workflowStep', $workflowRight['WorkflowStep']['step']);
                    $this->controller->set('workflowAction', $workflows[$workflowRight['WorkflowStep']['id']]);
                    $this->controller->set('_approval', true);
                    break;
                }
            }
        }
    }

    private function getAllApprovalRights($model, $workflowModels, $id){
        $modelTemp = ClassRegistry::init($model);

        $modelTemp->id = $id;
        $createdUserId = $modelTemp->field('created_user_id');

        $viewWorkflowLog = false;

        if($createdUserId == $this->Auth->user('id')){
             $viewWorkflowLog = true;
        }else{
            $workflows = $this->Workflow->find('all',
                array(
                    'fields'=>array('DISTINCT(WorkflowStep.security_role_id) as RoleID'),
                    'joins' => array(
                        array(
                            'type' => 'LEFT',
                            'table' => 'workflow_steps',
                            'alias' => 'WorkflowStep',
                            'conditions' => array('Workflow.id = WorkflowStep.workflow_id')
                        )
                    ),
                    'conditions'=>array('Workflow.model_name'=>$workflowModels),
                    'order'=>array('Workflow.id, WorkflowStep.step')
                )
            );
            $workflowGroupUsers = $this->Session->read('workflowGroupUsers');
            if(!empty($workflowGroupUsers)){
                if(!empty($workflows)){
                    foreach($workflowGroupUsers as $workflowGroupUser){
                        foreach($workflows as $workflow){
                            if($workflowGroupUser['SecurityGroupUser']['RoleID'] == $workflow['WorkflowStep']['RoleID']){
                                $viewWorkflowLog = true;
                                break;
                            }
                        }
                    }
                   
                }
            }
        }

        if($viewWorkflowLog){
             $workflowLogs = $this->WorkflowLog->find('all',
                    array(
                        'fields'=>array('Workflow.workflow_name', 'Workflow.action', 'WorkflowLog.approve', 'WorkflowLog.comments', 'WorkflowLog.created' ,'SecurityUser.first_name', 'SecurityUser.last_name', 'WorkflowStep.step'),
                        'joins' => array(
                                array(
                                    'type' => 'LEFT',
                                    'table' => 'workflow_steps',
                                    'alias' => 'WorkflowStep',
                                    'conditions' => array('WorkflowLog.workflow_step_id = WorkflowStep.id')
                                ),
                                 array(
                                    'type' => 'LEFT',
                                    'table' => 'workflows',
                                    'alias' => 'Workflow',
                                    'conditions' => array('Workflow.id = WorkflowStep.workflow_id')
                                ),
                                 array(
                                    'type' => 'LEFT',
                                    'table' => 'security_users',
                                    'alias' => 'SecurityUser',
                                    'conditions' => array('SecurityUser.id = WorkflowLog.user_id')
                                )
                        ),
                        'order'=>array('WorkflowLog.created DESC'),
                        'conditions'=>array('WorkflowLog.model_name'=>$workflowModels, 'WorkflowLog.record_id'=>$id)
                    )
                );
             $this->controller->set('workflowLogs', $workflowLogs);
        }
        $this->controller->set('_viewApprovalLog', $viewWorkflowLog);

    }

    public function getCurrentWorkflowStep($model, $step){
        $this->Workflow = ClassRegistry::init('Workflow');
        $workflows = $this->Workflow->find('first',
            array(
                'fields'=>array('Workflow.workflow_name', 'Workflow.approve', 'WorkflowStep.id', 'WorkflowStep.step', 'WorkflowStep.security_role_id'),
                    'joins' => array(
                    array(
                        'type' => 'LEFT',
                        'table' => 'workflow_steps',
                        'alias' => 'WorkflowStep',
                        'conditions' => array('Workflow.id = WorkflowStep.workflow_id')
                    )
                ),
                'conditions'=>array('Workflow.model_name'=>$model, 'WorkflowStep.step'=>$step),
                'order'=>array('WorkflowStep.workflow_id, WorkflowStep.step')
            )
        );
        return $workflows;
    }

    public function getEndOfWorkflow($model, $step, $approve){
         if($approve){
            $workflows = $this->getCurrentWorkflowStep($model, $step+1);
            if(empty($workflows)){
                return true;
            }
        }
        return false;
    }

    public function updateApproval($data){
        $data['WorkflowLog']['user_id'] = $this->Auth->user('id');
        if($this->WorkflowLog->save($data)){
            return true;
        }
        return false;
    }

    public function getWorkflowStatus($model, $id, $status){
        $workflowModels = $this->getWorkflowIdByModel($model);
        $workflowStatus = '';

        if(!empty($workflowModels)){
            $this->WorkflowLog = ClassRegistry::init('WorkflowLog');
            $workflowLog = $this->WorkflowLog->find('first',
                array(
                    'fields'=>array('WorkflowLog.model_name', 'WorkflowLog.workflow_step_id', 'WorkflowLog.approve', 'WorkflowStep.step'),
                    'joins' => array(
                            array(
                                'type' => 'LEFT',
                                'table' => 'workflow_steps',
                                'alias' => 'WorkflowStep',
                                'conditions' => array('WorkflowLog.workflow_step_id = WorkflowStep.id')
                            )
                    ),
                    'order'=>array('WorkflowLog.record_id, WorkflowLog.created DESC', 'WorkflowLog.workflow_step_id DESC'),
                    'conditions'=>array('WorkflowLog.model_name'=>$workflowModels, 'WorkflowLog.record_id'=>$id)
                )
            );
            $step = 0;
            if(!empty($workflowLog)){
                $step = $workflowLog['WorkflowStep']['step'];
                if($workflowLog['WorkflowLog']['approve']=='0'){
                    $step = 0;
                }else{
                    if($workflowLog['WorkflowLog']['model_name']!=$workflowModels[key(array_slice($workflowModels, -1, 1, TRUE))]){
                        if($status=='2'){
                            $modelNext = $this->getCurrentModelName($workflowModels,$workflowLog['WorkflowLog']['model_name']);
                            $step = 0;
                            $model = $modelNext;
                        }
                    }
                    if($this->getEndOfWorkflow($model, $step, $workflowLog['WorkflowLog']['approve'])){
                        /*if($workflowLog['WorkflowLog']['model_name']!=$workflowModels[key(array_slice($workflowModels, -1, 1, TRUE))]){
                            if($this->getEndOfWorkflow($workflowLog['WorkflowLog']['model_name'], $step, $workflowLog['WorkflowLog']['approve'])){
                                $step = 0;
                            }
                            $model = $this->getCurrentModelName($workflowModels,$workflowLog['WorkflowLog']['model_name']);
                        }else{
                            $workflow = $this->getCurrentWorkflowStep($model, $step);
                            return !empty($workflow) ? __($workflow['Workflow']['approve']) : NULL;
                        }*/
                        $model = $workflowLog['WorkflowLog']['model_name'];
                        $workflow = $this->getCurrentWorkflowStep($model, $step);
                        return !empty($workflow) ? __($workflow['Workflow']['approve']) : NULL;
                    }
                }
                $workflow = $this->getCurrentWorkflowStep($model, $step+1);
                return !empty($workflow) ? __($workflow['Workflow']['workflow_name']) : NULL;
            }
               
        }
        return false;
    }

}
