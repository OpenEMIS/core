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

class RubricsTemplateHeader extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction', 'Reorder' => array('parentKey' => 'rubric_template_id'));
    public $belongsTo = array(
        //'Student',
        'RubricsTemplate' => array(
            'foreignKey' => 'rubric_template_id'
        ),
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    //public $hasMany = array('RubricsTemplateColumnInfo');

    public $validate = array(
        'title' => array(
            'ruleRequired' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'message' => 'Please enter a valid Title.'
            )
        )/* ,
              'pass_mark' => array(
              'ruleRequired' => array(
              'rule' => 'notEmpty',
              'required' => true,
              'message' => 'Please enter a valid Pass Mark.'
              )
              ) */
    );
	
	public $_action = 'rubricsTemplatesHeader';

    public function beforeAction($controller, $action) {
        if ($action != 'rubricsTemplatesHeaderDelete') {
            $id = empty($controller->params['pass'][0]) ? '' : $controller->params['pass'][0];

            if (empty($id)) {
                return $controller->redirect(array('action' => 'rubricsTemplates'));
            }

            $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
            $rubricTemplateData = $RubricsTemplate->getRubric($id);
            $rubricName = trim($rubricTemplateData['RubricsTemplate']['name']);

            $controller->set('modelName', $this->name);
            $controller->Navigation->addCrumb('Rubrics', array('controller' => 'Quality', 'action' => 'rubricsTemplates', 'plugin' => 'Quality'));

            if ($action != 'rubricsTemplatesHeader') {
                $controller->Navigation->addCrumb($rubricName, array('controller' => 'Quality', 'action' => 'rubricsTemplatesHeader', $id, 'plugin' => 'Quality'));
            } else {
                $controller->Navigation->addCrumb($rubricName);
                $controller->set('subheader', $rubricName);
            }
        }
		
		$controller->set('_action', $this->_action);
    }
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'title', 'labelKey' => 'Quality.section_header'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
    public function rubricsTemplatesHeader($controller, $params) {
        $id = empty($params['pass'][0]) ? '' : $params['pass'][0];


        $data = $this->getRubricHeaders($id, 'all');

        if (empty($data)) {
            //return $controller->redirect(array('action' => 'rubricsTemplates'));
        }

        $controller->Session->write('RubricsHeader.order', count($data));
		$controller->set(compact('id', 'data'));
    }

    public function rubricsTemplatesHeaderAdd($controller, $params) {
        $rubric_template_id = empty($params['pass'][0]) ? 0 : $params['pass'][0];

        /* $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
          $rubricTemplateData = $RubricsTemplate->getRubric($rubric_template_id);
          $rubricName = trim($rubricTemplateData['RubricsTemplate']['name']);
          $controller->Navigation->addCrumb($rubricName,array('controller' => 'Quality', 'action' => 'rubricsTemplatesHeader',$rubric_template_id, 'plugin' => 'Quality'));
         */
        $controller->Navigation->addCrumb('Add Section Header');
        $controller->set('subheader', 'Add Section Header');

        $this->_setupForm($controller, $params, 'add');
    }

    public function rubricsTemplatesHeaderEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Headers');
        $controller->set('subheader', 'Edit Rubric Headers');

        $this->_setupForm($controller, $params, 'edit');

        $this->render = 'add';
    }

    public function rubricsTemplatesHeaderView($controller, $params) {
        $controller->Navigation->addCrumb('Details');
        $controller->set('subheader', 'Details');

        if (count($params['pass']) < 2) {
            return $controller->redirect(array('action' => 'rubricsTemplates'));
        }
        $rubric_template_id = empty($params['pass'][0]) ? '' : $params['pass'][0];
        $id = empty($params['pass'][1]) ? '' : $params['pass'][1];

        $disableDelete = false;
        $QualityStatus = ClassRegistry::init('Quality.QualityStatus');
        if ($QualityStatus->getCreatedRubricCount($rubric_template_id) > 0) {
            $disableDelete = true;
        }

        $controller->Session->write('RubricsTemplateHeader.id', $id);
        $controller->Session->write('RubricsTemplateHeader.rubric_template_id', $rubric_template_id);
        $data = $this->findById($id);
        //pr($data);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('disableDelete', 'data', 'rubric_template_id', 'fields'));
    }

    private function _setupForm($controller, $params, $type) {
        $rubric_template_id = empty($params['pass'][0]) ? '' : $params['pass'][0];
        $controller->set('rubric_template_id', $rubric_template_id);

        $id = empty($params['pass'][1]) ? '' : $params['pass'][1];
        $controller->set('id', $id);
		
		if ($controller->request->is('post') || $controller->request->is('put')) {
			
			if ($this->save($controller->request->data)) {
                $controller->Message->alert('general.add.success');
                return $controller->redirect(array('action' => 'rubricsTemplatesHeader', $rubric_template_id));
            }
        } else {
            $this->recursive = -1;
            $data = $this->findById($id);
			if ($type == 'add') {
                $data[$this->name]['order'] = $controller->Session->read('RubricsHeader.order') + 1;
            }
            if (!empty($data)) {
                $controller->request->data = $data;
            }
        }
    }

    public function rubricsTemplatesHeaderDelete($controller, $params) {
        if ($controller->Session->check('RubricsTemplateHeader.id')) {
            $id = $controller->Session->read('RubricsTemplateHeader.id');
            $rubric_template_id = $controller->Session->read('RubricsTemplateHeader.rubric_template_id');
           if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
            $controller->Session->delete('RubricsTemplate.id');
            $controller->redirect(array('action' => 'rubricsTemplatesHeader', $rubric_template_id));
        }
    }

    public function rubricsTemplatesHeaderDeleteAll($id) {
        $this->unbindModel(array('belongsTo' => array('RubricsTemplate')));
        $data = $this->find('list', array('conditions' => array('rubric_template_id' => $id), 'fields' => array('id', 'id')));
        //
        // $listOfHeaderId = implode(',', $data);
        // pr($listOfHeaderId);
        //Delete Sub Header
        if (!empty($data)) {
            $RubricsTemplateSubheader = ClassRegistry::init('Quality.RubricsTemplateSubheader');
            $RubricsTemplateSubheader->rubricsTemplatesSubheaderDeleteAll($data);

            foreach ($data as $obj) {
                // pr($obj);
               $this->delete($obj);
            }
        }
    }

    //SQL Function

    public function getRubricHeaders($rubricTemplateId, $type = 'list') {
        $data = $this->find($type, array('conditions' => array('rubric_template_id' => $rubricTemplateId), 'recursive' => -1, 'order' => array('rubric_template_id', 'order')));

        return $data;
    }

    public function getRubricTemplate($id) {
        $data = $this->find('first', array('conditions' => array('id' => $id), 'recursive' => -1));
        return $data;
    }

    public function getAllQuestionsStatus($institutionSiteId, $rubricId, $qualityInstitutionRubricsid) {
        $data = $this->find('all', array(
            'recursive' => -1,
            'conditions' => array('RubricsTemplateHeader.rubric_template_id' => $rubricId),
            'group' => array('RubricsTemplateHeader.id'),
            'fields' => array('COUNT(*) as total', 'RubricsTemplateHeader.id'),
            'joins' => array(
                array(
                    'alias' => 'RubricsTemplateSubheader',
                    'table' => 'rubrics_template_subheaders',
                    //'type' => 'RIGHT',
                    'conditions' => array(
                        'RubricsTemplateSubheader.rubric_template_header_id = RubricsTemplateHeader.id'
                    )
                ),
                array(
                    'alias' => 'RubricsTemplateItem',
                    'table' => 'rubrics_template_items',
                    //'type' => 'RIGHT',
                    'conditions' => array(
                        'RubricsTemplateItem.rubric_template_subheader_id = RubricsTemplateSubheader.id'
                    )
                ),
            )
        ));

        $QualityInstitutionRubricsAnswer = ClassRegistry::init('Quality.QualityInstitutionRubricsAnswer');
        $currentCompletedData = $QualityInstitutionRubricsAnswer->getTotalCount($institutionSiteId, $rubricId, $qualityInstitutionRubricsid);

        $statusData = array();
        foreach ($data AS $obj) {
            $statusData[$obj['RubricsTemplateHeader']['id']] = 'Not Started';

            foreach ($currentCompletedData as $completedObj) {
                if ($obj['RubricsTemplateHeader']['id'] == $completedObj['QualityInstitutionRubricsAnswer']['rubric_template_header_id']) {
                    if ($obj[0]['total'] > $completedObj[0]['total']) {
                        $statusData[$obj['RubricsTemplateHeader']['id']] = 'Not Completed';
                    } else if ($obj[0]['total'] <= $completedObj[0]['total']) {
                        $statusData[$obj['RubricsTemplateHeader']['id']] = 'Completed';
                    }

                    break;
                }
            }
        }

        return $statusData;
    }

	public function rubricsTemplatesHeaderReorder($controller, $params) {
		$id = isset($controller->params->pass[0]) ? $controller->params->pass[0] : null;
		
		$conditions = array($id);
		$model = $this->alias;
		$header = 'Reorder Section Headers';
        $data = $this->getRubricHeaders($id, 'all');
		$controller->Navigation->addCrumb('Reorder Section Headers');
		$controller->set(compact('data', 'model', 'header', 'conditions'));
	}
	
	public function rubricsTemplatesHeaderMove($controller, $params) {
		$rubricTemplateId = isset($controller->params->pass[0]) ? $controller->params->pass[0] : null;
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$data = $controller->request->data;
			
			$conditions = array('rubric_template_id' => $rubricTemplateId);
			$this->moveOrder($data, $conditions);
			$redirect = array('plugin' => 'Quality', 'action' => $this->_action.'Reorder', $rubricTemplateId);
			return $controller->redirect($redirect);
		}
	}
}
