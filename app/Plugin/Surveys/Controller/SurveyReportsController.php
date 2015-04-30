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

class SurveyReportsController extends SurveysAppController {
	public $uses = array('InstitutionSiteSurvey');

	public $components = array('Report' => array('module' => 'Survey'));
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Reports';
		$this->Navigation->addCrumb('Reports', array('plugin' => 'Surveys', 'controller' => 'SurveyReports', 'action' => 'index'));
		$this->Navigation->addCrumb('Surveys', array('plugin' => 'Surveys', 'controller' => 'SurveyReports', 'action' => 'index'));
		$this->Navigation->addCrumb('List of Reports');
    }

    public function ajaxGetReportProgress() {
    	return $this->Report->ajaxGetReportProgress();
    }
	
	public function index() {
		$this->Report->index();
	}

	public function generate($selectedFeature=0) {
		$named = $this->request->params['named'];

		$SurveyModule = ClassRegistry::init('Surveys.SurveyModule');
		$modules = $SurveyModule->getModuleList();
		$selectedModule = !empty($modules) ? key($modules) : 0;

		$SurveyTemplate = ClassRegistry::init('Surveys.SurveyTemplate');
		$templates = $SurveyTemplate->getTemplateListByModule($selectedModule);
		$selectedSurveyTemplate = isset($named['surveyTemplate']) ? $named['surveyTemplate'] : key($templates);

		$templateOptions = array();
		foreach ($templates as $key => $template) {
			$templateOptions['surveyTemplate:' . $key] = $template;
		}

		$InstitutionSiteSurveyCompleted = ClassRegistry::init('InstitutionSiteSurveyCompleted');
		$periodOptions = $InstitutionSiteSurveyCompleted->find('list', array(
			'contain' => array('AcademicPeriod'),
			'fields' => array(
				'AcademicPeriod.id', 'AcademicPeriod.name'
			),
			'conditions' => array(
				'InstitutionSiteSurveyCompleted.survey_template_id' => $selectedSurveyTemplate
			),
			'group' => array(
				'AcademicPeriod.id'
			),
			'order' => array(
				'AcademicPeriod.order'
			)
		));

		$this->set('templateOptions', $templateOptions);

		$i=0;
		$features = array(
			array('name' => __('Institution'), 'model' => 'InstitutionSiteSurveyCompleted', 'survey_template' => true, 'period' => true)
		);

		foreach ($features as $i => $feature) {
			$features[$i]['value'] = $i;
			$features[$i]['selected'] = ($selectedFeature == $i);
		}

		$steps = array(
			'feature' => __('Feature'),
			'survey_template' => __('Templates'),
			'period' => __('Period'),
			'format' => __('Format')
		);
		$this->set('steps', $steps);
		$this->set('templateOptions', $templateOptions);
		$this->set('selectedSurveyTemplate', $selectedSurveyTemplate);
		$this->set('periodOptions', $periodOptions);
		$this->Report->generate($features, $selectedFeature);
	}

	public function download($id) {
		$this->Report->download($id);
	}
}
