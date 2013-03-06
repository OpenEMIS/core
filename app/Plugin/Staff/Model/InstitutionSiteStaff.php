<?php
App::import('Model', 'Institution');
class InstitutionSiteStaff extends StaffAppModel {

	public $useTable = 'institution_site_staff';

	public function getData($id) {
		$options['joins'] = array(
            array('table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSite.id = InstitutionSiteStaff.institution_site_id'
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
            'InstitutionSiteStaff.staff_id' => $id,
        );


        $options['fields'] = array(
        	'InstitutionSite.name',
            'Institution.id',
        	'Institution.name',
        	'Institution.code',
            'InstitutionSiteStaff.id',
            'InstitutionSiteStaff.institution_site_id',
            'InstitutionSiteStaff.start_date',
            'InstitutionSiteStaff.end_date',
        );

		$list = $this->find('all', $options);

		return $list;
	}

    public function getInstitutionSelectionValues($list) {
        $InstitutionSite = ClassRegistry::init('InstitutionSite');
        return $data = $InstitutionSite->find('all',array('fields'=>array('InstitutionSite.id','Institution.name','InstitutionSite.name'),'conditions'=>array('InstitutionSite.id  '=>$list)));
    }
}