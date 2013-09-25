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

App::uses('AppController', 'Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('CakeNumber', 'Utility');
App::uses('ImageMeta', 'Image');
App::uses('ImageValidate', 'Image');

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
		),
		'LDAP'
	);
    private $imageConfig = array();
	
	public function beforeFilter() {
		parent::beforeFilter();
        $this->Auth->allow('getJSConfig');
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		$this->bodyTitle = 'Settings';
		$this->imageConfig = $this->ConfigItem->getImageConfItem();
	}
	
	public function getJSConfig() {
		$this->Navigation->skip = true;
		$this->autoLayout = false;
		$this->RequestHandler->respondAs('text/javascript');
		
		$protocol = ($_SERVER['SERVER_PORT'] == '443'?'https://':'http://');
                $host = $_SERVER['HTTP_HOST'];
		
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
			'conditions' => array('ConfigItem.visible' => 1, 'ConfigItem.editable' => 1)
		));
		foreach ($items as $key => $value) {
			foreach ($items[$key] as $innerKey => $innerValue) {
				$items[$key][$innerKey]['value'] = (is_null($items[$key][$innerKey]['value']) || empty($items[$key][$innerKey]['value']))? $items[$key][$innerKey]['default_value']: $items[$key][$innerKey]['value'];
				if ($items[$key][$innerKey]['name'] == "yearbook_logo") {
					$items[$key][$innerKey]['hasYearbookLogoContent'] = false;

					$attachment = $this->ConfigAttachment->findById($items[$key][$innerKey]['value']);

					if (!empty($attachment['ConfigAttachment']['file_content'])) {
						$items[$key][$innerKey]['hasYearbookLogoContent'] = true;
					}

				}
			}
		}

		$sorted = $this->groupByType($this->ConfigItem->formatArray($items));
		// Checking if user has access to _view for setup
		$_view_dashboard = false;
		if($this->AccessControl->check($this->params['controller'], 'dashboard')) {
			$_view_dashboard = true;
		}
		$this->set('_view_dashboard', $_view_dashboard);
		
		$schoolYear = $this->SchoolYear->find('list', array('fields' => array('SchoolYear.id', 'SchoolYear.name'), 'order' => array('name desc')));
		$this->set('school_years', $schoolYear);
		// End Access Control
		$this->set('items', $sorted);
	}

	public function edit(){
		$this->Navigation->addCrumb('Edit System Configurations');


		if($this->request->is('post')){
			//pr($this->request->data);
			$dataToBeSave = array();
			foreach($this->request->data as $key => $element){
				if(strtolower($key) == 'configitem'){
					$dataToBeSave = $element;
					break;
				}
			}

			$errorCustomMsg = $this->validateFields($dataToBeSave);


			if(empty($errorCustomMsg)) {
				$this->save();
			}

			
			$this->set('errorCustomMsg', $errorCustomMsg);
		}


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
		$schoolYear = $this->SchoolYear->find('list', array('fields' => array('SchoolYear.id', 'SchoolYear.name'), 'order' => array('name desc')));

		$sorted = $this->groupByType($this->Utility->formatResult($items));
		$this->set('school_years', $schoolYear);
		$this->set('items', $sorted);
	}

	private function validateFields($data){
		$error = array();
		foreach($data as $key => $element){
			foreach($element as $innerKey => $innerElement){
				if($key=='Data Outliers'){
					if($innerElement['id']=='106'){
						$value = $innerElement['value'];
						if(!is_numeric($value) || $value < 1 || $value > 5){
							$error[$innerElement['id']] = "Please enter numeric value between 1 to 5";
						}
					}
				}
			}
		}

		return $error;
	}

	public function save() {
		//$this->autoRender = false;
		if($this->request->is('post')){
			$this->ConfigItem->set($this->request->data);
			$savedItems = false;
			$savedFeatures = false;
			$dataToBeSave = array();
			foreach($this->request->data as $key => $element){
				if(strtolower($key) == 'configitem'){
					$dataToBeSave = $element;
					break;
				}
			}
			//pr($dataToBeSave);'
			foreach($dataToBeSave as $key => $element){
				foreach($element as $innerKey => $innerElement){
					$yearbookLogoElement = "";
					$configItem = $this->ConfigItem->findById($innerElement['id'], array('ConfigItem.name'));
					$formData = $this->data;

					
					// if student/teacher/staff prefix
					if($configItem['ConfigItem']['name'] == "student_prefix" || $configItem['ConfigItem']['name'] == "teacher_prefix" || $configItem['ConfigItem']['name'] == "staff_prefix") {
						$prefix = $formData['ConfigItem']['Auto Generated Identification No'][$innerKey]['value']['prefix'];
						$enable = $formData['ConfigItem']['Auto Generated Identification No'][$innerKey]['value']['enable'];
						unset($innerElement[$innerKey]['value']['prefix']);
						unset($innerElement[$innerKey]['value']['enable']);
						$innerElement['value'] = $prefix.','.$enable;
					}
					
					// if student/teacher/staff prefix
					if($configItem['ConfigItem']['name'] == "student_prefix" || $configItem['ConfigItem']['name'] == "teacher_prefix" || $configItem['ConfigItem']['name'] == "staff_prefix") {
						$prefix = $formData['ConfigItem']['Auto Generated Identification No'][$innerKey]['value']['prefix'];
						$enable = $formData['ConfigItem']['Auto Generated Identification No'][$innerKey]['value']['enable'];
						unset($innerElement[$innerKey]['value']['prefix']);
						unset($innerElement[$innerKey]['value']['enable']);
						$innerElement['value'] = $prefix.','.$enable;
					}
					
					if($key === 'Year Book Report') {
						$yearbook = $formData['ConfigItem']['Year Book Report'];
						if($configItem['ConfigItem']['name'] === "yearbook_orientation") {
							if(empty($innerElement['value'])) {
								$innerElement['value'] = '';
								$this->log($innerElement, 'debug');
							}
						} else if($configItem['ConfigItem']['name'] === "yearbook_logo") {
							$imgValidate = new ImageValidate(800,800);
							$data = array();
							$reset_image = $yearbook[$innerKey]['reset_yearbook_logo'];
	
							if (isset($yearbook[$innerKey]['file_value']) && $yearbook[$innerKey]['file_value']['error'] != UPLOAD_ERR_NO_FILE) {
								
								if (array_key_exists('reset_yearbook_logo', $yearbook[$innerKey])) {
									if (!empty($yearbook[$innerKey]['file_value'])) {
										$img = new ImageMeta($yearbook[$innerKey]['file_value']);
	
										if($reset_image == 0){
											$validated = $imgValidate->validateImage($img);
	
											if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
												$yearbookLogo = $this->ConfigAttachment->find('first', array(
													'conditions' => array('ConfigAttachment.type' => 'Year Book Report')
												));
												if($yearbookLogo) {
													$data['id'] = $yearbookLogo['ConfigAttachment']['id'];
												}
												$data['file_content'] = $img->getContent();
												$img->setName('yearbook_logo');
												$data['file_name'] = $img->getFilename();
												$data['type'] = 'Year Book Report';
												$data['name'] = $yearbook[$innerKey]['file_value']['name'];
												$data['description']="";
												$data['order']="0";
											}
											$rec = $this->ConfigAttachment->save($data);
	
											// check if yearbook logo is stored in attachment, and stored the id to config Item
											$innerElement['value'] = "";
											if (!empty($rec) && $rec['ConfigAttachment']['id'] > 0) {
												$innerElement['value'] = $rec['ConfigAttachment']['id'];
											}
										}else{
	
											$data['ConfigAttachment']['file_content'] = '';
											$data['ConfigAttachment']['file_name'] = '';
										}
									}				            
								}
								
							} else {
								if ($reset_image == 1) {				            	
									if ($yearbook[$innerKey]['value'] > 0 && $yearbook[$innerKey]['value'] != "" && !is_null($yearbook[$innerKey]['value'])) {
										$data['id'] = $yearbook[$innerKey]['value'];
										$data['file_content'] = "";
										$data['file_name'] = "";					                    
										$data['name'] = "";
									}
									$rec = $this->ConfigAttachment->save($data);
								}
							}
						}
					}
					
					//$this->log($innerElement, 'debug');
					if ($this->ConfigItem->save($innerElement)) {
						$savedItems = true;
						if($configItem['ConfigItem']['name'] == "date_format") {
							$_SESSION['Config.DateFormat'] = $innerElement['value'];
						}
	                }else{
	                    #echo 'false<br/>';
	                }
				}
				//$this->ConfigItem->saveAll($element);
				
			}
			$this->Session->write('configItem.language', $this->ConfigItem->getValue('language'));
			$this->Session->write('configItem.currency', $this->ConfigItem->getValue('currency'));
			$this->Session->write('configItem.yearbook_school_year', $this->ConfigItem->getValue('yearbook_school_year'));
			$this->redirect('/Config');
			
		}
	}

	################# Start Yearbook #################

	public function fetchYearbookImage($id){
        $this->autoRender = false;

        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->ConfigAttachment->findById($id);

        if(empty($imageRawData['ConfigAttachment']['file_content']) || empty($imageRawData['ConfigAttachment']['file_content'])){
            header("HTTP/1.0 404 Not Found");
            die();
        }else{
            $imageFilename = $imageRawData['ConfigAttachment']['file_name'];
            $fileExt = pathinfo($imageFilename, PATHINFO_EXTENSION);
            $imageContent = $imageRawData['ConfigAttachment']['file_content'];
       // header("Content-type: {$imageMeta->getMime()}");
            header("Content-type: " . $mime_types[$fileExt]);
            echo $imageContent;
        }
    }

	################# End Yearbook #################
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
				$this->Utility->alert(__('File has not been updated successfully.'));
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
					$msg[$key] = __('Image has exceeded the allow file size of').' '.CakeNumber::toReadableSize($this->imageConfig['dashboard_img_size_limit']).'. '.__('Please reduce file size.');
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
	public function getAllowedChar() {
		$this->layout = 'ajax';
		$this->autoRender = false;
		echo $this->ConfigItem->getValue('special_characters');
	}
	public function getAllRules() {
		$this->layout = 'ajax';
		$this->autoRender = false;
		$data = $this->ConfigItem->getAllCustomValidation();
		echo json_encode($data);
	}
	
	
	public function checkLDAPconn(){
		$this->layout = 'ajax';
		$this->autoRender = false;
		
		//checkConn
		if($this->RequestHandler->isAjax()){
			if ($this->request->is('post')){
				
				$arrSettings = array(
								'host'=>$this->data['server'],
								'port'=>$this->data['port'],
								'version'=>$this->data['version'],
								'basedn'=>$this->data['basedn']
							);
				 echo  (($this->LDAP->checkConn($arrSettings))?'ok':'failed');
				
			}
		}
		die;
		
	}
}