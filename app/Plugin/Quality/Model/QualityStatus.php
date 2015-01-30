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

class QualityStatus extends QualityAppModel {

    //public $useTable = 'rubrics';
    public $actsAs = array('ControllerAction', 'DatePicker' => array('date_enabled', 'date_disabled'));
    public $belongsTo = array(
        //'Student',
        //'RubricsTemplateHeader',
        'ModifiedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'modified_user_id'
        ),
        'CreatedUser' => array(
            'className' => 'SecurityUser',
            'foreignKey' => 'created_user_id'
        )
    );
    //  public $hasMany = array('RubricsTemplateColumnInfo');

    public $validate = array(
        'rubric_template_id' => array(
            'ruleRequired' => array(
                'rule' => 'checkDropdownData',
                //  'required' => true,
                'message' => 'Please select a valid Name.'
            )
        ),
        'date_enabled' => array(
            'ruleNotLater' => array(
                'rule' => array('compareDate', 'date_disabled'),
                'message' => 'Date Enabled cannot be later than Date Disabled'
            ),
        )
    );
    //public $statusOptions = array('Date Disabled', 'Date Enabled');

	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'name'),
                array('field' => 'year'),
                array('field' => 'date_enabled'),
                array('field' => 'date_disabled'),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
    public function checkDropdownData($check) {
        $value = array_values($check);
        $value = $value[0];

        return !empty($value);
    }

    public function compareDate($field = array(), $compareField = null) {
        $startDate = new DateTime(current($field));
        $endDate = new DateTime($this->data[$this->name][$compareField]);
        return $endDate > $startDate;
    }

    public function status($controller, $params) {
        $institutionId = $controller->Session->read('InstitutionId');

        $controller->Navigation->addCrumb('Status');
        $header = __('Status');
      
        $this->recursive = -1;
        $data = $this->getQualityStatuses(); //$this->find('all');

		$statusOptions = $controller->Option->get('dateStatusOptions');

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getRubricOptions();

		$controller->set(compact('rubricOptions', 'header', 'data', 'statusOptions'));
    }

    public function statusView($controller, $params) {
        $controller->Navigation->addCrumb('Status Details');
        $header =  __('Status Details');
      
        $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
        $data = $this->find('first', array('conditions' => array($this->name . '.id' => $id)));

        if (empty($data)) {
            $controller->redirect(array('action' => 'status'));
        }

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $RubricsTemplate->recursive = -1;
        $rubricTemplateInfo = $RubricsTemplate->findById($data[$this->name]['rubric_template_id']);

		$data['QualityStatus']['name'] = $rubricTemplateInfo['RubricsTemplate']['name'];

        $AcademicPeriod = ClassRegistry::init('AcademicPeriod');
        $academicPeriodId = $AcademicPeriod->getAcademicPeriodId($data[$this->name]['year']);

        $disableDelete = false;
        $QualityInstitutionRubric = ClassRegistry::init('Quality.QualityInstitutionRubric');
        if ($QualityInstitutionRubric->getAssignedInstitutionRubricCount($academicPeriodId, $id) > 0) {
            $disableDelete = true;
        }

		$fields = $this->getDisplayFields($controller);
		
        $rubricName = $rubricTemplateInfo['RubricsTemplate']['name'];
        $controller->Session->write('QualityStatus.id', $id);
       /* $controller->set('rubricName', $rubricName);*/
		$statusOptions = $controller->Option->get('dateStatusOptions');
		$controller->set(compact('id', 'header', 'data', 'fields', 'disableDelete', 'statusOptions'));
    }

    public function statusAdd($controller, $params) {
        $controller->Navigation->addCrumb('Add Status');
        $controller->set('header', __('Add Status'));
        $controller->set('displayType', 'add');
        $controller->set('selectedAcademicPeriod', date("Y"));

        $this->_setupStatusForm($controller, $params, 'add');
    }

    public function statusEdit($controller, $params) {
        $controller->Navigation->addCrumb('Edit Status');
        $controller->set('header', __('Edit Status'));
        $controller->set('selectedAcademicPeriod', date("Y"));
        $controller->set('displayType', 'edit');
        $this->_setupStatusForm($controller, $params, 'edit');
        $this->render = 'add';
    }

    private function _setupStatusForm($controller, $params, $type) {
		$statusOptions = $controller->Option->get('dateStatusOptions');
		$displayType = $type;

        $RubricsTemplate = ClassRegistry::init('Quality.RubricsTemplate');
        $rubricOptions = $RubricsTemplate->getRubricOptions();

        $AcademicPeriod = ClassRegistry::init('AcademicPeriod');
        $academicPeriodOptions = $AcademicPeriod->getAcademicPeriodListValues();
        
		$controller->set(compact('rubricOptions', 'academicPeriodOptions', 'displayType', 'statusOptions'));
        if ($controller->request->is('get')) {

            $id = empty($params['pass'][0]) ? 0 : $params['pass'][0];

            $this->recursive = -1;
            $data = $this->findById($id);

            if (!empty($data)) {
                $controller->request->data = $data;
                $controller->set('selectedAcademicPeriod', $data[$this->name]['year']);
            } /*else {
               // $controller->request->data['QualityStatus']['date_disabled'] = date('d-m-Y', time() + 86400);
                //$controller->request->data[$this->name]['institution_id'] = $institutionId;
            }*/
        } else {
            // $controller->request->data[$this->name]['student_id'] = $controller->studentId;
            // pr($controller->request->data);
            // die;
            $proceedToSave = true;
            if ($type == 'add') {
                $conditions = array(
                    'QualityStatus.rubric_template_id' => $controller->request->data['QualityStatus']['rubric_template_id'],
                    'QualityStatus.year' => $controller->request->data['QualityStatus']['year']
                );
                if ($this->hasAny($conditions)) {
                    $proceedToSave = false;
                    $controller->Message->alert('general.exists');
                }
            }

            if ($proceedToSave) {
                if ($this->save($controller->request->data)) {
                    $controller->Message->alert('general.add.success');
                    return $controller->redirect(array('action' => 'status'));
                }
            }
        }
    }

    public function statusDelete($controller, $params) {
		if ($controller->Session->check('QualityStatus.id')) {
			$id = $controller->Session->read('QualityStatus.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('QualityStatus.id');
			$controller->redirect(array('action' => 'status'));
		}
	}

	//SQL Function 
    public function getQualityStatuses() {
        $options['recursive'] = -1;
        $options['joins'] = array(
            array(
                'table' => 'rubrics_templates',
                'alias' => 'RubricsTemplate',
                'conditions' => array('RubricsTemplate.id = QualityStatus.rubric_template_id')
            )
        );
        $options['order'] = array('QualityStatus.year DESC', 'RubricsTemplate.name');
        $options['fields'] = array('QualityStatus.*', 'RubricsTemplate.*');
        $data = $this->find('all', $options);

        return $data;
    }

    public function getRubricStatus($year, $rubricId) {
        $date = date('Y-m-d', time());
        
        $conditions = array(
            'QualityStatus.rubric_template_id' => $rubricId,
            'QualityStatus.year' => $year,
            'QualityStatus.date_enabled <= ' => $date,
            'QualityStatus.date_disabled >= ' => $date
        );
        

        return $this->hasAny($conditions);
    }

    public function getCreatedRubricCount($rubricId) {
        $options['conditions'] = array('rubric_template_id' => $rubricId);
        $options['fields'] = array('COUNT(id) as Total');
        $options['recursive'] = -1;
        $data = $this->find('first', $options);

        return $data[0]['Total'];
    }

}
