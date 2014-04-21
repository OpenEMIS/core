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

// App::uses('StudentsAppModel', 'Model');

class StudentAttachment extends StudentsAppModel {

	public $actsAs = array('ControllerAction');
	public $belongsTo = array(
		'Student',
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

	public function beforeAction($controller, $action) {
		$controller->set('model', $this->alias);
		$controller->FileUploader->fileModel = 'StudentAttachment';
		$controller->FileUploader->additionalFileType();
	}

	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'name'),
                array('field' => 'description'),
                array('field' => 'file_name', 'type' => 'file', 'url'=> array('action' => 'attachmentsDownload')),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public function attachments($controller, $params) {
		$controller->Navigation->addCrumb('Attachments');
		$id = $controller->studentId;
		$data = $controller->FileUploader->getList(array('student_id' => $id));
		$arrFileExtensions = $controller->Utility->getFileExtensionList();

		$controller->set(compact('data', 'arrFileExtensions'));
		$this->render = false;
		$controller->render('../Elements/attachment/index');
	}

	public function attachmentsEdit($controller, $params)  {
		$this->render = false;
		$controller->Navigation->addCrumb('Edit Attachments');
		$header = __('Edit Attachments');
		$id = isset($params['pass'][0])?$params['pass'][0]:0 ;

		if ($controller->request->is(array('post', 'put'))) { // save
			if($this->save($controller->request->data)){
				$controller->Message->alert('general.add.success');
				$controller->redirect(array('action' => 'attachments'));
			}
		}
		else{
			$data = $this->findById($id);//pr($data);
			$controller->request->data = $data;
		}
		$controller->set(compact('header', 'data', 'id'));
		$controller->render('/Elements/attachment/edit');
	}

	public function attachmentsAdd($controller, $params) {
		$this->render = false;
		$controller->Navigation->addCrumb('Add Attachment');
		$header = __('Add Attachment');
		//$controller->set('params', $params);

		if ($controller->request->is(array('post', 'put'))) {
			if (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Skip')) {
				$controller->Navigation->skipWizardLink($controller->request->action);
			} else if (isset($controller->request->data['submit']) && $controller->request->data['submit'] == __('Previous')) {
				$controller->Navigation->previousWizardLink($controller->request->action);
			} else {
				$controller->Navigation->validateModel($controller->request->action, 'StudentAttachment');
			}
			/*if (!empty($_FILES)) {
				$errors = $this->FileAttachment->saveAll($controller->request->data, $_FILES, $id);
				if (sizeof($errors) == 0) {
					$controller->Navigation->updateWizard($controller->request->action, null);
					$controller->Utility->alert(__('Files have been saved successfully.'));
					$controller->redirect(array('action' => 'attachments'));
				} else {
					$controller->Utility->alert(__('Some errors have been encountered while saving files.'), array('type' => 'error'));
				}
			} else {
				$controller->Utility->alert(__('Some errors have been encountered while saving files.'), array('type' => 'error'));
			}*/
			
			$this->set( $controller->request->data );
			if ($this->validates()) {
				$postData = $controller->request->data[$this->alias];
				$controller->FileUploader->additionData = array('student_id' => $controller->studentId, 'name' => $postData['name'], 'description' => $postData['description']);
				$controller->FileUploader->uploadFile();
				if ($controller->FileUploader->success) {
					$controller->Navigation->updateWizard($controller->request->action, null);
					return $controller->redirect(array('action' => 'attachments'));
				} 
			}
		}
		
		$controller->set(compact('header', 'params'));
		$controller->render('/Elements/attachment/add');
	}
	
	public function attachmentsView($controller, $params) {
		$this->render = false;
		$controller->Navigation->addCrumb('Attachment Details');
		$id = isset($params['pass'][0])?$params['pass'][0]:0 ;
		
		$data = $this->findById($id);
		if(empty($data)){
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action'=> 'attachments'));
		}
		
		$controller->Session->write('StudentAttachmentId', $id);
		$fields = $this->getDisplayFields($controller);
        $controller->set(compact('data', 'fields'));
		$controller->render('/Elements/attachment/view');
	}

	public function attachmentsDelete($controller, $params) {
		$controller->autoRender = false;
		if ($controller->Session->check('StudentId') && $controller->Session->check('StudentAttachmentId')) {
            $id = $controller->Session->read('StudentAttachmentId');

            if($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
            $controller->Session->delete('StudentAttachmentId');
            return $controller->redirect(array('action' => 'attachments'));
        }
		/*
		if ($this->request->is('post')) {
			$result = array('alertOpt' => array());
			$controller->Utility->setAjaxResult('alert', $result);
			$id = $this->params->data['id'];

			if ($this->FileAttachment->delete($id)) {
				$result['alertOpt']['text'] = __('File is deleted successfully.');
			} else {
				$result['alertType'] = $controller->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = __('Error occurred while deleting file.');
			}

			return json_encode($result);
		}*/
	}

	public function attachmentsDownload($controller, $params) {
		$id = $params['pass'][0];
		$controller->FileUploader->downloadFile($id);
	}

}

?>
