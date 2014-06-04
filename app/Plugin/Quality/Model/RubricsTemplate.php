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

class RubricsTemplate extends QualityAppModel {
	public $actsAs = array('ControllerAction', 'Quality.RubricsSetup');
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	public $hasMany = array(
		'RubricsTemplateHeader' => array(
			'foreignKey' => 'rubric_template_id',
		),
		'RubricsTemplateColumnInfo' => array(
			'foreignKey' => 'rubric_template_id',
		)
	);
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name.'
			)
		),
		'pass_mark' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Pass Mark.'
			)
		)
	);
	public $weightingOptions = array(1 => 'Points', 2 => 'Percentage');

	public function beforeAction($controller, $action) {
        $controller->set('modelName', $this->alias);
	}
	
	public function rubricsTemplates($controller, $params) {
		$controller->Navigation->addCrumb('Rubrics');
		$controller->set('subheader', 'Rubrics');
		$controller->set('modelName', $this->name);

		$this->recursive = -1;
		$data = $this->find('all');

		$controller->set('data', $data);
	}

	public function rubricsTemplatesView($controller, $params) {
		$controller->Navigation->addCrumb('Rubric Details');
		$controller->set('subheader', 'Rubric Details');
		//$controller->set('modelName', $this->name);

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$data = $this->find('first', array('conditions' => array($this->name . '.id' => $id), 'recursive' => 0));
		//pr($data);
		if (empty($data)) {
			$controller->redirect(array('action' => 'rubricsTemplates'));
		}

		$disableDelete = false;
		$QualityStatus = ClassRegistry::init('Quality.QualityStatus');
		if ($QualityStatus->getCreatedRubricCount($id) > 0) {
			$disableDelete = true;
		}

		$SecurityRole = ClassRegistry::init('SecurityRole');
		$roleOptions = $SecurityRole->getRubricRoleOptions();

		$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
		$gradeOptions = $InstitutionSiteClassGrade->getGradeOptions();

		$RubricsTemplateGrade = ClassRegistry::init('Quality.RubricsTemplateGrade');
		$rubricGradesList = $RubricsTemplateGrade->getSelectedGradeOptions($id);

		$rubricGradesOptions = array();
		if (!empty($rubricGradesList)) {
			foreach ($rubricGradesList as $rubricGradeid) {
				if (!empty($gradeOptions[$rubricGradeid])) {
					$rubricGradesOptions[$rubricGradeid] = $gradeOptions[$rubricGradeid];
				}
			}
			sort($rubricGradesOptions);
		}
		$weightingOptions = $this->weightingOptions;
		$controller->Session->write('RubricsTemplate.id', $id);
		
		$controller->set(compact('rubricGradesOptions', 'roleOptions', 'disableDelete', 'data', 'weightingOptions'));
	}

	public function rubricsTemplatesAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Rubric');
		$controller->set('subheader', 'Add Rubric');
