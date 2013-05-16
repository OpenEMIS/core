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

class FileAttachmentComponent extends Component {
	private $controller;
	private $model;
	private $foreignKey;
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->init();
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) { }
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) { }
	
	//called after Controller::render()
	public function shutdown(Controller $controller) { }
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }
	
	public function init() {
		$this->model = ClassRegistry::init($this->settings['model']);
		$this->foreignKey = (isset($this->settings['foreignKey']) && !is_null($this->settings['foreignKey']))?$this->settings['foreignKey']:null;
		$this->controller->set('_model', $this->model->alias);
	}
	private function fixBlankFile(&$data){
		foreach($data as &$arrDataVal){
			foreach($arrDataVal as $key => &$arrModule){
				foreach($arrModule as $key => &$val){
					if($key == 'name' && trim($val) == '') {
						$name = @pathinfo($arrModule['file_name'], PATHINFO_FILENAME);
						$arrModule['name'] = $name;
					}
				}
			}
		}
	}
	public function getList($id) {
		$modelClass = $this->model->alias;
		$data = $this->model->find('all', array(
			'conditions' => array($modelClass . '.visible' => 1, $modelClass . '.' . $this->foreignKey => $id)
		));
		$this->fixBlankFile($data);
		return $data;
	}
	
	public function saveAll($data, $fileArray, $foreignKeyValue) {
		
		if(empty($fileArray) && empty($data)) return array('error');
		$errors = array();
		$model = $this->model->alias;		
		$key = (isset($this->settings['foreignKey']) && !is_null($this->settings['foreignKey']))?$this->settings['foreignKey']:null;
		
		if(isset($fileArray['files'])) {
			$files = $fileArray['files']['error'];
			foreach($files as $i => $status) {
				$tmpName = $fileArray['files']['tmp_name'][$i];
				if($status == UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
					$name = pathinfo($fileArray['files']['name'][$i], PATHINFO_FILENAME);
					$blob = file_get_contents($tmpName);
					$data[$model][$i]['name']  = (!isset($data[$model][$i]['name']) || is_null($data[$model][$i]['name']) || strlen(trim($data[$model][$i]['name']))==0) ? $name : trim($data[$model][$i]['name']);
					if(!is_null($foreignKeyValue)) {
						$data[$model][$i]['file_name'] = $fileArray['files']['name'][$i];
						$data[$model][$i][$key] = $foreignKeyValue;
					}else{
						$fileext = pathinfo($fileArray['files']['name'][$i], PATHINFO_EXTENSION);
						$width = $fileArray['files']['resolution'][$i]['width'];
						$height = $fileArray['files']['resolution'][$i]['height'];
						$data[$model][$i]['file_name'] = time()."_0_0_{$width}_{$height}.{$fileext}";
					}
					$data[$model][$i]['file_content'] = $blob;
				} else {
					$errors[] = __('error'); // add some meaningful messages
				}
			}
		}
		
		if(sizeof($errors) == 0) {
			$this->model->saveAll($data[$model]);
		}
		
		return $errors;
	}
	
	public function delete($id) {
		return $this->model->delete($id);
	}
	
	public function download($id) {
		
		$this->controller->autoRender= false;
        $file = $this->model->findById($id);
        $fileext = pathinfo($file[$this->model->alias]['file_name'], PATHINFO_EXTENSION);
        $filename = pathinfo($file[$this->model->alias]['file_name'], PATHINFO_FILENAME);
		$filenameOut = ($file[$this->model->alias]['name'] == '' ? $filename : $file[$this->model->alias]['name']);
        header('Content-type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        //header('Content-length: ' . $file[$this->model->alias]['blobsize']);
		//header('Content-length: ' . mb_strlen($file[$this->model->alias]['file_content']));//$file[$this->model->alias]['blobsize']);
        header('Content-Disposition: attachment;  filename='.str_replace(" ","_",$filenameOut).'.'.$fileext);
        echo $file[$this->model->alias]['file_content'];
	}
}
?>
