<?php

class FileUploaderComponent extends Component {
	/* ---------------------------------------------------------------------------
	 * $fileSizeLimit : Set the file size limit in bytes
	 * @var int 
	 * ---------------------------------------------------------------------------- */

	public $fileSizeLimit = 0;

	/* ---------------------------------------------------------------------------
	 * $fileModel : It is the name of the model that you want. 
	 * @var string
	 * ---------------------------------------------------------------------------- */
	public $fileModel = 'ImageUpload';

	/* ---------------------------------------------------------------------------
	 * $fileVar : it is the name of the key to look in for an uploaded file
	 * For this to work you will need to use the
	 * 
	 * - Single upload
	 * $form-input('file', array('type'=>'file')); 
	 * 
	 * OR
	 *
	 * - Multiple uploads
	 * $form-input('files.', array('type'=>'file' ,'multiple' ));  
	 *
	 * @var string
	 * ---------------------------------------------------------------------------- */
	public $fileVar = 'file';

	/* ---------------------------------------------------------------------------
	 * $uploadedFile : This will hold the uploadedFile array if there is one 
	 * @var boolean|array 
	 * ---------------------------------------------------------------------------- */
	public $uploadedFile = false;

	/* ---------------------------------------------------------------------------
	 * $data and $param : Both are retriving from the controller
	 * ---------------------------------------------------------------------------- */
	public $data = array();
	public $param = array();

	/* ---------------------------------------------------------------------------
	 * $allowedTypes : The type of files that are accepted
	 * @var array
	 * ---------------------------------------------------------------------------- */
	public $allowedTypes = array(
		'image/jpeg',
		'image/gif',
		'image/png',
		'image/pjpeg',
		'image/x-png'
	);

	/* ---------------------------------------------------------------------------
	 * $success is to check whether the upload is completed or not
	 * @return true/false
	 * ---------------------------------------------------------------------------- */
	public $success = false;

	/* ---------------------------------------------------------------------------
	 * $dbPrefix : the name that will be used in the database column 
	 * it will always auto append with "_name" | "_content"
	 * ---------------------------------------------------------------------------- */
	public $dbPrefix = 'file';//'photo';

	/* ---------------------------------------------------------------------------
	 * $additionData : Additional infomation to be append and save togather with the data
	 * e.g. $additionData['form_id'] = 1;
	 * @var array
	 * ---------------------------------------------------------------------------- */
	public $additionData = array();

	/* ---------------------------------------------------------------------------
	 * $allowEmptyUpload : a bool check whether to allow empty upload or not
	 * @var bool
	 * ---------------------------------------------------------------------------- */
	public $allowEmptyUpload = false;

	/* ---------------------------------------------------------------------------
	 * $success : Output the error message for the controller to call
	 * @return string
	 * ---------------------------------------------------------------------------- */
	//public $message = '';

	public $alertMessage = array(
		'success' => 'The file%s has been uploaded.',
		'error' => array(
			'general' => 'An error has occur. Please contact the system administrator.',
			'uploadSizeError' => 'Please ensure that the file is smaller than file limit.',
			'UPLOAD_ERR_NO_FILE' => 'No file was uploaded.',
			//'UPLOAD_ERR_FORM_SIZE' => 'The uploaded file "%s" exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
			//'UPLOAD_ERR_FORM_SIZE' => 'Please ensure that the file "%s" is smaller than %s.',
			'UPLOAD_ERR_FORM_SIZE' => 'Please ensure that the file is smaller than file limit.',
			'UPLOAD_ERR_INI_SIZE' => 'Please ensure that the file is smaller than file limit.',
			//'UPLOAD_ERR_INI_SIZE' => 'The uploaded file "%s" exceeds the upload_max_filesize directive in php.ini.',
			'invalidFileFormat' => 'file is not a valid format.',
			'saving' => 'Failed to save the file.'
		)
	);
	public $components = array('Utility');

	public function initialize(Controller $controller) {
		$this->fileSizeLimit = 2 * 1024 * 1024;
		$this->data = $controller->data;
		$this->params = $controller->params;
	}

