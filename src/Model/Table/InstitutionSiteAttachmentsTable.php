<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class InstitutionSiteAttachmentsTable extends Table {
	public function initialize(array $config) {

		$this->belongsTo('InstitutionSites');
		// public $belongsTo = array(
		// 	'InstitutionSite',
		// 	'ModifiedUser' => array('foreignKey' => 'modified_user_id', 'className' => 'SecurityUser'),
		// 	'CreatedUser' => array('foreignKey' => 'created_user_id', 'className' => 'SecurityUser')
		// );

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}


	// public function beforeAction($controller, $action) {
	public function beforeAction() {

		$visibility = ['view' => true, 'edit' => true];
		
		$this->fields['contact_person']['visible'] = $visibility;

		$this->fields['id']['type'] = 'hidden';
		// array('field' => 'name', 'type' => 'file', 'url' => array('action' => 'attachmentsDownload')),
		$this->fields['description']['type'] = 'textarea';
		// $this->fields['id']['type'] = 'hidden';

				// array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				// array('field' => 'modified', 'edit' => false),
				// array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				// array('field' => 'created', 'edit' => false)

		// $this->controller->set('model', $this->alias);
		// $this->controller->FileUploader->fileModel = 'InstitutionSiteAttachment';
		// $this->controller->FileUploader->additionalFileType();
	}

	// public function attachments($controller, $params) {
	public function attachments() {
		pr('model attachments');
		pr('===============================================');
		pr($this);die;
		$this->controller->ControllerAction->index();
		// $this->render = false;
		// $controller->Navigation->addCrumb('Attachments');
		// $header = __('Attachments');
		// $data = $this->findAllByInstitutionSiteIdAndVisible($controller->institutionSiteId, 1, array('id', 'name', 'description', 'file_name', 'file_content', 'created'), array(), null, null, -1);
		// $arrFileExtensions = $controller->Utility->getFileExtensionList();
		// $controller->set(compact('data', 'arrFileExtensions', 'header'));
		// $controller->render('/Elements/attachment/index');
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
		if ($controller->Session->check('InstitutionSite.id') && $controller->Session->check('InstitutionSiteAttachmentId')) {
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
