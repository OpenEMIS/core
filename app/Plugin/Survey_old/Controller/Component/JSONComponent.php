<?php
App::uses('Component', 'Controller');
class JSONComponent extends Component {
	
	private $controller;
	private $surveyPath = '';
	private $filename = '';
	private $fileHandle = '';

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
		$this->writer = new XMLWriter;
		if($this->surveyPath == ''){
			$this->initPath();
		}
	}
	
	public function setPath($path){
		$this->surveyPath = $path;
	}
	
	private function initPath(){
		$this->surveyPath = WWW_ROOT.'survey/';
	}
	public function prepareJSONFile($filename){
		$this->filename = str_replace(' ', '_', $filename);
		$create = false;
		$perm = false;
		if(!is_dir($this->surveyPath)){
			$create =  true;
			$perm = 0755;
		}
		$dir = new Folder($this->surveyPath,$create,$perm);
		$arr = $dir->find('.'.$filename);
		if(!empty($arr)){
			unlink($this->surveyPath.DS.$filename);
		}
		$fileHandle = new File($this->surveyPath.DS.$filename, true, 0755);
	}
	
	public function createJSONFile($array){
		$fileHandle = new File($this->surveyPath.DS.$this->filename, true, 0755);
		$content =  json_encode($array, JSON_FORCE_OBJECT);
		$fileHandle->write($content);
		$fileHandle->close(); // Be sure to close the file when you're done

        $dir = new Folder($this->surveyPath.DS);
        $files = $dir->find('.*\.json');
        foreach ($files as $file) {
            $file = new File($dir->pwd() . DS . $file);
            $contents = $file->read();
            if(!$file->size()>0){
                $file->delete();
            }
            $file->close(); // Be sure to close the file when you're done
        }
	}
	
	public function getJSONFile($filename){
		$fileHandle = new File($this->surveyPath.DS.$filename, true, 0755);
		return $fileHandle->read();
	}
}
?>