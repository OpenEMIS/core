<?php
App::import('Model', 'Institution');
class InstitutionSiteStudent extends StudentsAppModel {

	public $useTable = 'institution_site_students';
/*	
	public $belongsTo = array(
		// 'InstitutionSite' => array('foreignKey' => 'institution_site_id')
		// 'SchoolYear'
	);	*/
	public $validate = array(
		'start_date' => array(
			'ruleRequired' => array(
				'rule' => 'date',
				'required' => true,
				'message' => 'Please select a valid Start Date'
			)
		),
		'end_date' => array(
			'ruleRequired' => array(
				'rule' => 'date',
				'required' => true,
				'message' => 'Please select a valid End Name'
			)
		)
	);
	public function getData($id) {
		$options['joins'] = array(
            array('table' => 'institution_sites',
                'alias' => 'InstitutionSite',
                'type' => 'LEFT',
                'conditions' => array(
                    'InstitutionSite.id = InstitutionSiteStudent.institution_site_id'
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


        

        $options['fields'] = array(
        	'InstitutionSite.name',
            'Institution.id',
        	'Institution.name',
        	'Institution.code',
            'InstitutionSiteStudent.id',
            'InstitutionSiteStudent.institution_site_id',
            'InstitutionSiteStudent.start_date',
            'InstitutionSiteStudent.end_date',
        );
		$options['conditions'] = array(
            'InstitutionSiteStudent.student_id' => $id,
        );
		//pr($options);die;

		$list = $this->find('all', $options);

		return $list;
	}
	
    public function getInstitutionSelectionValues($list) {
		$InstitutionSite = ClassRegistry::init('InstitutionSite');
		return $data = $InstitutionSite->find('all',array('fields'=>array('InstitutionSite.id','Institution.name','InstitutionSite.name'),'conditions'=>array('InstitutionSite.id  '=>$list)));
    }
}