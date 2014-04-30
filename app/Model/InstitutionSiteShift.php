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

App::uses('AppModel', 'Model');

class InstitutionSiteShift extends AppModel {

    public $belongsTo = array(
        'ModifiedUser' => array('foreignKey' => 'modified_user_id', 'className' => 'SecurityUser'),
        'CreatedUser' => array('foreignKey' => 'created_user_id', 'className' => 'SecurityUser'),
    );
    public $validate = array(
        'name' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a shift name'
            )
        ),
        'school_year_id' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please select a school year'
            )
        ),
        'start_time' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a start time'
            )
        ),
        'end_time' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a end time'
            )
        )
    );
    
    public function getAllShiftsByInstitutionSite($institutionSiteId){
        $result = $this->find('all', array(
            'recursive' => -1,
            'fields' => array('InstitutionSiteShift.*', 'InstitutionSite.*', 'SchoolYear.*'),
            'joins' => array(
                array(
                    'table' => 'school_years',
                    'alias' => 'SchoolYear',
                    'type' => 'LEFT',
                    'conditions' => array('InstitutionSiteShift.school_year_id = SchoolYear.id')
                ),
                array(
                    'table' => 'institution_sites',
                    'alias' => 'InstitutionSite',
                    'type' => 'LEFT',
                    'conditions' => array('InstitutionSiteShift.location_institution_site_id = InstitutionSite.id')
                )
            ),
            'conditions' => array('InstitutionSiteShift.institution_site_id' => $institutionSiteId)
        ));
        
        return $result;
    }
    
    public function getShiftById($shiftId){
        $data = $this->find('first', array(
            'recursive' => -1,
            'fields' => array('InstitutionSiteShift.*', 'InstitutionSite.*', 'SchoolYear.*', 'CreatedUser.*', 'ModifiedUser.*'),
            'joins' => array(
                array(
                    'table' => 'school_years',
                    'alias' => 'SchoolYear',
                    'type' => 'LEFT',
                    'conditions' => array('InstitutionSiteShift.school_year_id = SchoolYear.id')
                ),
                array(
                    'table' => 'institution_sites',
                    'alias' => 'InstitutionSite',
                    'type' => 'LEFT',
                    'conditions' => array('InstitutionSiteShift.location_institution_site_id = InstitutionSite.id')
                ),
                array(
                    'table' => 'security_users',
                    'alias' => 'CreatedUser',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteShift.created_user_id = CreatedUser.id'
                    )
                ),
                array(
                    'table' => 'security_users',
                    'alias' => 'ModifiedUser',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'InstitutionSiteShift.modified_user_id = ModifiedUser.id'
                    )
                )
            ),
            'conditions' => array('InstitutionSiteShift.id' => $shiftId)
        ));

        return $data;
    }

}

?>
