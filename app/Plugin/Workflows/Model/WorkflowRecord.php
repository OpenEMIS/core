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

class WorkflowRecord extends WorkflowsAppModel {
	public $tablePrefix = 'wf_';
	public $useTable = 'workflow_records';

	public $belongsTo = array(
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
        'WorkflowComment' => array(
            'className' => 'Workflows.WorkflowComment',
            'foreignKey' => 'workflow_record_id',
			'dependent' => true
        ),
        'WorkflowTransition' => array(
            'className' => 'Workflows.WorkflowTransition',
            'foreignKey' => 'workflow_record_id',
			'dependent' => true
        )
    );
}
