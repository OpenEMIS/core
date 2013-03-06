<?php
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