<?php
namespace User\Controller\Component;

use Cake\Controller\Component;

class ImageComponent extends Component {
	public function getUserImage($id) {
		$base64Format = (array_key_exists('base64', $this->request->query))? $this->request->query['base64']: false;
		$controller = $this->_registry->getController();
		$UserModel = $controller->ControllerAction->model;
		$photoData = $UserModel->find()
			->select(['photo_content'])
			->where([$UserModel->aliasField($UserModel->primaryKey()) => $id])
			->first()
			;
		$phpResourceFile = $photoData->photo_content;

		if (is_resource($phpResourceFile)) {
			if ($base64Format) {
				echo base64_encode(stream_get_contents($phpResourceFile));
			} else {
				$controller->response->type('jpg');
				$controller->response->body(stream_get_contents($phpResourceFile));
			}
		}
	}
}