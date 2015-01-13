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

class InstitutionSiteSurveyCompleted extends AppModel {
	public $useTable = 'institution_site_surveys';

	public $actsAs = array(
		'ControllerAction2',
		'Surveys.Survey' => array(
			'module' => 'Institution',
			'status' => 2,
			'customfields' => array(
				'modelValue' => 'InstitutionSiteSurveyAnswer',
				'modelCell' => 'InstitutionSiteSurveyTableCell'
			),
			'conditions' => array(
				'institution_site_id' => array('sessionKey' => 'InstitutionSite.id')
			)
		)
	);

	public $belongsTo = array(
		'Surveys.SurveyTemplate',
		'Surveys.SurveyStatus',
		'AcademicPeriod' => array(
			'className' => 'SchoolYear',
			'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name', 'AcademicPeriod.order')
		),
		'SurveyStatusPeriod' => array(
			'className' => 'Surveys.SurveyStatusPeriod',
			'foreignKey' => false,
			'conditions' => array(
				'InstitutionSiteSurveyCompleted.academic_period_id = SurveyStatusPeriod.academic_period_id',
				'InstitutionSiteSurveyCompleted.survey_status_id = SurveyStatusPeriod.survey_status_id'
			)
		),
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
		'InstitutionSiteSurveyAnswer',
		'InstitutionSiteSurveyTableCell'
	);

	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Surveys', array('action' => $this->alias, $this->action));
		$this->Navigation->addCrumb('Completed');
		$this->setVar(compact('contentHeader'));
	}

	public function index() {
		$data = $this->getSurveyDataByStatus();
		$this->setVar(compact('data'));
	}

	public function view($id=0) {
		if ($this->exists($id)) {
			$data = $this->getSurveyById($id);
			$this->Session->write($this->alias.'.id', $id);
			$this->setVar(compact('id', 'data'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}

	public function details($id=0) {
		if ($this->exists($id)) {
			$templateData = $this->getSurveyTemplateBySurveyId($id);
			$data = $this->getFormatedSurveyData($templateData['id']);
			$dataValues = $this->getFormatedSurveyDataValues($id);

			$model = 'SurveyQuestion';
	    	$modelOption = 'SurveyQuestionChoice';
	    	$modelValue = 'InstitutionSiteSurveyAnswer';
	    	$modelRow = 'SurveyTableRow';
	    	$modelColumn = 'SurveyTableColumn';
			$modelCell = 'InstitutionSiteSurveyTableCell';
			$action = 'view';

			$this->setVar(compact('id', 'templateData', 'data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}

	public function remove() {
		if ($this->Session->check($this->alias.'.id')) {
			$id = $this->Session->read($this->alias.'.id');
			$result = $this->updateAll(
			    array(''.$this->alias.'.status' => 1),
			    array(
			    	''.$this->alias.'.id' => $id
			    )
			);
			if($result) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->log($this->validationErrors, 'debug');
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($this->alias.'.id');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}
}