//		$controller->set('modelName', $this->name);

		$this->setupRubricsTemplate($controller, $params, 'add');
	}

	public function rubricsTemplatesEdit($controller, $params) {
		$this->render = 'add';

		$controller->Navigation->addCrumb('Edit Rubric');
		$controller->set('subheader', 'Edit Rubric');
	//	$controller->set('modelName', $this->name);

		$this->setupRubricsTemplate($controller, $params, 'edit');
	}

	public function setupRubricsTemplate($controller, $params, $type) {
		$institutionId = $controller->Session->read('InstitutionId');
		$controller->set('weightingOptions', $this->weightingOptions);

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$controller->set('id', $id);

		$SecurityRole = ClassRegistry::init('SecurityRole');
		$roleOptions = $SecurityRole->getRubricRoleOptions();

		$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
		$gradeOptions = $InstitutionSiteClassGrade->getGradeOptions();

		$RubricsTemplateGrade = ClassRegistry::init('Quality.RubricsTemplateGrade');
		$rubricGradesList = $RubricsTemplateGrade->getSelectedGradeOptions($id);

		$rubricGradesOptions = array();
		if (!empty($rubricGradesList)) {
			foreach ($rubricGradesList as $rubricGradeid) {
				if (isset($gradeOptions[$rubricGradeid])) {
					$rubricGradesOptions[$rubricGradeid] = $gradeOptions[$rubricGradeid];
				}
			}
			sort($rubricGradesOptions);
		}
		
		$controller->set(compact('rubricGradesOptions', 'gradeOptions', 'roleOptions', 'type'));

		if ($controller->request->is('get')) {
			//pr($controller->request->data);
			$this->recursive = -1;
			$data = $this->findById($id);
			if (!empty($data)) {
				$controller->request->data = $data;
			} else {
				$controller->request->data[$this->name]['institution_id'] = $institutionId;
			}
		} else {//post
			//  pr($controller->request->data);//die;
			$rubricData = $controller->request->data['RubricsTemplate'];

			if (isset($controller->request->data['RubricsTemplateGrade'])) {
				$rubricGradeData = $controller->request->data['RubricsTemplateGrade'];
			}
			$validateData = ($type == 'edit') ? false : true;

			if ($this->saveAll($rubricData, array('validate' => $validateData))) {
				$rubricId = $this->id;

				if (isset($rubricGradeData)) {

					$unique = array_map('unserialize', array_unique(array_map('serialize', $rubricGradeData)));

					foreach ($unique as $key => $objGrade) {
						$conditions = array(
							'RubricsTemplateGrade.rubric_template_id' => $rubricId,
							'RubricsTemplateGrade.education_grade_id' => $objGrade['education_grade_id']
						);
						if (!$RubricsTemplateGrade->hasAny($conditions)) {
							$unique[$key]['rubric_template_id'] = $rubricId;
						} else {
							unset($unique[$key]);
						}
					}
					$RubricsTemplateGrade->saveAll($unique);
				}

				if ($type == 'add') {
					$columnData[0]['RubricsTemplateColumnInfo']['name'] = 'Good';
					$columnData[0]['RubricsTemplateColumnInfo']['weighting'] = '3';
					$columnData[0]['RubricsTemplateColumnInfo']['color'] = '00ff00';
					$columnData[0]['RubricsTemplateColumnInfo']['order'] = '1';
					$columnData[0]['RubricsTemplateColumnInfo']['rubric_template_id'] = $rubricId;
					$columnData[1]['RubricsTemplateColumnInfo']['name'] = 'Normal';
					$columnData[1]['RubricsTemplateColumnInfo']['weighting'] = '2';
					$columnData[1]['RubricsTemplateColumnInfo']['color'] = '000ff0';
					$columnData[1]['RubricsTemplateColumnInfo']['order'] = '2';
					$columnData[1]['RubricsTemplateColumnInfo']['rubric_template_id'] = $rubricId;
					$columnData[2]['RubricsTemplateColumnInfo']['name'] = 'Bad';
					$columnData[2]['RubricsTemplateColumnInfo']['weighting'] = '1';
					$columnData[2]['RubricsTemplateColumnInfo']['color'] = 'ff0000';
					$columnData[2]['RubricsTemplateColumnInfo']['order'] = '3';
					$columnData[2]['RubricsTemplateColumnInfo']['rubric_template_id'] = $rubricId;

					$RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
					$RubricsTemplateColumnInfo->saveAll($columnData);

					$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
					return $controller->redirect(array('action' => 'rubricsTemplates'));
				} else {
					$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));
					return $controller->redirect(array('action' => 'rubricsTemplatesView', $this->id));
				}
				//pr($controller->request->data);
			}
		}
	}

	public function rubricsTemplatesDelete($controller, $params) {
		if ($controller->Session->check('RubricsTemplate.id')) {
			$this->unbindModel(array('hasMany' => array('RubricsTemplateHeader', 'RubricsTemplateColumnInfo')));
			$id = $controller->Session->read('RubricsTemplate.id');

			$data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));


			$name = $data[$this->name]['name'];


			//Delete Header
			$RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
			$RubricsTemplateHeader->rubricsTemplatesHeaderDeleteAll($id);


			//Delete ColumnInfo
			$RubricsTemplateColumnInfo = ClassRegistry::init('Quality.RubricsTemplateColumnInfo');
			$RubricsTemplateColumnInfo->rubricsTemplatesCriteriaDeleteAll($id);

			//Delete Grades
			$RubricsTemplateGrade = ClassRegistry::init('Quality.RubricsTemplateGrade');
			$RubricsTemplateGrade->rubricsTemplatesGradesDeleteAll($id);


			$this->delete($id);

			$controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->Session->delete('RubricsTemplate.id');
			$controller->redirect(array('action' => 'rubricsTemplates'));
		}
	}

	//SQL Function 

	public function getRubricOptions($orderBy = 'name', $status = false) {
		$options['order'] = array('RubricsTemplate.' . $orderBy);
		$options['recursive'] = -1;

		$data = $this->find('list', $options);
//pr($data);die;
		return $data;
	}

	public function getRubricHeader($institutionSiteId, $year) {
		$options['order'] = array('RubricsTemplate.id');
		$options['group'] = array('RubricsTemplate.id');
		$options['recursive'] = -1;
		$options['joins'] = array(
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array('InstitutionSiteClass.institution_site_id =' . $institutionSiteId)
			),
			array(
				'table' => 'school_years',
				'alias' => 'SchoolYear',
				'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
			),
			array(
				'table' => 'quality_statuses',
				'alias' => 'QualityStatus',
				//  'type' => 'LEFT',
				'conditions' => array('QualityStatus.year = SchoolYear.name',
					'QualityStatus.year =' . $year,
					'RubricsTemplate.id = QualityStatus.rubric_template_id')
			),
		);
		//$options['conditions'] = array('RubricTemplate.id' => 'QualityStatus.rubric_template_id');
		$data = $this->find('list', $options);
