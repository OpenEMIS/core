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

class StudentGuardian extends StudentsAppModel {

    public $belongsTo = array(

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
        'guardian_relation_id' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please select a Relationship'
            )
        )
    );

    public function getGuardian($guardianId, $studentId) {
        $data = $this->find('first', array(
            'recursive' => -1,
            'fields' => array('Guardian.*', 'StudentGuardian.*', 'GuardianRelation.*', 'GuardianEducationLevel.*', 'CreatedUser.*', 'ModifiedUser.*'),
            'joins' => array(
                array(
                    'table' => 'guardians',
                    'alias' => 'Guardian',
                    'conditions' => array(
                        'StudentGuardian.guardian_id = Guardian.id'
                    )
                ),
                array(
                    'table' => 'guardian_relations',
                    'alias' => 'GuardianRelation',
                    'conditions' => array(
                        'StudentGuardian.guardian_relation_id = GuardianRelation.id'
                    )
                ),
                array(
                    'table' => 'guardian_education_levels',
                    'alias' => 'GuardianEducationLevel',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'Guardian.guardian_education_level_id = GuardianEducationLevel.id'
                    )
                ),
                array(
                    'table' => 'security_users',
                    'alias' => 'CreatedUser',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'StudentGuardian.created_user_id = CreatedUser.id'
                    )
                ),
                array(
                    'table' => 'security_users',
                    'alias' => 'ModifiedUser',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'StudentGuardian.modified_user_id = ModifiedUser.id'
                    )
                )
            ),
            'conditions' => array(
                'StudentGuardian.guardian_id' => $guardianId,
                'StudentGuardian.student_id' => $studentId
             )
        ));
        return $data;
    }

    public function getGuardians($studentId) {
        $data = $this->find('all', array(
            'recursive' => -1,
            'fields' => array('Guardian.*', 'StudentGuardian.*', 'GuardianRelation.*', 'GuardianEducationLevel.*', 'CreatedUser.*', 'ModifiedUser.*'),
            'joins' => array(
                array(
                    'table' => 'guardians',
                    'alias' => 'Guardian',
                    'conditions' => array(
                        'StudentGuardian.guardian_id = Guardian.id'
                    )
                ),
                array(
                    'table' => 'guardian_relations',
                    'alias' => 'GuardianRelation',
                    'conditions' => array(
                        'StudentGuardian.guardian_relation_id = GuardianRelation.id'
                    )
                ),
                array(
                    'table' => 'guardian_education_levels',
                    'alias' => 'GuardianEducationLevel',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'Guardian.guardian_education_level_id = GuardianEducationLevel.id'
                    )
                ),
                array(
                    'table' => 'security_users',
                    'alias' => 'CreatedUser',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'StudentGuardian.created_user_id = CreatedUser.id'
                    )
                ),
                array(
                    'table' => 'security_users',
                    'alias' => 'ModifiedUser',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'StudentGuardian.modified_user_id = ModifiedUser.id'
                    )
                )
            ),
            'conditions' => array(
                'StudentGuardian.student_id' => $studentId
            )
        ));

        return $data;
    }

}
