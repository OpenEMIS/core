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

class StaffLeave extends StaffAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('date_from', 'date_to'));
	public $belongsTo = array(
		'Staff.StaffLeaveType',
		'Staff.LeaveStatus',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'date_from' => array(
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'date_to'),
				'message' => 'Date From cannot be later than Date To'
			),
			'ruleNoOverlap' => array(
				'rule' => array('checkOverlapDates'),
				'message' => 'Leave have been selected for this date. Please choose a different date'
			)
		)
	);
	
	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate > $startDate;
	}
	
	public function checkOverlapDates($field = array()) {
		$data = $this->data[$this->name];
		$startDate = $data['date_from'];
		$endDate = $data['date_to'];
		
		$conditions = array(
			'OR' => array(
				array('date_from <=' => $startDate, 'date_to >=' => $startDate),
				array('date_from <=' => $endDate, 'date_to >=' => $endDate),
				array('date_from >=' => $startDate, 'date_from <=' => $endDate)
			),
			'StaffLeave.staff_id' => $data['staff_id']
		);
		
		if(isset($data['id'])) {
			$conditions['StaffLeave.id <>'] = $data['id'];
		}
		$check = $this->find('all', array('recursive' => -1, 'conditions' => $conditions));
		return empty($check);
	}
	
	public function beforeAction($controller, $action) {
		$controller->set('model', $this->alias);
		$controller->FileUploader->fileVar = 'files';
		$controller->FileUploader->fileModel = 'StaffLeaveAttachment';
		$controller->FileUploader->allowEmptyUpload = true;
		$controller->FileUploader->additionalFileType();
	}
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'StaffLeaveType', 'labelKey' => 'general.type'),
				array('field' => 'name', 'model' => 'LeaveStatus', 'labelKey' => 'general.status'),
				array('field' => 'date_from'),
				array('field' => 'date_to'),
				array('field' => 'comments'),
				array('field' => 'number_of_days'),
				array('field' => 'file_name', 'model' => 'StaffLeaveAttachment', 'labelKey' => 'general.attachments', 'multi_records' => true, 'type' => 'files', 'url' => array('action' => 'leavesAttachmentsDownload')),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function leaves($controller, $params) {
		$controller->Navigation->addCrumb('Leaves');
		$header = __('Leaves');
		$staffId = $controller->Session->read('Staff.id');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser','CreatedUser')));
		$data = $this->findAllByStaffId($staffId, array('StaffLeave.*', 'StaffLeaveType.name', 'LeaveStatus.name'), array('StaffLeave.date_from'));
		$controller->set(compact('header','data'));
	}

	public function leavesAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Leaves');
		$header = __('Add Leaves');
		$typeOptions = $this->StaffLeaveType->getList(1);
		$statusOptions = $this->LeaveStatus->getList(1);

		if ($controller->request->is('post')) {
			
			$postData = $controller->request->data[$this->alias];
			$postData['staff_id'] = $controller->Session->read('Staff.id');
			unset($postData['files']);
			$postFileData = $controller->request->data[$this->alias]['files'];
			//pr($postFileData);
			//pr($postData);die;
			$this->set($postData);
			if ($this->validates()) {
				if($this->save($postData)){
					$this->create();
					$id = $this->getInsertID();
					
					//if(!empty($postFileData['tmp_name'])){ 
						$controller->FileUploader->additionData = array('staff_leave_id' => $id);
						$controller->FileUploader->uploadFile(NULL, $postFileData);
						if ($controller->FileUploader->success) {
							$controller->Message->alert('general.add.success');
						}
					/*}
					else{
						$controller->Message->alert('general.add.success');
					}*/
					return $controller->redirect(array('action' => 'leaves'));
					
				}
			}
		}
		
		$controller->set(compact('header', 'statusOptions', 'typeOptions'));
	}

	public function leavesView($controller, $params) {
		$controller->Navigation->addCrumb('Leaves Details');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$header = __('Leaves Details');
		
		$this->recursive = 1;
		$data = $this->findById($id);
		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'leaves'));
		}
		
		$attachments = $controller->FileUploader->getList(array('conditions' => array('StaffLeaveAttachment.staff_leave_id'=>$id)));
		$data['multi_records'] = $attachments;

		$controller->Session->write('StaffLeaveId', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'header', 'fields', 'id'));
	}

	public function leavesEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit Leaves');
		$header = __('Edit Leaves');
		
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;
		$typeOptions = $this->StaffLeaveType->getList(1);
		$statusOptions = $this->LeaveStatus->getList(1);
		
		$data = $this->findById($id);
		$attachments = $controller->FileUploader->getList(array('conditions' => array('StaffLeaveAttachment.staff_leave_id'=>$id)));

		if ($controller->request->is('post') || $controller->request->is('put')) {
			$postData = $controller->request->data[$this->alias];
			//pr($postData);die;
			$postData['staff_id'] = $controller->Session->read('Staff.id');
			unset($postData['files']);
			$postFileData = $controller->request->data[$this->alias]['files'];
	
			$this->set($postData);
			if ($this->validates()) {
				if($this->save($postData)){
					//if(!empty($postFileData['tmp_name'])){ 
						$controller->FileUploader->additionData = array('staff_leave_id' => $id);
						$controller->FileUploader->uploadFile(NULL, $postFileData);
						if ($controller->FileUploader->success) {
							$controller->Message->alert('general.add.success');
							return $controller->redirect(array('action' => 'leavesView', $id));
						}
					//}
					//else{
					//	$controller->Message->alert('general.add.success');
					//}
					//return $controller->redirect(array('action' => 'leavesView', $id));
				}
			}
		}
		else{
			if (empty($data)) {
				return $controller->redirect(array('action' => 'leaves'));
			}
			$controller->request->data = $data;
		}
		
		$controller->set(compact('header', 'statusOptions', 'typeOptions', 'attachments'));
	}
	
	public function leavesDelete($controller, $params) {
		if ($controller->Session->check('StaffLeaveId')) {
			$id = $controller->Session->read('StaffLeaveId');

			if ($this->delete($id)) {
				$StaffLeaveAttachment = ClassRegistry::init('StaffLeaveAttachment');
				$StaffLeaveAttachment->deleteAll(array('StaffLeaveAttachment.staff_leave_id' => $id)); 
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}

			$controller->Session->delete('StaffLeaveId');

			return $controller->redirect(array('action' => 'leaves'));
		}
	}

	public function leavesAjaxAttachmentsLeaveDelete($controller, $params) {
		$this->render = false;
		if ($controller->request->is('post')) {
			$result = array('alertOpt' => array());
			$controller->Utility->setAjaxResult('alert', $result);
			$id = $params->data['id'];

			$StaffLeaveAttachment = ClassRegistry::init('StaffLeaveAttachment');
			if ($StaffLeaveAttachment->delete($id)) {
				$msgData  = $controller->Message->get('FileUplaod.success.delete');
				$result['alertOpt']['text'] = $msgData['msg'];// __('File is deleted successfully.');
			} else {
				$msgData  = $controller->Message->get('FileUplaod.error.delete');
				$result['alertType'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = $msgData;//__('Error occurred while deleting file.');
			}
			
			return json_encode($result);
		}
	}

	public function leavesAttachmentsDownload($controller, $params) {
		$id = $params['pass'][0];
		$this->render = false;
		$controller->FileUploader->downloadFile($id);
	}
	
	public function leavesAjaxAddField($controller, $params) {
		$this->render =false;
		
		$fileId = $controller->request->data['size'];
		$multiple = true;
		$controller->set(compact('fileId', 'multiple'));
		$controller->render('/Elements/templates/file_upload_field');
	}
}
