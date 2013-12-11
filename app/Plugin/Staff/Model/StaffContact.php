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

class StaffContact extends StaffAppModel {
	public $belongsTo = array(
		'Staff',
		'ContactType',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'contact_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Contact Type'
			)
		),
		'value' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid value'
			)
		),
		'preferred' => array(
			 'comparison' => array(
            	'rule'=>array('validatePreferred', 'preferred'), 
            	'allowEmpty'=>true,
            	'message' => 'Please select a preferred for the selected contact type'
            )
		),
	);

	function validatePreferred($check1, $field2) {
	 	$flag = false;
        foreach($check1 as $key=>$value1) {
            $preferred = $this->data[$this->alias][$field2];
			$contactOption = $this->data[$this->alias]['contact_option_id'];
			if($preferred=="0"){
	            $count = $this->find('count', array('conditions'=>array('ContactType.contact_option_id'=>$contactOption)));
	            if($count!=0){
	            	$flag = true;
	            }
            }else{
            	$flag = true;
            }

        }
        return $flag;
    }
}