	/* ---------------------------------------------------------------------------
	 * By calling uploadFile() at the controller, it will handle all the error 
	 * checking plus upload it to the database
	 * 
	 * ---------------------------------------------------------------------------- */

	public function uploadFile($id = NULL) {
		// pr($this->data);die;
		if (!empty($this->data)) {
			$this->uploadedFile = $this->_getUploadFileArr();
			//	pr($this->uploadedFile);die;
			//	pr($this->data);
			//	$id = '';
			if (!empty($this->data[$this->fileModel]['id'])) {
				$id = $this->data[$this->fileModel]['id'];
			}

			//pr($id);

			if ($this->_checkFile() && $this->_checkType()) {
				$this->_processFile($id);
			} else {
				$this->success = false;
			}
		}
	}

	/* ---------------------------------------------------------------------------
	 * By calling fetchImage() at the controller, it will load the image blob and 
	 * display it according
	 * 
	 * $id : image id 
	 * ---------------------------------------------------------------------------- */

	public function fetchImage($id) {
		$model = & $this->getModel();
		$controller->autoRender = false;
		$data = $model->findById($id);

		$fileExt = pathinfo($data[$this->fileModel]['photo_name'], PATHINFO_EXTENSION);

		if ($fileExt == 'jpg') {
			$fileExt = 'jpeg';
		}

		header('Content-type: image/' . $fileExt);
		echo $data[$this->fileModel]['photo_content'];
	}

	/* ---------------------------------------------------------------------------
	 * By calling additionalFileType() at the controller, it will support more file types
	 * ---------------------------------------------------------------------------- */

