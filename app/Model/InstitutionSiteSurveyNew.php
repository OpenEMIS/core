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
				'institution_site_id' => array('sessionKey' => 'InstitutionSite.id')
			)
		)
	);

	public $belongsTo = array(
		'Surveys.SurveyTemplate',
		'AcademicPeriod' => array(
			'className' => 'SchoolYear',
			'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name', 'AcademicPeriod.order')
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

	public function beforeSave($options=array()) {
		/*not working
		foreach ($this->data['InstitutionSiteSurveyAnswer'] as $key => $obj) {
			switch($obj['InstitutionSiteSurveyAnswer']['type']) {
				case 2:
					$fieldName = 'text_value';
					break;
				case 3:
					$fieldName = 'int_value';
					break;
				case 4:
					$fieldName = 'int_value';
					break;
				case 5:
					$fieldName = 'textarea_value';
					break;
				case 6:
					$fieldName = 'int_value';
					break;
				default:
					$fieldName = 'text_value';
			}
			if(empty($obj['InstitutionSiteSurveyAnswer'][$fieldName])) {
				unset($this->data['InstitutionSiteSurveyAnswer'][$key]);
			}
		}

		return true;
		*/
	}

	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Surveys', array('action' => $this->alias, $this->action));
		$this->Navigation->addCrumb('New');
		$this->setVar(compact('contentHeader'));
	}

	public function index() {
		$data = $this->getSurveyTemplatesByModule();
		$this->setVar(compact('data'));
	}

	public function add($templateId=0, $academicPeriodId=0) {
		if ($this->SurveyTemplate->exists($templateId)) {
			$this->SurveyTemplate->contain();
			$template = $this->SurveyTemplate->findById($templateId);
			$data = $this->getFormatedSurveyData($templateId);
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

			$this->setVar(compact('templateId', 'academicPeriodId', 'template', 'data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}
}
