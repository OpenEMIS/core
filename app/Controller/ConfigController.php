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
		'ConfigItemOption',
		'ConfigAttachment',
		'SchoolYear',
		'Country'
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
		$this->Auth->allow('fetchImage');
		$this->Auth->allow('getJSConfig');
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->bodyTitle = 'Administration';
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


		$typeOptions = $this->ConfigItem->find('list',array(
			'fields' => array('ConfigItem.type', 'ConfigItem.type'),
			'recursive' => -1,
			'conditions' => array('ConfigItem.visible' => 1),
			'order'=> array('ConfigItem.type')
		));

		if($this->AccessControl->newCheck($this->params['controller'], 'dashboard')) {
			$typeOptions['Dashboard'] = 'Dashboard';
		}

		$selectedType = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($typeOptions);
		
		$items = array();
		if($selectedType == 'Dashboard'){
			$fileExtensions = $this->Utility->getFileExtensionList(); 
			$imageFileExts = array();
			foreach ($fileExtensions as $key => $value) {
				if(strtolower($value) == 'image'){
					$imageFileExts[$key] = $value;
				}
			}
			$items = $this->ConfigAttachment->find('all', array('conditions' => array('ConfigAttachment.type' => 'dashboard')));
			$this->set('arrFileExtensions', $imageFileExts);
			$this->set('items', $items);
		}else{
			$items = $this->ConfigItem->find('all',array(
				'recursive' => -1,
				'conditions' => array('ConfigItem.visible' => 1, 'ConfigItem.type' => $selectedType)
			));
		}

		$this->set(compact('typeOptions', 'selectedType'));
		$options = array();
		if($selectedType!='Dashboard'){
			foreach ($items as $key => $value) {

				foreach ($items[$key] as $innerKey => $innerValue) {
					if(isset($items[$key][$innerKey]['option_type'])){
						$options[$items[$key][$innerKey]['id']] = $this->getOptionValue($items[$key][$innerKey]['option_type']);
					}
					if(isset($items[$key][$innerKey]['value'])){
						$items[$key][$innerKey]['value'] = (is_null($items[$key][$innerKey]['value']) || empty($items[$key][$innerKey]['value']))? $items[$key][$innerKey]['default_value']: $items[$key][$innerKey]['value'];
					}
					if ($items[$key][$innerKey]['name'] == "yearbook_logo") {
						$items[$key][$innerKey]['id'] = "yearbook_logo";
						$items[$key][$innerKey]['hasYearbookLogoContent'] = false;

						$attachment = $this->ConfigAttachment->findById($items[$key][$innerKey]['value']);

						if (!empty($attachment['ConfigAttachment']['file_content'])) {
							$items[$key][$innerKey]['hasYearbookLogoContent'] = true;
						}

					}
				}
			}
			$sorted = $this->groupByType($this->ConfigItem->formatArray($items));
		
			$this->set('items', $sorted);
		}

		$this->set('options', $options);
		$this->set('action', $this->action);
		$this->renderView($selectedType, null);

	}

	private function renderView($selectedType, $selectedName=null, $selectedAction=null){
		$views = array(
			'LDAP Configuration' => 'ldap',
			'Dashboard' => 'dashboard',
			'Year Book Report' => 'yearbook_logo',
			'Custom Validation' => 'custom_validation',
			'Auto Generated OpenEMIS ID' => 'auto_generated'
		);
		if (array_key_exists($selectedType, $views)) {
			$view = $views[$selectedType];
			
			if ($selectedType!='Year Book Report' 
			|| ($selectedType=='Year Book Report' && $selectedName=='yearbook_logo')) {
				$this->render('/Config/custom/' . $view);
			}
		}
	}

	private function getDisplayFields($id) {
		if($id == 'LDAP Configuration'){
			$fields = array(
				'model' => 'ConfigItem',
				'fields' => array(
					array('field' => 'type'),
					array('field' => 'host', 'labelKey'=>'Config.host'),
					array('field' => 'port', 'labelKey'=>'Config.port'),
					array('field' => 'version', 'labelKey'=>'Config.version'),
					array('field' => 'base_dn', 'labelKey'=>'Config.base_dn'),
					array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
					array('field' => 'modified', 'edit' => false),
					array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
					array('field' => 'created', 'edit' => false)
				)
			);
		}else{
			$fields = array(
				'model' => 'ConfigItem',
				'fields' => array(
					array('field' => 'type'),
					array('field' => 'label'),
					array('field' => 'value'),
					array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
					array('field' => 'modified', 'edit' => false),
					array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
					array('field' => 'created', 'edit' => false)
				)
			);
		}
		
		return $fields;
	}

	private function getData($id){
		$data = array();
		if($id=='LDAP Configuration'){
			$dataVal = $this->ConfigItem->find('all',array(
				'conditions' => array('ConfigItem.visible' => 1, 'ConfigItem.type' =>$id)
			));
			foreach($dataVal as $key=>$value){
				$data['ConfigItem'][$value['ConfigItem']['name']] = $value['ConfigItem']['value'];
				$data['ConfigItem']['editable'] = $value['ConfigItem']['editable'];
				$data['ConfigItem']['type'] = $value['ConfigItem']['type'];
				$data['ConfigItem']['field_type'] = $value['ConfigItem']['field_type'];
				$data['ConfigItem']['option_type'] = $value['ConfigItem']['option_type'];
				$data['ConfigItem']['visible'] = $value['ConfigItem']['visible'];
				$data['ConfigItem'][$value['ConfigItem']['name'].'Id'] = $value['ConfigItem']['id'];
				$data['ModifiedUser']['modified'] = $value['ModifiedUser']['modified'];
				$data['ModifiedUser']['first_name'] = $value['ModifiedUser']['first_name'];
				$data['ModifiedUser']['last_name'] = $value['ModifiedUser']['last_name'];
				$data['CreatedUser']['created'] = $value['CreatedUser']['created'];
				$data['CreatedUser']['first_name'] = $value['CreatedUser']['first_name'];
				$data['CreatedUser']['last_name'] = $value['CreatedUser']['last_name'];
			}
		}else if($id == 'Dashboard'){
			$fileExtensions = $this->Utility->getFileExtensionList(); 
			$imageFileExts = array();
			foreach ($fileExtensions as $key => $value) {
				if(strtolower($value) == 'image'){
					$imageFileExts[$key] = $value;
				}
			}
			$data = $this->ConfigAttachment->find('all', array('conditions' => array('ConfigAttachment.type' => 'dashboard')));

			$this->set('arrFileExtensions', $imageFileExts);
		}else if($id == 'yearbook_logo'){
			$data = $this->ConfigItem->find('first',array(
				'conditions' => array('ConfigItem.visible' => 1, 'ConfigItem.name' =>$id)
			));
			$attachment = $this->ConfigAttachment->find('first', array('conditions' => array('ConfigAttachment.id' => $data['ConfigItem']['value'])));
			$defaultAttachment = $this->ConfigAttachment->find('first', array('conditions' => array('ConfigAttachment.id' => $data['ConfigItem']['default_value'])));

			$this->set('attachment', $attachment);
			$this->set('defaultAttachment', $defaultAttachment);
		
		}else{
			$data = $this->ConfigItem->find('first',array(
				'conditions' => array('ConfigItem.visible' => 1, 'ConfigItem.id' =>$id)
			));
		}

		return $data;
	}


	private function getOptionValue($option_type){
		$optionValues = $this->ConfigItemOption->find('list',array(
			'fields' => array('ConfigItemOption.value', 'ConfigItemOption.option'),
			'recursive' => -1,
			'conditions' => array('ConfigItemOption.visible'=>1, 'ConfigItemOption.option_type' =>$option_type),
			'order' => array('ConfigItemOption.order')
		));

		$options = array();
		if(strpos($option_type, 'database:')!==false){
			$tableName = str_replace('database:', '', $option_type);
			$table = ClassRegistry::init($tableName);

			$options = $table->find('list', array(
				'fields'=>array(key($optionValues), $optionValues[key($optionValues)]),
				'order' => array(key($optionValues))
			));
		}else{
			foreach($optionValues as $key=>$value){

				if(strpos($value, 'date')!==false){
					eval("\$val = $value;");
				}else{
					$val = $value;
				}
				
				$options[$key] = $val;
			}
		}

		return $options;
	}

	public function view(){
		$this->Navigation->addCrumb('View System Configurations');

		$id = isset($this->params['pass'][0]) ? $this->params['pass'][0] : '';
		if(empty($id)){
			$this->redirect(array('action'=>'index'));
		}

		$data = $this->getData($id);

		$editable = $data['ConfigItem']['editable'];
		$name = $data['ConfigItem']['name'];
		$type = $data['ConfigItem']['type'];
		$fieldType = $data['ConfigItem']['field_type'];
		$optionType = $data['ConfigItem']['option_type'];
		$fields = $this->getDisplayFields($id);
		$options = $this->getOptionValue($optionType);
		if(!empty($options)){
			$data['ConfigItem']['value'] = $options[$data['ConfigItem']['value']];
			$data['ConfigItem']['default_value'] = $options[$data['ConfigItem']['default_value']];
		}

		$this->set(compact('id', 'data', 'fields', 'editable', 'type', 'options'));
		$this->set('action', $this->action);

		$this->renderView($type, $name);

	}
	public function edit(){
		$this->Navigation->addCrumb('Edit System Configurations');
		$id = empty($this->params['pass'][0])? 0:$this->params['pass'][0];

		if ($this->request->is('post')) {
			$data = $this->request->data;

			if($id == 'LDAP Configuration') {
				$fields = array('host', 'port', 'version', 'base_dn');
				$saveData = array();
				$i = 0;
				foreach($fields as $value) {
					$saveData[$i]['id'] = $data['ConfigItem'][$value.'Id'];
					$saveData[$i]['value'] = $data['ConfigItem'][$value];
					$i++;
				}
				if($this->ConfigItem->saveAll($saveData)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action'=>'index', $data['ConfigItem']['type']));
				}
			} else {
				if($this->ConfigItem->save($data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => 'view', $id));
				}
			}
		} else {
			$data = $this->getData($id);
			$this->request->data = $data;
		}

		$type = $this->request->data['ConfigItem']['type'];
		$name = $this->request->data['ConfigItem']['name'];
		$fieldType = $this->request->data['ConfigItem']['field_type'];
		$optionType = $this->request->data['ConfigItem']['option_type'];

		$options = $this->getOptionValue($optionType);

		$this->set(compact('options', 'id', 'type', 'fieldType'));

		$this->set('action', $this->action);
		$this->renderView($type, $name);
	}

	################# Start Yearbook #################

	public function yearbookEdit(){
		$this->Navigation->addCrumb('Edit System Configurations');
		$id = empty($this->params['pass'][0])? 0:$this->params['pass'][0];

		if($this->request->is('get')){
			$data = $this->getData('yearbook_logo');

			//pr($data);

			$this->request->data = $data;
			
		}else{
			$requestData = $this->data['ConfigItem'];
			$imgValidate = new ImageValidate(800,800);
			$data = array();
			$reset_image = $requestData['reset_yearbook_logo'];

			if (isset($requestData['file_value']) && $requestData['file_value']['error'] != UPLOAD_ERR_NO_FILE) {
			
				if (empty($requestData['reset_yearbook_logo'])) {
					if (!empty($requestData['file_value'])) {
						$img = new ImageMeta($requestData['file_value']);

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
								$data['name'] = $requestData['file_value']['name'];
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
					if ($requestData['value'] > 0 && $requestData['value'] != "" && !is_null($requestData['value'])) {
						$data['id'] = $requestData['value'];
						$data['file_content'] = "";
						$data['file_name'] = "";					                    
						$data['name'] = "";
					}
					$rec = $this->ConfigAttachment->save($data);
				}
			}

			return $this->redirect(array('action'=>'index', 'Year Book Report'));
		}

		$this->set('id', $id);
		$this->set('action', 'edit');
		$this->render('/Config/custom/yearbook_logo');
	}

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

	################# Start Auto Generated #################

	public function autoGeneratedEdit(){
		$this->Navigation->addCrumb('Edit System Configurations');
		$id = empty($this->params['pass'][0])? 0:$this->params['pass'][0];

		if($this->request->is('get')){
			$data = $this->getData($id);
			$default_value = '';

			$val = str_replace(",","",substr($data['ConfigItem']['default_value'],0,-1));
			if(substr($data['ConfigItem']['default_value'], -1)>0) {
				$default_value = __('Enabled');
			}else{
				$default_value = __('Disabled');
			}
			if($val!=''){
				$default_value .= ' ';
				$default_value .= __('('.$val.')');
			}



			$data['ConfigItem']['default_value'] = $default_value;

			$this->request->data = $data;
			
		}else{
			$requestData = $this->data;
			$prefix = $requestData['ConfigItem']['value']['prefix'];
			$enable = $requestData['ConfigItem']['value']['enable'];
			$requestData['ConfigItem']['value'] = $prefix.','.$enable;
			unset($requestData['ConfigItem']['default_value']);

			if($this->ConfigItem->save($requestData)){
				return $this->redirect(array('action'=>'index', $requestData['ConfigItem']['type']));
			}
		}

		$type = $this->request->data['ConfigItem']['type'];
		$name = $this->request->data['ConfigItem']['name'];
		$fieldType = $this->request->data['ConfigItem']['field_type'];
		$optionType = $this->request->data['ConfigItem']['option_type'];

		$options = $this->getOptionValue($optionType);

		$this->set(compact('options', 'id', 'type', 'fieldType'));

		$this->set('id', $id);
		$this->set('action', 'edit');
		$this->render('/Config/custom/auto_generated');
	}

	################# End Auto Generated #################
	################# Start Dashboard #################

	public function dashboardView(){
		$this->Navigation->addCrumb('System Configurations', array('controller' => 'Config', 'action' => 'index'));
		$this->Navigation->addCrumb('Dashboard');

		$id = empty($this->params['pass'][0])? 0:$this->params['pass'][0];
		
		$data = $this->ConfigAttachment->find('first', array('conditions' => array('ConfigAttachment.type' => 'dashboard', 'ConfigAttachment.id' => $id)));
		$this->set('data', $data);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());

		if(empty($data)){
			return $this->redirect(array('action'=>'index', 'Dashboard'));
		}

		$image['width'] = $this->ConfigItem->getValue('dashboard_img_width');
		$image['height'] = $this->ConfigItem->getValue('dashboard_img_height');
		$image = array_merge($image, $this->ConfigAttachment->getCoordinates($data['ConfigAttachment']['file_name']));

		$this->Session->write('DashboardId', $id);
		$this->set('image', $image);
		$this->set('id', $id);
		$this->render('/Config/dashboard/view');
	}
	
	public function dashboardEdit() {
		$this->Navigation->addCrumb('System Configurations', array('controller' => 'Config', 'action' => 'index'));
		$this->Navigation->addCrumb('Dashboard', array('controller' => 'Config', 'action' => 'index', 'Dashboard'));
		$this->Navigation->addCrumb('Edit');

		$id = empty($this->params['pass'][0])? 0:$this->params['pass'][0];

		if($this->request->is('post')) { // save
			$requestData = $this->data;
			$isEdited = false;
			pr($requestData);
			if($this->ConfigAttachment->save($requestData)){
				$isEdited = $this->ConfigAttachment->updateAttachmentCoord($requestData['ConfigItem']['id'],$requestData['ConfigItem']['x'], $requestData['ConfigItem']['y']);
			}
			if($isEdited){
				$this->Utility->alert(__('File have been updated successfully.'));
				$this->redirect(array('action' => 'index', 'Dashboard'));
			}
		}else{
			$this->request->data = $this->ConfigAttachment->find('first',
			array('conditions' => array('ConfigAttachment.id' => $id)
			));
		}

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
		
		$this->set('id', $id);
		$this->set('imageConfig', $this->imageConfig);
		$this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
		$this->render('/Config/dashboard/edit');
	}
	
	public function dashboardAdd() {
		$this->Navigation->addCrumb('System Configurations', array('controller' => 'Config', 'action' => 'index'));
		$this->Navigation->addCrumb('Dashboard', array('controller' => 'Config', 'action' => 'index', 'Dashboard'));
		$this->set('params', $this->params->query);

		$model = $this->ConfigAttachment->modelName();
		$this->set('_model', $model);


		if($this->request->is('post')) { // save
			$requestData = $this->data;
			//$active = $requestData[$model][0]['default'];

			foreach ($requestData['ConfigAttachment'] as $key => $value) {
				$requestData['ConfigAttachment'][$key]['active'] = 0;	
				$requestData['ConfigAttachment'][$key]['type'] = 'dashboard';
				$requestData['ConfigAttachment'][$key]['visible'] = '1';
				$requestData['ConfigAttachment'][$key]['order'] = '0';
				$requestData['ConfigAttachment'][$key]['description'] = '';
			}

			$errors = (isset($_FILES['files']))?$this->vaildateImage($_FILES):array();

			if(sizeof($errors) == 0) $errors = array_merge($errors,$this->FileAttachment->saveAll($requestData, $_FILES, null));

			if(sizeof($errors) == 0) {
				$this->Utility->alert(__('Files have been saved successfully.'));
				$this->redirect(array('action' => 'index', 'Dashboard'));
			} else {
				$this->Utility->alert(__('Some errors have been encountered while saving files.'), array('type' => 'error'));
			}
			
		}
		$this->render('/Config/dashboard/add');
	}
	
	public function dashboardDelete(){
		if($this->Session->check('DashboardId')) {
			$id = $this->Session->read('DashboardId');
			if($this->ConfigAttachment->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete('DashboardId');
			return $this->redirect(array('action' => 'index'));
		}
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