//pr($data);//die;
		return $data;
	}

	public function getLatestRubricYear($institutionSiteId) {
		$options['order'] = array('RubricsTemplate.id', 'SchoolYear.name DESC');
		$options['recursive'] = -1;
		$options['joins'] = array(
			array(
				'table' => 'institution_site_classes',
				'alias' => 'InstitutionSiteClass',
				'conditions' => array('InstitutionSiteClass.institution_site_id =' . $institutionSiteId)
			),
			array(
				'table' => 'school_years',
				'alias' => 'SchoolYear',
				'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
			),
			array(
				'table' => 'quality_statuses',
				'alias' => 'QualityStatus',
				//  'type' => 'LEFT',
				'conditions' => array('QualityStatus.year = SchoolYear.name',
					'RubricsTemplate.id = QualityStatus.rubric_template_id')
			),
		);
		$options['fields'] = array('SchoolYear.name');
		//$options['conditions'] = array('RubricTemplate.id' => 'QualityStatus.rubric_template_id');
		$data = $this->find('first', $options);

		if (!empty($data)) {
			return $data['SchoolYear']['name'];
		} else {
			return date('Y');
		}
	}

	public function getEnabledRubricsOptions($year, $gradeId = NULL) {
		$date = date('Y-m-d', time());
		$options['order'] = array('RubricsTemplate.name');
		$options['recursive'] = -1;

		$options['joins'] = array(
			array(
				'table' => 'quality_statuses',
				'alias' => 'QualityStatus',
				'conditions' => array('RubricsTemplate.id = QualityStatus.rubric_template_id')
			)
		);

		$options['conditions'] = array('QualityStatus.year' => $year, 'QualityStatus.date_enabled <= ' => $date, 'QualityStatus.date_disabled >= ' => $date);

		if (!empty($gradeId)) {
			$rubricTable = array(
				'table' => 'rubrics_template_grades',
				'alias' => 'RubricsTemplateGrade',
				'conditions' => array(
					'RubricsTemplate.id = RubricsTemplateGrade.rubric_template_id',
					'RubricsTemplateGrade.education_grade_id = ' . $gradeId
				)
			);
			array_push($options['joins'], $rubricTable);
		}
		$data = $this->find('list', $options);
		return $data;
	}

	public function getRubric($id) {
		$data = $this->find('first', array('conditions' => array('id' => $id), 'recursive' => -1));

		return $data;
	}

	public function rubricsTemplatesAjaxAddGrade($controller, $params) {
		//  $this->render = false;
		if ($controller->request->is('ajax')) {
			$InstitutionSiteClassGrade = ClassRegistry::init('InstitutionSiteClassGrade');
			$gradeOptions = $InstitutionSiteClassGrade->getGradeOptions();

			$controller->set('index', $params->query['index'] + 1);
			$controller->set('gradeOptions', $gradeOptions);
		}
		// echo 'return data';
	}

	public function getInstitutionQAReportHeader($institutionSiteId, $year = NULL, $includeArea = NULL) {
		if ($includeArea === 'yes') {
			$header = array(array('Year'), array('Area Name'), array('Area Code'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
		} else {
			$header = array(array('Year'), array('Institution Site Name'), array('Institution Site Code'), array('Class'), array('Grade'));
		}

		if (!empty($institutionSiteId)) {
			$RubricsTemplateHeader = ClassRegistry::init('Quality.RubricsTemplateHeader');
			if (empty($year)) {
				$rubricYear = $this->getLatestRubricYear($institutionSiteId);
			} else {
				$rubricYear = $year;
			}

			//   pr($rubricYear);
			$rubricOptions = $this->getRubricHeader($institutionSiteId, $rubricYear);
			// pr($rubricOptions);
			// die;
			if (!empty($rubricOptions)) {
				foreach ($rubricOptions as $key => $item) {
					$headerOptions = $RubricsTemplateHeader->getRubricHeaders($key, 'all');
					//pr($headerOptions);

					if (!empty($headerOptions)) {
						$tempArr = array();
						$tempArr[][] = 'Rubric Name';
						foreach ($headerOptions AS $obj) {
							$tempArr[][] = $obj['RubricsTemplateHeader']['title'];
						}
						$tempArr[][] = 'Total Weighting(%)';
						$tempArr[][] = 'Pass/Fail';
						$header = array_merge($header, $tempArr);
					}
				}

				$headerOptions = array();
				$headerOptions[][] = 'Grand Total Weighting(%)';
				//  $headerOptions[] = 'Pass/Fail';
				$header = array_merge($header, $headerOptions);
			}
		}
		// pr($header); die;
		return $header;
	}
}
