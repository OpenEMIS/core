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
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Institutions';
		$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
		$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index'));
    }
	
	public function generate($model, $format, $index=false) {
		$this->render = false;
		$modelObj = ClassRegistry::init($model);
		$method = $modelObj->getFormatFunction($format);
		if($method !== false) {
			$result = call_user_func_array(array($modelObj, $method), array($index));
			pr($result);die;
		}
	}
	
	public function index() {
		return $this->redirect(array('action' => 'general'));
	}
	
	public function general() {
		$header = __('General');
		$this->Navigation->addCrumb($header);
		
		$data = array(
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
}	
