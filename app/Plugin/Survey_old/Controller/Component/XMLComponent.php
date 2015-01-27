<?php
App::uses('Component', 'Controller');
class XMLComponent extends Component {
	
	private $controller;
	public $Options;
	private $writer; 
	private $surveyPath = '';
	
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
		$this->initPath();
	}
	
	private function initPath(){
		$this->surveyPath = WWW_ROOT.'survey/';
	}
	public function createXMLFile($filename)
	{
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
		$file = new File($this->surveyPath.DS.$filename, true, 0755);
	}
	public function openXMLFile($fileName)
	{
		$this->writer->openURI($this->surveyPath.$fileName);
	}
	
	public function openXMLSpecInfo($xmlVersion, $lang, $indent)
	{
		$this->writer->startDocument($xmlVersion,$lang);
		$this->writer->setIndent($indent);
	}
	
	public function openXMLSurveyHead($origin,$user)
	{
		// Main elements in XML
		$this->writer->startElement('survey');
		$this->writer->writeAttribute('version','1.0');
		// Child elements in XML
		$this->writer->writeElement('date',date("Y-m-d"));
		$this->writer->writeElement('time',date("h:i"));
		$this->writer->writeElement('origin',$origin);
		$this->writer->writeElement('user',$user);
	}
	
	public function openXMLSurveyBody()
	{
		// Main elements in XML
		$this->writer->startElement('question');
		// Child elements in XML
		$this->writer->StartElement('input'); 
		$this->writer->WriteAttribute('type', 'text'); 
		$this->writer->WriteAttribute('name', 'code');  
		$this->writer->WriteAttribute('hidden', 'true'); 
		$this->writer->endElement();
	}
	
	public function openXMLSurveyQuestion($arrContent)
	{
		// Main elements in XML
		$this->writer->startElement('table');
		$this->writer->WriteAttribute('type', 'institutions'); 
		
		for($i = 1; $i <= $arrContent['maxCount1']; ++$i){
			if($arrContent['checkQuestion'.$i]){
				// Child elements in XML
				$this->writer->StartElement('column'); 
				$this->writer->WriteAttribute('name', $arrContent['columnName'.$i]); 
				$this->writer->WriteAttribute('type', $arrContent['columnType'.$i]);  
				$this->writer->writeElement('label',$arrContent['columnLabel'.$i]);
				for($j = 1; $j <= $arrContent['maxCountItem'.$i]; ++$j){
					$this->writer->writeElement('item',$arrContent['column'.$i.'Item'.$j]);
				}
				$this->writer->writeElement('value','');
				$this->writer->endElement();
			}
		}
		$this->writer->endElement();
	}
	
	public function closeXMLElement()
	{
		$this->writer->endElement();
		$this->writer->endElement();
		$this->writer->endDocument();
		$this->writer->flush();
	}
	
    public function doXMLProcess($arrContent) {
		$fileName = $arrContent['year'].'_'.$arrContent['siteTypes'].'_'.date("YmdHis").'.xml';
		$this->createXMLFile($fileName);
		$this->openXMLFile($fileName);
		$this->openXMLSpecInfo('1.0', 'UTF-8', 4);
		$this->openXMLSurveyHead('SurveyProg v1.3.05','user site');
		$this->openXMLSurveyBody();
		$this->openXMLSurveyQuestion($arrContent);
		$this->closeXMLElement();
    }
}
?>