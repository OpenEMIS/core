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