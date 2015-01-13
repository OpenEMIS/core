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

class QualificationInstitution extends AppModel {
	public $useTable = "qualification_institutions";
	public $hasMany = array('StaffQualification');

	public function getOptions(){
		$data = $this->find('all', array('recursive' => -1, 'conditions'=>array('visible'=>1), 'order' => array('QualificationInstitution.order')));
		$list = array();

		foreach($data as $obj){
			$list[$obj['QualificationInstitution']['id']] = $obj['QualificationInstitution']['name'];
		}

		return $list;
	}

	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('QualificationInstitution.id','QualificationInstitution.name'),
			'conditions' => array('QualificationInstitution.name LIKE' => $search
			),
			'order' => array('QualificationInstitution.order')
		));
		
		$data = array();
		
		foreach($list as $obj) {
			$institutionId = $obj['QualificationInstitution']['id'];
			$institutionName = $obj['QualificationInstitution']['name'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $institutionName)),
				'value' => array('qualification-institution-id' => $institutionId, 'qualification-institution-name' => $institutionName)
			);
		}

		return $data;
	}
}
