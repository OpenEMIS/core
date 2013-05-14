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

App::uses('AppModel', 'Model');

class CensusRoom extends AppModel {
    public $belongsTo = array(
		'InfrastructureRoom' => array('foreignKey' => 'infrastructure_room_id')
	);

	/*public function getTest() {
        $this->bindModel(array(
            'belongsTo'=> array(
				'SchoolYear' => array('foreignKey' => 'school_year_id'),
				'InfrastructureStatus' => array('foreignKey' => 'infrastructure_status_id'),
				'InstitutionSite'=>array('foreignKey' => 'institution_site_id'),
				'Institution' => array(
	                'joinTable'  => 'institutions',
					'foreignKey' => false,
	                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
	            )
   			)
        ));
        return $this->find('all', array('limit' => 5));
	}*/
}