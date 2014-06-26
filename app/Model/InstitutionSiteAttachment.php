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

class InstitutionSiteAttachment extends AppModel {

	public $actsAs = array('ControllerAction');
	public $belongsTo = array(
		'InstitutionSite',
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
		$WizardMode = false;
		$model = $this->alias;
		$controller->set(compact('model', 'WizardMode'));
		$controller->FileUploader->fileModel = 'InstitutionSiteAttachment';
		$controller->FileUploader->additionalFileType();
	}

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'type' => 'file', 'url' => array('action' => 'attachmentsDownload')),
				array('field' => 'description', 'type' => 'textarea'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function attachments($controller, $params) {
		$this->render = false;
		$controller->Navigation->addCrumb('Attachments');
		$header = __('Attachments');
		$data = $this->findAllByInstitutionSiteIdAndVisible($controller->institutionSiteId, 1, array('id', 'name', 'description', 'file_name', 'file_content', 'created'), array(), null, null, -1);
		$arrFileExtensions = $controller->Utility->getFileExtensionList();
		$controller->set(compact('data', 'arrFileExtensions', 'header'));
		$controller->render('/Elements/attachment/index');
	}

	public function attachmentsAdd($controller, $params) {
		$this->render = false;
		$controller->Navigation->addCrumb('Add Attachment');
		$header = __('Add Attachment');
		if ($controller->request->is(array('post', 'put'))) {
			$this->set($controller->request->data);
			if ($this->validates()) {
				$postData = $controller->request->data[$this->alias];
				$controller->FileUploader->additionData = array('institution_site_id' => $controller->institutionSiteId, 'name' => $postData['name'], 'description' => $postData['description']);
				$controller->FileUploader->uploadFile();
				if ($controller->FileUploader->success) {
					return $controller->redirect(array('action' => 'attachments'));
				}
			}
		}
		$controller->set(compact('header', 'params'));
		$controller->render('/Elements/attachment/add');
	}

	public function attachmentsEdit($controller, $params) {
		$this->render = false;
		$controller->Navigation->addCrumb('Edit Attachments');
		$header = __('Edit Attachments');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;

		if ($controller->request->is(array('post', 'put'))) { // save
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				$controller->redirect(array('action' => 'attachmentsView', $id));
			}
		} else {
			$data = $this->findById($id); //pr($data);
			$controller->request->data = $data;
		}
		$controller->set(compact('header', 'data', 'id'));
		$controller->render('/Elements/attachment/edit');
	}

	public function attachmentsView($controller, $params) {
		$this->render = false;
		$controller->Navigation->addCrumb('Attachment Details');
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0;

		$data = $this->findById($id);
		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'attachments'));
		}

		$controller->Session->write('InstitutionSiteAttachmentId', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'fields'));
		$controller->render('/Elements/attachment/view');
	}

	public function attachmentsDelete($controller, $params) {
		$controller->autoRender = false;
		if ($controller->Session->check('InstitutionSiteId') && $controller->Session->check('InstitutionSiteAttachmentId')) {
			$id = $controller->Session->read('InstitutionSiteAttachmentId');

			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('InstitutionSiteAttachmentId');
			return $controller->redirect(array('action' => 'attachments'));
		}
	}

	public function attachmentsDownload($controller, $params) {
		$id = $params['pass'][0];
		$controller->FileUploader->downloadFile($id);
	}

}

?>
