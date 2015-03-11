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

class WfWorkflow extends WorkflowsAppModel {
	public $useTable = 'wf_workflows';

	public $belongsTo = array(
		'WorkflowModel' => array(
            'className' => 'Workflows.WorkflowModel',
            'foreignKey' => 'wf_workflow_model_id'
        ),
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
        'WfWorkflowStep' => array(
            'className' => 'Workflows.WfWorkflowStep',
			'dependent' => true
        )
    );

    public $validate = array(
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a code'
			),
			'unique' => array(
	            'rule' => array('checkUnique', array('code'), false),
	            'message' => 'This code is already exists in the system'
	        )
		),
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		),
		'wf_workflow_model_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a form'
			),
			'unique' => array(
	            'rule' => array('checkUnique', array('wf_workflow_model_id'), false),
	            'message' => 'This form is already exists in the system'
	        )
		)
	);
}
