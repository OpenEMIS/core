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

        $workflows = array();
        foreach($groupUsers as $groupUser){
            $workflows = $this->Workflow->find('all',
                array(
                    'fields'=>array('Workflow.model_name', 'Workflow.id', 'Workflow.workflow_name', 'WorkflowStep.id', 'WorkflowStep.step', 'WorkflowStep.security_role_id'),
                    'joins' => array(
                        array(
                            'type' => 'LEFT',
                            'table' => 'workflow_steps',
                            'alias' => 'WorkflowStep',
                            'conditions' => array('Workflow.id = WorkflowStep.workflow_id')
                        )
                    ),
                    'conditions'=>array('security_role_id'=>$groupUser['SecurityGroupUser']['RoleID']),
                    'order'=>array('Workflow.id, WorkflowStep.step')
                )
            );
        }

        return $workflows;
    }

    public function getApprovalWorkflow($model, $pending, $id){
        $workflowLog = $this->WorkflowLog->find('first',
            array(
                'fields'=>array('WorkflowLog.workflow_step_id', 'WorkflowLog.approve', 'WorkflowStep.step'),
                'joins' => array(
                        array(
                            'type' => 'LEFT',
                            'table' => 'workflow_steps',
                            'alias' => 'WorkflowStep',
                            'conditions' => array('WorkflowLog.workflow_step_id = WorkflowStep.id')
                        )
                ),
                'order'=>array('WorkflowLog.model_name, WorkflowLog.record_id, WorkflowLog.created DESC'),
                'conditions'=>array('WorkflowLog.model_name'=>$model, 'WorkflowLog.record_id'=>$id)
            )
        );


        $modelName = ClassRegistry::init($model);

        $step = 0;
        $new = false;
        if(!empty($workflowLog)){
            $step = $workflowLog['WorkflowStep']['step'];
            if($workflowLog['WorkflowLog']['approve']=='0'){
                $step = 0;
                $new = true;
            }
        }



        if(!$new && !$pending){
            $workflow = $this->getCurrentWorkflowStep($model, $step+1);
            if(!empty($workflow)){
                $this->controller->set('workflowStatus', $workflow['Workflow']['workflow_name']);
            }
        }
        
        $this->getAllApprovalRights($model, $id);
        $workflowRights = $this->Session->read('workflow');
        if(empty($workflowRights)){
            $this->controller->set('_approval', false);
        }else{
            $this->getWorkflowApprovalRight($model, $step, $workflowRights, $workflowLog);
        }
    }


    private function getWorkflowApprovalRight($model, $step, $workflowRights, $workflowLog){
        $this->controller->set('_approval', false);

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
            if($workflowRight['Workflow']['model_name']==$model){
                if(empty($workflowLog)){
                    if($workflowRight['WorkflowStep']['step']==1){
                        $this->controller->set('workflowStepId', $workflowRight['WorkflowStep']['id']);
                        $this->controller->set('workflowStep', $workflowRight['WorkflowStep']['step']);
                        $this->controller->set('workflowAction', $workflows[$workflowRight['WorkflowStep']['id']]);
                        $this->controller->set('_approval', true);
                        break;
                    }
                }else{
                    if($workflowRight['WorkflowStep']['step']==($step+1)){
                        $this->controller->set('workflowStepId', $workflowRight['WorkflowStep']['id']);
                        $this->controller->set('workflowStep', $workflowRight['WorkflowStep']['step']);
                        $this->controller->set('workflowAction', $workflows[$workflowRight['WorkflowStep']['id']]);
                        $this->controller->set('_approval', true);
                        break;
                    }
                }
            }

        }
    }

    private function getAllApprovalRights($model, $id){
        $this->controller->set('_viewApprovalLog', false);
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
                    'conditions'=>array('Workflow.model_name'=>$model),
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
                        'conditions'=>array('WorkflowLog.model_name'=>$model, 'WorkflowLog.record_id'=>$id)
                    )
                );
             $this->controller->set('workflowLogs', $workflowLogs);
        }
        $this->controller->set('_viewApprovalLog', $viewWorkflowLog);

    }

    public function getCurrentWorkflowStep($model, $step){
         $Workflow = ClassRegistry::init('Workflow');
         $workflows = $Workflow->find('first',
            array(
                'fields'=>array('Workflow.workflow_name', 'WorkflowStep.id', 'WorkflowStep.step', 'WorkflowStep.security_role_id'),
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

    public function getWorkflowStatus($model, $id){
        $workflowStatus = '';
        $WorkflowLog = ClassRegistry::init('WorkflowLog');
        $workflowLog = $WorkflowLog->find('first',
            array(
                'fields'=>array('WorkflowLog.workflow_step_id', 'WorkflowLog.approve', 'WorkflowStep.step'),
                'joins' => array(
                        array(
                            'type' => 'LEFT',
                            'table' => 'workflow_steps',
                            'alias' => 'WorkflowStep',
                            'conditions' => array('WorkflowLog.workflow_step_id = WorkflowStep.id')
                        )
                ),
                'order'=>array('WorkflowLog.model_name, WorkflowLog.record_id, WorkflowLog.created DESC'),
                'conditions'=>array('WorkflowLog.model_name'=>$model, 'WorkflowLog.record_id'=>$id)
            )
        );

        $step = 0;
        if(!empty($workflowLog)){
            $step = $workflowLog['WorkflowStep']['step'];
        }

        $workflow = $this->getCurrentWorkflowStep($model, $step+1);
        $workflowStatus = !empty($workflow) ? $workflow['Workflow']['workflow_name'] : NULL;
        return $workflowStatus;
    }

}
