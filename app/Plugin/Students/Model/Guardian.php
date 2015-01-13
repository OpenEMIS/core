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

class Guardian extends StudentsAppModel {

    public $belongsTo = array(
		'Students.GuardianEducationLevel',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    //public $hasMany = array('StudentGuardian');

    public $validate = array(
        'first_name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter First Name'
            )
        ),
        'last_name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter Last Name'
            )
        ),
        'gender' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please select a Gender'
            )
        )
    );

    public function getAutoCompleteList($search, $student_id = 0) {
        $search = sprintf('%%%s%%', $search);
        $invalidIds = array();
        
        if($student_id !== 0){
            $invalidGuardians = $this->find('all', array(
                'recursive' => -1,
                'fields' => array('DISTINCT Guardian.id'),
                'joins' => array(
                    array(
                        'table' => 'student_guardians',
                        'alias' => 'StudentGuardian',
                        'conditions' => array('Guardian.id = StudentGuardian.guardian_id')
                    )
                ),
                'conditions' => array(
                    'StudentGuardian.student_id' => $student_id
                )
            ));
            
            foreach($invalidGuardians AS $obj){
                $invalidIds[] = $obj['Guardian']['id'];
            }
        }
        
        if(empty($invalidIds)){
            $list = $this->find('all', array(
                'recursive' => -1,
                'fields' => array('Guardian.*'),
                'conditions' => array(
                    'OR' => array(
                        'Guardian.first_name LIKE' => $search,
                        'Guardian.last_name LIKE' => $search
                    )
                ),
                'order' => array('Guardian.first_name', 'Guardian.last_name')
            ));
        }else{
            $list = $this->find('all', array(
                'recursive' => -1,
                'fields' => array('Guardian.*'),
                'conditions' => array(
                    "NOT" => array('Guardian.id' => $invalidIds),
                    'OR' => array(
                        'Guardian.first_name LIKE' => $search,
                        'Guardian.last_name LIKE' => $search
                    )
                ),
                'order' => array('Guardian.first_name', 'Guardian.last_name')
            ));
        }

        $data = array();
        foreach ($list as $obj) {
            $guardian = $obj['Guardian'];
            $data[] = array(
                'label' => sprintf('%s %s', $guardian['first_name'], $guardian['last_name']),
                'value' => $guardian['id'],
                'first_name' => $guardian['first_name'],
                'last_name' => $guardian['last_name'],
                'gender' => $guardian['gender'],
                'email' => $guardian['email'],
                'home_phone' => $guardian['home_phone'],
                'office_phone' => $guardian['office_phone'],
                'mobile_phone' => $guardian['mobile_phone'],
                'address' => $guardian['address'],
                'postal_code' => $guardian['postal_code'],
                'occupation' => $guardian['occupation'],
                'comments' => $guardian['comments'],
                'guardian_education_level_id' => $guardian['guardian_education_level_id']
            );
        }
        return $data;
    }

}
