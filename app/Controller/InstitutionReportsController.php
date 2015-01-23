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

	public $components = array('Report');
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Reports';
		$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index'));
		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionReports', 'action' => 'index'));
		$this->Navigation->addCrumb('List of Reports');
    }

    public function ajaxGetReportProgress() {
    	return $this->Report->ajaxGetReportProgress();
    }
	
	public function index() {
		$this->Report->index();
	}

	public function generate($selectedFeature=0) {
		$i=0;
		$features = array(
			array('name' => __('Overview'), 'model' => 'InstitutionSite', 'period' => false),
			array('name' => __('Programmes'), 'model' => 'InstitutionSiteProgramme', 'period' => true),
			array('name' => __('Positions'), 'model' => 'InstitutionSitePosition', 'period' => true),
			array('name' => __('Shifts'), 'model' => 'InstitutionSiteShift', 'period' => true)
		);

		foreach ($features as $i => $feature) {
			$features[$i]['value'] = $i;
			$features[$i]['selected'] = ($selectedFeature == $i);
		}

		$this->Report->generate($features, $selectedFeature);
	}

	public function download($id) {
		$this->Report->download($id);
	}
}