	public function additionalFileType() {
		$this->allowedTypes[] = 'text/rtf';
		$this->allowedTypes[] = 'text/plain';
		$this->allowedTypes[] = 'text/csv';
		$this->allowedTypes[] = 'application/pdf';
		$this->allowedTypes[] = 'application/vnd.ms-powerpoint';
		$this->allowedTypes[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
		$this->allowedTypes[] = 'application/msword';
		$this->allowedTypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		$this->allowedTypes[] = 'application/vnd.ms-excel';
		$this->allowedTypes[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		$this->allowedTypes[] = 'application/zip';
	}

	public function downloadFile($id) {
		$model = & $this->getModel();
		$model->recursive = -1;
		$data = $model->findById($id);
		//pr($data);
		$fileName = $data[$this->fileModel][$this->dbPrefix . '_name'];

		$fileInfo = explode('.', $data[$this->fileModel][$this->dbPrefix . '_name']);
		$fileType = $fileInfo[count($fileInfo) - 1];

		//$fileName = str_replace('.'.$fileType, '', $data[$this->fileModel][$this->dbPrefix.'_name']);

		header('Content-type: ' . $fileType);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		echo $data[$this->fileModel][$this->dbPrefix . '_content'];

		exit();
	}

	public function getList($option = array()) {
		if(empty($option)){
			return array();
		}
		$foreignKey = key($option);
		$id = $option[$foreignKey];
		$model = & $this->getModel();
		$data = $model->find('all', array(
			'conditions' => array($this->fileModel . '.visible' => 1, $this->fileModel . '.' . $foreignKey => $id)
		));
		//$this->fixBlankFile($data);
		return $data;
	}

	function &getModel() {
		$model = null;
		$name = $this->fileModel;

		if ($name) {
			$model = ClassRegistry::init($name);


			if (empty($model) && $this->fileModel) {
				//$this->_error('FileUpload::getModel() - Model is not set or could not be found');
				return null;
			}
		}
		return $model;
	}

	function _processFile($id = NULL) {
		$model = & $this->getModel();
		$fileData = array();


		foreach ($this->uploadedFile as $selectedFile) {

			$selectedData = array();
			if (!empty($id)) {
				$selectedData['id'] = $id;
			}
			$selectedData[$this->dbPrefix . '_content'] = file_get_contents($selectedFile['tmp_name']);
			$selectedData[$this->dbPrefix . '_name'] = $selectedFile['name'];
			//pr($this->additionData);
			if (!empty($this->additionData)) {
				$selectedData = array_merge($selectedData, $this->additionData);
			}
			//pr($selectedData);die;
			array_push($fileData, array($this->fileModel => $selectedData));
		}
		if (!empty($fileData) && !empty($model)) {
			if ($model->saveAll($fileData, array('validate' => false))) {
				$this->success = true;
				if (count($fileData) > 1) {
					$this->Utility->alert(__(sprintf($this->alertMessage['success'], 's')), array('type' => 'ok'));
				} else {
					$this->Utility->alert(__(sprintf($this->alertMessage['success'], '')), array('type' => 'ok'));
				}
			} else {
				$this->success = false;
				$this->Utility->alert(__($this->alertMessage['error']['saving']), array('type' => 'error'));
			}
		} else {
			if ($this->allowEmptyUpload) {
				$this->success = true;
			} else {
				$this->success = false;
				$this->Utility->alert(__($this->alertMessage['error']['general']), array('type' => 'error'));
			}
			//	$this->message = __($this->alertMessage['error']['general']);//'An error has occur. Please contact the system administrator.';
		}
	}

	function _getUploadFileArr() {
		 /*pr($this->fileModel);
		  pr($this->fileVar);
		  pr($this->data[$this->fileModel][$this->fileVar]); die;*/
		if (!empty($this->fileModel) && isset($this->data[$this->fileModel][$this->fileVar])) {
			if ($this->fileVar == 'files') {
				$fileArr = $this->data[$this->fileModel][$this->fileVar];
			} else {
				$fileArr[] = $this->data[$this->fileModel][$this->fileVar];
			}
		} else {
			$fileArr = false;
		}
		return $fileArr;
	}

	function _checkFile() {
		foreach ($this->uploadedFile as $key => $selectedFile) {
			if ($selectedFile['size'] > $this->fileSizeLimit) {
				$message = __(sprintf($this->alertMessage['error']['uploadSizeError']));
				$this->Utility->alert(__($message), array('type' => 'error'));
				return false;
			} else if ($selectedFile['error'] == UPLOAD_ERR_OK) {
				//$this->message = "";
				//return true;
			} else if ($selectedFile['error'] == UPLOAD_ERR_INI_SIZE) {
				$message = __(sprintf($this->alertMessage['error']['UPLOAD_ERR_INI_SIZE']));
				$this->Utility->alert(__($message), array('type' => 'error'));
				return false;
			} else if ($selectedFile['error'] == UPLOAD_ERR_FORM_SIZE) {
				$message = __(sprintf($this->alertMessage['error']['UPLOAD_ERR_FORM_SIZE']));
				$this->Utility->alert(__($message), array('type' => 'error'));
				return false;
			} else if ($selectedFile['error'] == UPLOAD_ERR_NO_FILE) {
				if (!$this->allowEmptyUpload) {
					$message = __($this->alertMessage['error']['UPLOAD_ERR_NO_FILE']);
					$this->Utility->alert(__($message), array('type' => 'info'));
					return false;
				} else {
					unset($this->uploadedFile[$key]);
				}
			} else {
				$message = __($this->alertMessage['error']['general']);
				$this->Utility->alert(__($message), array('type' => 'error'));
				return false;
			}
		}

		return true;
	}

	function _checkType() {

		foreach ($this->uploadedFile as $selectedFile) {
			$isSameFileType = false;

			foreach ($this->allowedTypes as $fileType) {
				if (strtolower($fileType) == strtolower($selectedFile['type']) && !$isSameFileType) {
					$isSameFileType = true;
					break;
				}
			}

			if (!$isSameFileType) {
				$message = sprintf($this->alertMessage['error']['invalidFileFormat'], $selectedFile['name']);
				$this->Utility->alert(__($message), array('type' => 'error'));
				return false;
			}
		}

		return true;
	}

	function _convertByteToMegabyte() {
		return (($this->fileSizeLimit / 1024) / 1024) . " MB";
	}

}

?>