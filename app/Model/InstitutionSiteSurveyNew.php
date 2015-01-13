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

class InstitutionSiteSurveyNew extends AppModel {
	public $useTable = 'institution_site_surveys';

	public $actsAs = array(
		'ControllerAction2',
		'Surveys.Survey' => array(
			'module' => 'Institution',
			'status' => 0,
			'customfields' => array(
				'modelValue' => 'InstitutionSiteSurveyAnswer',
				'modelCell' => 'InstitutionSiteSurveyTableCell'
			),
			'conditions' => array(
				'institution_site_id' => array('sessionKey' => 'InstitutionSite.id'),
				'student_id' => array('sessionKey' => 'student.id')
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
				'InstitutionSiteSurveyNew.academic_period_id = SurveyStatusPeriod.academic_period_id',
				'InstitutionSiteSurveyNew.survey_status_id = SurveyStatusPeriod.survey_status_id'
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
		$this->Navigation->addCrumb('New');
		$this->setVar(compact('contentHeader'));
	}

	public function index() {
		$data = $this->getSurveyStatusByModule();
		$this->setVar(compact('data'));
	}

	public function view($academicPeriodId=0, $surveyStatusId=0) {
		if(isset($this->request->params['pass'][1])) {
			$academicPeriodId = $this->request->params['pass'][1];
		}
		if(isset($this->request->params['pass'][2])) {
			$surveyStatusId = $this->request->params['pass'][2];
		}

		$data = $this->getSurveyStatusPeriod($academicPeriodId, $surveyStatusId);

		if($data) {
			$this->Session->write($this->alias.'.academicPeriodId', $academicPeriodId);
			$this->Session->write($this->alias.'.surveyStatusId', $surveyStatusId);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}

		$this->setVar(compact('data', 'academicPeriodId', 'surveyStatusId'));
	}

	public function add() {
		if ($this->Session->check($this->alias.'.surveyStatusId')) {
			$academicPeriodId = $this->Session->read($this->alias.'.academicPeriodId');
			$surveyStatusId = $this->Session->read($this->alias.'.surveyStatusId');

			$templateData = $this->getSurveyTemplate($academicPeriodId, $surveyStatusId);
			$data = $this->getFormatedSurveyData($templateData['id']);
			$dataValues = array();

			$model = 'SurveyQuestion';
	    	$modelOption = 'SurveyQuestionChoice';
	    	$modelValue = 'InstitutionSiteSurveyAnswer';
	    	$modelRow = 'SurveyTableRow';
	    	$modelColumn = 'SurveyTableColumn';
			$modelCell = 'InstitutionSiteSurveyTableCell';
			$action = 'edit';

			if ($this->request->is(array('post', 'put'))) {

				$surveyData = $this->prepareSubmitSurveyData($this->request->data);

				if ($this->saveAll($surveyData)) {
					if($surveyData[$this->alias]['status'] == 2) {
						$this->Message->alert('Survey.save.final');
						return $this->redirect(array('action' => $this->alias, 'index'));
					} else {
						$this->Message->alert('Survey.save.draft');
						return $this->redirect(array('action' => 'InstitutionSiteSurveyDraft', 'edit', $this->id));
					}
				} else {
					$dataValues = $this->prepareFormatedDataValues($surveyData);
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}

			$this->setVar(compact('academicPeriodId', 'surveyStatusId', 'templateData', 'data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}
}
