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
App::uses('Sanitize', 'Utility');

class InstitutionReportsController extends AppController {
	public $options = array();
	public $institutionSiteId;
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
		
		if ($this->Session->check('InstitutionSiteId')) {
			$this->institutionSiteId = $this->Session->read('InstitutionSiteId');
			
			$InstitutionSiteModel = ClassRegistry::init('InstitutionSite');
			$institutionSiteName = $InstitutionSiteModel->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
			$this->bodyTitle = $institutionSiteName;
			
			$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view'));
			$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index'));
		} else {
			$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
		}
    }
	
	public function generate($model, $format, $index=false) {
		$this->autoRender = false;
		$modelObj = ClassRegistry::init($model);
		$method = $modelObj->getFormatFunction($format);
		if($method !== false) {
			$args = array($this->institutionSiteId, $index);
			$result = call_user_func_array(array($modelObj, $method), $args);
			//pr($result);
		}
	}
	
	public function index() {
		return $this->redirect(array('action' => 'general'));
	}
	
	public function general() {
		$header = __('General');
		$this->Navigation->addCrumb($header);
		
		$data = array(
			array('name' => 'Overview and More', 'model' => 'InstitutionSite', 'params' => array('csv' => 1)),
			array('name' => 'Bank Accounts', 'model' => 'InstitutionSiteBankAccount')
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}
	
	public function details() {
		$header = __('Details');
		$this->Navigation->addCrumb($header);
		
		$data = array(
			array('name' => 'Programme List', 'model' => 'InstitutionSiteProgramme', 'params' => array('csv' => 1)),
			array('name' => 'Student List', 'model' => 'InstitutionSiteStudent', 'params' => array('csv' => 1))
		);
		
		foreach($data as $i => $obj) {
			$model = ClassRegistry::init($obj['model']);
			$formats = $model->getSupportedFormats();
			$data[$i]['formats'] = $formats;
		}
		
		$this->set(compact('data', 'header'));
		$this->render('index');
	}
	
}	
