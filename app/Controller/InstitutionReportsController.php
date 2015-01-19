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

App::uses('AppController', 'Controller');

class InstitutionReportsController extends AppController {
	public $uses = array('InstitutionSite');
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
		
		if ($this->Session->check('InstitutionSite.id')) {
			$this->institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			$name = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
			$this->bodyTitle = $name;
			
			$this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'view'));
			$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index'));
		} else {
			$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
    }
	
	
}	
