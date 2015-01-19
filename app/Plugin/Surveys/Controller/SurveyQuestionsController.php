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

class SurveyQuestionsController extends SurveysAppController {
	public $uses = array(
		'Surveys.SurveyQuestion',
		'Surveys.SurveyQuestionChoice',
		'Surveys.SurveyTableRow',
		'Surveys.SurveyTableColumn',
		'Surveys.SurveyTemplate'
	);

	public $components = array(
		'CustomField2' => array(
			'models' => array(
				'Module' => 'Surveys.SurveyModule',
				'Group' => 'Surveys.SurveyTemplate',
				'Field' => 'Surveys.SurveyQuestion',
				'FieldOption' => 'Surveys.SurveyQuestionChoice',
				'TableRow' => 'Surveys.SurveyTableRow',
				'TableColumn' => 'Surveys.SurveyTableColumn'
			)
		)
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$params = $this->params->named;

		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Surveys', array('action' => 'index'));

		if($this->action == 'reorder') {
			$params['action'] = 'index';
			$this->Navigation->addCrumb('Questions', $params);
			$this->Navigation->addCrumb('Reorder');
		} else if($this->action == 'preview') {
			$params['action'] = 'index';
			$this->Navigation->addCrumb('Questions', $params);
			$this->Navigation->addCrumb('Preview');
		} else {
			$this->Navigation->addCrumb('Questions');
		}

		$this->set('contentHeader', __('Questions'));
	}

	public function index() {
		$this->CustomField2->index();
    }

    public function view($id=0) {
    	$this->CustomField2->view($id);
	}

    public function add() {
    	$this->CustomField2->add();
    }

    public function edit($id=0) {
    	$this->CustomField2->edit($id);
    }

    public function delete() {
    	$this->CustomField2->delete();
    }

	public function reorder($id=0) {
		$this->CustomField2->reorder($id);
    }

    public function moveOrder($id=0) {
    	$this->CustomField2->moveOrder($id);
    }

    public function preview() {
    	$this->CustomField2->preview();
    }
}
?>