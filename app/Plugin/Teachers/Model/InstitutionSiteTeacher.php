<?php
App::import('Model', 'Institution');
class InstitutionSiteTeacher extends TeachersAppModel {

    public $useTable = 'institution_site_teachers';

    public function getData($id) {
        $options['joins'] = array(
            array('table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSite.id = InstitutionSiteTeacher.institution_site_id'
                )
            ),
            array('table' => 'institutions',
                'alias' => 'Institution',
                'type' => 'LEFT',
                'conditions' => array(
                    'Institution.id = InstitutionSite.institution_id'
                )
            )
        );

        $options['conditions'] = array(
            'InstitutionSiteTeacher.teacher_id' => $id,
        );

        $options['fields'] = array(
            'InstitutionSite.name',
            'Institution.id',
            'Institution.name',
            'Institution.code',
            'InstitutionSiteTeacher.id',
            'InstitutionSiteTeacher.institution_site_id',
            'InstitutionSiteTeacher.start_date',
            'InstitutionSiteTeacher.end_date',
        );

        $list = $this->find('all', $options);

        return $list;
    }

    public function getInstitutionSelectionValues($list) {
        $InstitutionSite = ClassRegistry::init('InstitutionSite');
        return $data = $InstitutionSite->find('all',array('fields'=>array('InstitutionSite.id','Institution.name','InstitutionSite.name'),'conditions'=>array('InstitutionSite.id  '=>$list)));
    }

}