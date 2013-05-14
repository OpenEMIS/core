<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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

class TeacherQualification extends TeachersAppModel {
    public $useTable = "teacher_qualifications";
    
    public function getData($id) {
        $utility = new UtilityComponent(new ComponentCollection);
        $options['joins'] = array(
            array('table' => 'teacher_qualification_institutions',
                'alias' => 'TeacherQualificationInstitution',
                'type' => 'LEFT',
                'conditions' => array(
                    'TeacherQualificationInstitution.id = TeacherQualification.teacher_qualification_institution_id'
                )
            ),
            array('table' => 'teacher_qualification_certificates',
                'alias' => 'TeacherQualificationCertificates',
                'type' => 'LEFT',
                'conditions' => array(
                    'TeacherQualificationCertificates.id = TeacherQualification.teacher_qualification_certificate_id'
                )
            ),
            array('table' => 'teacher_qualification_categories',
                'alias' => 'TeacherQualificationCategories',
                'type' => 'LEFT',
                'conditions' => array(
                    'TeacherQualificationCategories.id = TeacherQualificationCertificates.teacher_qualification_category_id'
                )
            ),
        );

        $options['fields'] = array(
            'TeacherQualification.id',
            'TeacherQualification.certificate_no',
            'TeacherQualification.issue_date',
            'TeacherQualification.teacher_qualification_institution_id as institute_id',
            'TeacherQualificationInstitution.name as institute',
            'TeacherQualification.teacher_qualification_certificate_id as certificate_id',
            'TeacherQualificationCertificates.name as certificate',
            'TeacherQualificationCategories.id as category_id',
            'TeacherQualificationCategories.name as category'
        );

        $options['conditions'] = array(
            'TeacherQualification.teacher_id' => $id,
        );

        $options['order'] = array('TeacherQualification.issue_date DESC');

        $list = $this->find('all', $options);
        $list = $utility->formatResult($list);

        return $list;
    }

}