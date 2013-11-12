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

App::uses('UtilityComponent', 'Component');

class StaffQualification extends StaffAppModel {
    public $useTable = "staff_qualifications";
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
        'qualification_title' => array(
            'required' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Qualification Title'
            )
        ),
        'graduate_year' => array(
            'required' => array(
                'rule' => 'numeric',
                'required' => true,
                'message' => 'Please enter a valid Graduate Year'
            )
        ),
        'qualification_level_id' => array(
            'required' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Qualification Level'
            )
        ),
        'qualification_specialisation_id' => array(
            'required' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Major/Specialisation'
            )
        ),
    );

    public function getData($id) {
        $utility = new UtilityComponent(new ComponentCollection);
        $options['joins'] = array(
            array('table' => 'qualification_institutions',
                'alias' => 'QualificationInstitution',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualificationInstitution.id = StaffQualification.qualification_institution_id'
                )
            ),
            array('table' => 'qualification_specialisations',
                'alias' => 'QualificationSpecialisation',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualificationSpecialisation.id = StaffQualification.qualification_specialisation_id'
                )
            ),
            array('table' => 'qualification_levels',
                'alias' => 'QualificationLevel',
                'type' => 'LEFT',
                'conditions' => array(
                    'QualificationLevel.id = StaffQualification.qualification_level_id'
                )
            ),
        );

        $options['fields'] = array(
            'StaffQualification.id',
            'StaffQualification.document_no',
            'StaffQualification.graduate_year',
            'StaffQualification.gpa',
            'StaffQualification.qualification_title',
            'StaffQualification.qualification_institution_country',
            'StaffQualification.qualification_institution_id as institute_id',
            'QualificationInstitution.name as institute',
            'StaffQualification.qualification_level_id as level_id',
            'QualificationLevel.name as level',
            'StaffQualification.qualification_specialisation_id as specialisation_id',
            'QualificationSpecialisation.name as specialisation'
        );

        $options['conditions'] = array(
            'StaffQualification.staff_id' => $id,
        );

        $options['order'] = array('StaffQualification.graduate_year DESC');

        $list = $this->find('all', $options);
        $list = $utility->formatResult($list);

        return $list;
    }

}
