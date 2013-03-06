<?php
class AreaHandlerComponent extends Component {
	private $controller;
	private $Area;
	private $AreaLevel;
	
	//public $components = array('Auth', 'Session');
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->init();
	}
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
	}
	
	public function init() {
		$this->Area = ClassRegistry::init('Area');
		$this->AreaLevel = ClassRegistry::init('AreaLevel');
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {}
	
	//called after Controller::render()
	public function shutdown(Controller $controller) {}
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}
	
	public function getAreasByParent(&$areaList, $parentId, $list=true, $visible=true, $recursive=true) {
		$options = array();
		$areas = array();
		$fields = array('Area.id', 'Area.parent_id');
		$conditions = array();
		if(!$list) {
			$fields = array_merge($fields, array('Area.code', 'Area.name', 'Area.area_level_id'));
		}
		
		if($visible) {
			$conditions['Area.visible'] = 1;
		}
		$conditions['Area.parent_id'] = $parentId;
		$options['recursive'] = 0;
		$options['fields'] = $fields;
		$options['conditions'] = $conditions;
		//$options['order'] = array('Area.order');
		$this->Area->formatResult = true;
		$this->Area->unbindModel(array('belongsTo' => array('AreaLevel')));
		$areas = $this->Area->find($list ? 'list' : 'all', $options);
		
		foreach($areas as $key => $value) {
			$parentId = $list ? $key : $value['id'];
			$areaList[$parentId] = $value;
			if($recursive) {
				$this->getAreasByParent($areaList, $parentId, $list, $visible, $recursive);
			}
		}
	}
}
?>
