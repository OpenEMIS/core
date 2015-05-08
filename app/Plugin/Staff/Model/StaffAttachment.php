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

class StaffAttachment extends StaffAppModel {
	public $actsAs = array(
		'DatePicker' => array('date_on_file')
	);
	public $belongsTo = array(
		'Staff.Staff',
		'ModifiedUser' => array('foreignKey' => 'modified_user_id', 'className' => 'SecurityUser'),
		'CreatedUser' => array('foreignKey' => 'created_user_id', 'className' => 'SecurityUser')
	);
	public $virtualFields = array(
		'blobsize' => "OCTET_LENGTH(file_content)"
	);
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a File name'
			)
		)
	);

	public function beforeAction() {
		$this->controller->FileUploader->fileModel = 'StaffAttachment';
		$this->controller->FileUploader->additionalFileType();

		$this->controller->viewVars['contentHeader'] = 'Staff Attachments';

		if ($this->action == 'view') {

			if (isset($this->request->params['pass'][1])) {
				$this->Navigation->addCrumb('Attachment Details');
		
				$this->fields['file_name']['type'] = 'download';
				$this->fields['file_name']['attr']['url'] = array('action' => 'StaffAttachment', 'download', $this->request->params['pass'][1]);

				$this->fields['file_content']['visible'] = false;
				$this->fields['visible']['visible'] = false;
				$this->fields['staff_id']['visible'] = false;
				$this->fields['created']['visible'] = true;
			} else {
				$this->controller->redirect(array('action' => 'StaffAttachment'));
			}
		} elseif ($this->action == 'edit') {

			$this->Navigation->addCrumb('Edit Attachments');

			$this->fields['id']['type'] = 'hidden';
			$this->fields['file_name']['visible'] = false;
			$this->fields['file_content']['visible'] = false;
			$this->fields['visible']['visible'] = false;
			$this->fields['staff_id']['visible'] = false;
			$this->fields['created']['visible'] = false;

		} elseif ($this->action == 'add') {

			$this->fields['file_name']['visible'] = false;

			$this->fields['file_content']['visible'] = false;
			$this->fields['file']['visible'] = true;
			$this->fields['file']['type'] = 'element';
			$this->fields['file']['element'] = 'templates/file_upload';
			$this->fields['file']['order'] = $this->fields['date_on_file']['order'] - 1;
			
			$this->fields['visible']['visible'] = false;
			$this->fields['staff_id']['visible'] = false;
			$this->fields['created']['visible'] = false;

			$this->fields['MAX_FILE_SIZE']['visible'] = true;
			$this->fields['MAX_FILE_SIZE']['type'] = 'hidden';
			$this->fields['MAX_FILE_SIZE']['name'] = 'MAX_FILE_SIZE';
			$this->fields['MAX_FILE_SIZE']['value'] = (2*1024*1024);
			$this->fields['MAX_FILE_SIZE']['order'] = count($this->fields);

			$this->ControllerAction->formType = 'file';
		}
	}

	public function index() {
		$this->Navigation->addCrumb('Attachments');
		$header = __('Attachments');
		$fields = array('id', 'name', 'description', 'file_name', 'file_content', 'date_on_file', 'created');
		$data = $this->findAllByStaffIdAndVisible($this->Session->read('Staff.id'), 1, $fields, array(), null, null, -1);

		$this->fields['name']['hyperlink'] = true;
		$this->fields['file_content']['visible'] = false;
		$this->fields['visible']['visible'] = false;
		$this->fields['staff_id']['visible'] = false;
		$this->fields['file_content']['visible'] = false;
		$this->fields['created']['visible'] = true;
		$this->fields['created']['labelKey'] = $this->alias;
		
		$this->controller->set(compact('data', 'header'));
	}

	public function add() {
		$this->render = false;
		$this->Navigation->addCrumb('Add Attachment');
		
		if ($this->request->is(array('post', 'put'))) {
			$this->set($this->request->data);
			if ($this->validates()) {
				$postData = $this->request->data[$this->alias];
				$this->controller->FileUploader->additionData = array('staff_id' => $this->Session->read('Staff.id'), 'name' => $postData['name'], 'description' => $postData['description'], 'date_on_file' => $postData['date_on_file']);
				$this->controller->FileUploader->uploadFile();
				if ($this->controller->FileUploader->success) {
					return $this->controller->redirect(array('action' => 'StaffAttachment'));
				}
			}
		}

		$this->controller->set(compact('header', 'params'));
	}

	public function download($id) {
		$this->controller->FileUploader->downloadFile($id);
	}
}
