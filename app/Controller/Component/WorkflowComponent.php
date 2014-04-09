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
    public $components = array('Session','Auth');

    private $modelMap = array(
        'GroupUser' => 'SecurityGroupUser',
        'Workflow' => 'Workflow',
        'WorkflowStep' => 'WorkflowStep'
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
        }
    }

    public function getWorkflow($userId){
        $groupUsers = $this->GroupUser->find('all', 
            array(
                'fields'=>array('Distinct(security_role_id) as RoleID'),
                'conditions'=>array('security_user_id'=>$userId)
            )
        );

        $workflows = array();
        foreach($groupUsers as $groupUser){
            $workflows = $this->Workflow->find('all',
                array(
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

}
