<?php
App::uses('AppController', 'Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('CakeNumber', 'Utility');

class ConfigController extends AppController {
	public $uses = array(
		'ConfigItem',
		'ConfigAttachment',
		'SchoolYear'
	);
	public $helpers = array('Number', 'Js' => array('Jquery'), 'Paginator');
    public $components = array(
		'Paginator',
		'FileAttachment' => array(
			'model' => 'ConfigAttachment'
		)
	);
    private $imageConfig = array();
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		$this->bodyTitle = 'Settings';
		$this->imageConfig = $this->ConfigItem->getImageConfItem();
	}
	
	public function getJSConfig() {
		$this->Navigation->skip = true;
		$this->autoLayout = false;
		$this->RequestHandler->respondAs('text/javascript');
		
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$host = $_SERVER['HTTP_HOST'];
		$protocol = strtolower(substr($protocol, 0, strpos($protocol, '/'))) . '://';
		
		$url = $protocol . $host . $this->webroot;
		
		$this->set('rootURL', $url);
		$this->set('ajaxReturnCodes', $this->Utility->ajaxReturnCodes());
		$this->set('ajaxErrorHandler', $this->Utility->getAjaxErrorHandler());
		$this->render('config');
	}
	
	public function getI18n() {
		$this->Navigation->skip = true;
		$this->autoLayout = false;
		$this->RequestHandler->respondAs('text/javascript');
		$this->render('i18n');
	}

	public function index(){
		$this->Navigation->addCrumb('System Configurations');
		
		$items = $this->ConfigItem->find('all',array(
				'fields' => array('ConfigItem.id', 'ConfigItem.name', 'ConfigItem.label', 'ConfigItem.type', 'ConfigItem.value', 'ConfigItem.default_value', 'ConfigItem.editable', 'ConfigItem.visible'),
				'recursive' => 0,
				// 'group' => array('ConfigItem.type'),
				'conditions' => array('ConfigItem.visible' => 1, 'ConfigItem.editable' => 1)
				// 'conditions' => array('ConfigItem.editable' => 1, 'ConfigItem.visible' => 1)
			));
		foreach ($items as $key => $value) {
			foreach ($items[$key] as $innerKey => $innerValue) {
				$items[$key][$innerKey]['value'] = (is_null($items[$key][$innerKey]['value']) || empty($items[$key][$innerKey]['value']))? $items[$key][$innerKey]['default_value']: $items[$key][$innerKey]['value'];
			}
		}

		$sorted = $this->groupByType($this->ConfigItem->formatArray($items));

		$this->set('items', $sorted);
	}

	public function edit(){
		$this->Navigation->addCrumb('Edit System Configurations');

		$items = $this->ConfigItem->find('all',array(
				'fields' => array('ConfigItem.id', 'ConfigItem.name', 'ConfigItem.label', 'ConfigItem.type', 'ConfigItem.value', 'ConfigItem.default_value', 'ConfigItem.visible'),
				'recursive' => 0,
				'conditions' => array('ConfigItem.editable' => 1, 'ConfigItem.visible' => 1)
			));
		foreach ($items as $key => $value) {
			foreach ($items[$key] as $innerKey => $innerValue) {
				$items[$key][$innerKey]['value'] = (is_null($items[$key][$innerKey]['value']) || empty($items[$key][$innerKey]['value']))? $items[$key][$innerKey]['default_value']: $items[$key][$innerKey]['value'];
			}
		}

		$school_year_raw = $this->SchoolYear->find('list', array('fields' => 'name', 'order' => 'name desc'));
		$school_year = array();
		foreach ($school_year_raw as $value) {
			$school_year[$value] = $value;
		}

		$sorted = $this->groupByType($this->Utility->formatResult($items));
		$this->set('school_years', $school_year);
		$this->set('items', $sorted);
	}

	public function save() {
		$this->autoRender = false;
		if($this->request->is('post')){
			$savedItems = false;
			$savedFeatures = false;
			$dataToBeSave = array();
			foreach($this->request->data as $key => $element){
				if(strtolower($key) == 'configitem'){
					$dataToBeSave = $element;
					break;
				}
			}
			foreach($dataToBeSave as $key => $element){
					foreach($element as $key => $innerElement){
						if ($this->ConfigItem->save($innerElement)) {
				            $savedItems = true;
				        }else{
				        	echo 'false<br/>';
				        }

					}
			}
			$this->Session->write('configItem.language', $this->ConfigItem->getValue('language'));
			$this->Session->write('configItem.currency', $this->ConfigItem->getValue('currency'));
			$this->redirect('/Config');
		}
	}

	################# Start Dashboard #################

	public function dashboard(){
		$this->Navigation->addCrumb('System Configurations', array('controller' => 'Config', 'action' => 'index'));
		$this->Navigation->addCrumb('Dashboard Image');
		$fileExtensions = $this->Utility->getFileExtensionList(); 
		$imageFileExts = array();
		foreach ($fileExtensions as $key => $value) {
			if(strtolower($value) == 'image'){
				$imageFileExts[$key] = $value;
			}
		}
		$data = $this->ConfigAttachment->find('all', array('conditions' => array('ConfigAttachment.type' => 'dashboard')));
        $this->set('data', $data);
		$this->set('arrFileExtensions', $imageFileExts);
		$this->render('/Config/dashboard/view');
	}
	
    public function dashboardEdit() {
		$this->Navigation->addCrumb('System Configurations', array('controller' => 'Config', 'action' => 'index'));
		$this->Navigation->addCrumb('Dashboard Image', array('controller' => 'Config', 'action' => 'dashboard'));
		$this->Navigation->addCrumb('Edit');

        if($this->request->is('post')) { // save
        	$requestData = $this->data;
        	$active = $requestData['ConfigAttachment']['visible'];
        	unset($requestData['ConfigAttachment']['visible']);
        	foreach ($requestData['ConfigAttachment'] as $key => $value) {
        		if($key == $active){
	        		$requestData['ConfigAttachment'][$key]['active'] = 1;
        		}else{
	        		$requestData['ConfigAttachment'][$key]['active'] = 0;	
        		}
        	    $requestData['ConfigAttachment'][$key]['type'] = 'dashboard';
        	}

        	$errors = (isset($_FILES['files']))?$this->vaildateImage($_FILES):array();
        	// pr($_FILES);
        	// die();

        	if(sizeof($errors) == 0) $errors = array_merge($errors,$this->FileAttachment->saveAll($requestData, $_FILES, null));
        	
			if(sizeof($errors) == 0) {
				$this->Utility->alert(__('Files have been saved successfully.'));
				$this->redirect(array('action' => 'dashboard'));
			} else {
				$this->Utility->alert(__('Some errors have been encountered while saving files.'), array('type' => 'error'));
			}
        }
		
        $data = $this->ConfigAttachment->find('all',
            array('fields' => array('ConfigAttachment.id', 'ConfigAttachment.name', 'ConfigAttachment.file_name', 'ConfigAttachment.order', 'ConfigAttachment.active', 'ConfigAttachment.created'),
                'conditions' => array('ConfigAttachment.type' => 'dashboard')
            ));
        $this->set('data',$data);
		$this->set('imageConfig', $this->imageConfig);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
		$this->render('/Config/dashboard/edit');
    }
	
	public function dashboardAdd() {
		$this->layout = 'ajax';
		$this->set('params', $this->params->query);
		$this->set('_model', $this->ConfigAttachment->modelName());
		$this->render('/Config/dashboard/add');
	}
       
    public function dashboardUpdateVisible() {
		$this->autoRender = false;
        if($this->request->is('post')) {
			$result = array('alertOpt' => array());
			// $this->Utility->setAjaxResult('alert', $result);
			$id = $this->params->data['id'];

			$rows = $this->ConfigAttachment->find('all', array('conditions' => array('visible' => 1)));
			foreach ($rows as $key => $value) {
				$rows[$key]['ConfigAttachment']['visible'] = 0;
				$this->ConfigAttachment->save($rows[$key]);
			}
			$row = $this->ConfigAttachment->find('first', array('conditions' => array('id'=>$id)));
			if($row){
				$row['ConfigAttachment']['visible'] = 1;
				if($this->ConfigAttachment->save($row)) {
					$result['alertType'] = $this->Utility->getAlertType('alert.ok');
					$result['alertOpt']['text'] = __('File is updated successfully.');
				}else {
					$result['alertType'] = $this->Utility->getAlertType('alert.error');
					$result['alertOpt']['text'] = __('Error occurred while updating file.');
				}
			}else {
				$result['alertType'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = __('Error occurred while updating file.');
			}
			
			return json_encode($result);
        }
    }
       
    public function dashboardDelete() {
		$this->autoRender = false;
        if($this->request->is('post')) {
			$result = array('alertOpt' => array());
			$this->Utility->setAjaxResult('alert', $result);
			$id = $this->params->data['id'];
			$isDeletedVisible = $this->ConfigAttachment->find('all', 
				array('conditions' => array(
						'ConfigAttachment.visible' => 1,
						'ConfigAttachment.id' => $id
					)
				)
			);
			
			if($this->FileAttachment->delete($id)) {
				$result['alertOpt']['text'] = __('File is deleted successfully.');
				if($isDeletedVisible){
					$row = $this->ConfigAttachment->find('first', array('order' => 'id DESC', 'limit' => 1));
					$row['ConfigAttachment']['visible'] = 1;
					$this->ConfigAttachment->save($row);
					$result['visibleRecord'] = $row['ConfigAttachment']['id'];
				}
			} else {
				$result['alertType'] = $this->Utility->getAlertType('alert.error');
				$result['alertOpt']['text'] = __('Error occurred while deleting file.');
			}
			
			return json_encode($result);
        }
    }
        
    public function attachmentsDownload($id) {
        $this->FileAttachment->download($id);
    }

	public function fetchImage($id){
		$this->autoRender = false;

		$imageFile = $this->ConfigAttachment->findById($id);
		$fileExt = pathinfo($imageFile['ConfigAttachment']['file_name'], PATHINFO_EXTENSION);
		$filename = pathinfo($imageFile['ConfigAttachment']['file_name'], PATHINFO_FILENAME);
		
		if($fileExt == 'jpg'){
			$fileExt = 'jpeg';
		}

		header('Content-type: image/'.$fileExt);
		echo $imageFile['ConfigAttachment']['file_content'];
		
	}

	public function dashboardImage($id){
		$this->Navigation->addCrumb('System Configurations', array('controller' => 'Config', 'action' => 'index'));
		$this->Navigation->addCrumb('Dashboard Image', array('controller' => 'Config', 'action' => 'dashboard'));
		$this->Navigation->addCrumb('Update');

		$isEdited = false;

		if($this->request->is('post')){

			$action = $this->request->data['ConfigItem']['action'];

			if (stristr(strtolower($action), 'edit')) {
				$isEdited = $this->ConfigAttachment->updateAttachmentCoord($this->request->data['ConfigItem']['id'],$this->request->data['ConfigItem']['x'], $this->request->data['ConfigItem']['y']);
			}

			if($isEdited){
				$this->Utility->alert(__('File have been updated successfully.'));
				$this->redirect(array('action' => 'dashboard'));
			}else{
				$this->Utility->alert(__('File have not been updated successfully.'));
				$this->redirect(array('action' => 'dashboard'));

			}
			
		}

		// $scale = $this->imageConfig['dashboard_img_width'] / $this->imageConfig['orignal_width'];
		$data = $this->ConfigAttachment->findById($id);//('all', array('conditions' => array('ConfigAttachment.id' => $id)));
		if(isset($data) && sizeof($data['ConfigAttachment']) > 0 ){
			$data = array_merge($data['ConfigAttachment']);
			// $imageResource = imagecreatefromstring($data['file_content']);
			// $data['width'] = imagesx($imageResource);
			// $data['height'] = imagesy($imageResource);
			$data = array_merge($data, $this->ConfigAttachment->getResolution($data['file_name']));
			$data = array_merge($data, $this->ConfigAttachment->getCoordinates($data['file_name']));
			unset($data['file_content']);

			// pr($data);
			// die();
			$this->set('data', $data);
		}

		$this->set('imageConfig', $this->imageConfig);
		$this->render('/Config/dashboard/dashboardImage');
	}

	private function vaildateImage(&$images){
		$supportedMimeType = array(
			'image/gif',
			'image/jpeg',
			// 'image/pjpeg',
			'image/png',
			// 'image/svg+xml',
			// 'image/tiff'
			// 'image/vnd.microsoft.icon'
		);


		$isVaild = false;
		
		$msg = array();

		foreach ($images['files']['tmp_name'] as $key => $value) {
			if($images['files']['error'][$key] == UPLOAD_ERR_NO_FILE){
				continue;
			}

			if($images['files']['error'][$key] > 0 && $images['files']['error'][$key] != UPLOAD_ERR_NO_FILE){
				$msg[$key] = 'Upload Error.';
			}else{
				# code...
				list($width, $height, $type, $attr) = getimagesize($images['files']['tmp_name'][$key]);
				
				// Check that upload image is supported.
				if(in_array($images['files']['type'][$key], $supportedMimeType)){
					$isVaild = true;
				}else{
					$msg[$key] = __("File format not supported.");
				}

				// Check that image file is within the size limit.
				if($isVaild && $images['files']['size'][$key] > $this->imageConfig['dashboard_img_size_limit']){
					$isVaild = $isVaild && false;
					$msg[$key] = __('Image have exceeded the allow file size of').' '.CakeNumber::toReadableSize($this->imageConfig['dashboard_img_size_limit']).'. '.__('Please reduce file size.');
				}

				// Check if uploaded image is within the limited width and height set in system
				if($isVaild && $width < $this->imageConfig['dashboard_img_width'] && $height < $this->imageConfig['dashboard_img_height']){
					$isVaild = $isVaild && false;
					$msg[$key] = __("Image resolution is too small.");
				}else{
					$images['files']['resolution'][$key] = array('width'=>$width, 'height' => $height);
				}
			}
			
		}
		return $msg;

	}

	################# End Dashboard #################

	private function groupByType($items=null){
		$groupByType = array();
		if(is_null($items)){
			return array();
		}
		$types = $this->ConfigItem->getTypes();
		foreach ($types as $element) {
			$groupByType[$element] = array();
		}
		foreach ($groupByType as $key => $element) {
			foreach ($items as $innerKey => $innerElement) {
				if($key == $innerElement['type']){
					$element[] = $innerElement;
				}
			}
			$groupByType[$key] = $element;
		}
		return  $groupByType;

	}

}