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