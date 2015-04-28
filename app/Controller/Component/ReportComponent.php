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

class ReportComponent extends Component {
	private $controller;
	public $Period;
	public $ReportProgress;

	public $components = array('Auth');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$steps = array(
			'feature' => __('Feature'),
			'period' => __('Period'),
			'format' => __('Format')
		);
		$formatOptions = array(
			'excel' => 'Excel'
		);
		$this->controller->set('steps', $steps);
		$this->controller->set('formatOptions', $formatOptions);

		$this->Period = ClassRegistry::init('AcademicPeriod');
		$this->controller->set('periodOptions', $this->Period->getAvailableAcademicPeriods());

		$this->ReportProgress = ClassRegistry::init('ReportProgress');
	}
	
	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
		
	}
	
	// Is called after the controller executes the requested action's logic, but before the controller's renders views and layout.
	public function beforeRender(Controller $controller) {
		
	}

	// Is called before output is sent to the browser.
	public function shutdown(Controller $controller) {

	}

	// Is invoked when the controller’s redirect method is called but before any further action.
	public function beforeRedirect(Controller $controller, $url, $status=null, $exit=true) {
		
	}

	public function ajaxGetReportProgress() {
		$this->controller->autoRender = false;

		$userId = $this->Auth->user('id');
		$id = $this->controller->params->query['id'];
		$fields = array(
			'ReportProgress.status',
			'ReportProgress.modified',
			'ReportProgress.current_records',
			'ReportProgress.total_records'
		);
		$obj = $this->ReportProgress->findById($id);
		$data = array();

		if ($obj) {
			if ($obj['ReportProgress']['total_records'] > 0) {
				$data['percent'] = intval($obj['ReportProgress']['current_records'] / $obj['ReportProgress']['total_records'] * 100);
			} else {
				$data['percent'] = 0;
			}
			$data['modified'] = $obj['ReportProgress']['modified'];
			$data['status'] = $obj['ReportProgress']['status'];
		}
		return json_encode($data);
	}

	public function index($module=null) {
		if (is_null($module)) {
			if (isset($this->settings['module'])) {
				$module = $this->settings['module'];
			}
		}
		$userId = $this->Auth->user('id');
		$this->ReportProgress->purge($userId);
		
		$model = 'ReportProgress';
		$data = $this->ReportProgress->findAllByModuleAndCreatedUserId($module, $userId, array(), array('created' => 'desc'));
		$this->controller->set(compact('data', 'model'));
		$this->controller->render('/Elements/reports/index');
	}

	public function generate($features, $selectedFeature) {
		$request = $this->controller->request;
		
		if ($request->is('post')) {
			$name = $features[$selectedFeature]['name'];
			$format = $request->data['Report']['format'];
			$period = null;
			$params = array('model' => $features[$selectedFeature]['model'], 'format' => $format, 'options' => array());
			if (array_key_exists('template', $request->data['Report'])) {
				$SurveyTemplate = ClassRegistry::init('Surveys.SurveyTemplate');
				$templateId = $request->data['Report']['template'];
				$template = $SurveyTemplate->field('name', array('id' => $templateId));
				$name .= ' (' . $template . ')';
				$params['options']['conditions'] = array('SurveyTemplate.id' => $templateId);
			}
			if (array_key_exists('period', $request->data['Report'])) {
				$periodId = $request->data['Report']['period'];
				$period = $this->Period->field('name', array('id' => $periodId));
				$name .= ' (' . $period . ')';
				$params['options']['conditions'] = array('AcademicPeriod.id' => $periodId);
			}
			$obj = array(
				'name' => $name,
				'module' => $this->settings['module'],
				'params' => $params
			);
			//pr($request->data);die;
			//pr($obj);die;
			$id = $this->ReportProgress->addReport($obj);
			if ($id !== false) {
				$this->ReportProgress->generate($id);
			}
			return $this->controller->redirect(array('action' => 'index'));
		}

		$this->controller->set('features', $features);
		$this->controller->set('selectedFeature', $selectedFeature);
		$this->controller->render('/Elements/reports/generate');
	}

	public function download($id) {
		$this->controller->autoRender = false;
		$this->ReportProgress->id = $id;
		$path = $this->ReportProgress->field('file_path');
		$filename = basename($path);
		
		header("Pragma: public", true);
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=".$filename);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($path));
		echo file_get_contents($path);
	}
}
