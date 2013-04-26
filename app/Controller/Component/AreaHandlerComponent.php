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
	
	
	public function getAreaList(){
		return $this->AreaLevel->find('list',array('recursive'=>0));
	}
	
	public function getAreatoParent($lowest){
        $arrVals = Array();
        $list = $this->Area->find('first', array(
                                'fields' => array('Area.id', 'Area.name', 'Area.parent_id', 'Area.area_level_id','AreaLevel.name'),
                                'conditions' => array('Area.id' => $lowest)));
    
        //check if not false
        if($list){
            $arrVals[$list['Area']['area_level_id']] = Array('level_id'=>$list['Area']['area_level_id'],'id'=>$list['Area']['id'],'name'=>$list['Area']['name'],'parent_id'=>$list['Area']['parent_id'],'AreaLevelName'=>$list['AreaLevel']['name']);
            if($list['Area']['area_level_id'] > 1){
                if($list['Area']['area_level_id']){
                    do {
                        $list = $this->Area->find('first', array(
                                'fields' => array('Area.id', 'Area.name', 'Area.parent_id', 'Area.area_level_id','AreaLevel.name', 'Area.visible'),
                                'conditions' => array('Area.id' => $list['Area']['parent_id'])));
                        $arrVals[$list['Area']['area_level_id']] = Array('visible'=>$list['Area']['visible'],'level_id'=>$list['Area']['area_level_id'],'id'=>$list['Area']['id'],'name'=>$list['Area']['name'],'parent_id'=>$list['Area']['parent_id'],'AreaLevelName'=>$list['AreaLevel']['name']);
                    } while ($list['Area']['area_level_id'] != 1);
                }
            }
        }
        return $arrVals;
    }
	
	public function getAllSiteAreaToParent($siteId,$options=array()) {
		$lowest = ($siteId<1) ? 1 : $siteId;
        $areas = $this->getAreatoParent($lowest);
        $areas = array_reverse($areas);

        $arrDisabledList = array();
        foreach($areas as $index => &$arrVals){
            $siblings = $this->Area->find('all',array('fields'=>Array('Area.id','Area.name','Area.parent_id','Area.visible'),'conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
            $opt = array();
            foreach($siblings as &$sibVal){                 
				 $arrDisabledList[$sibVal['Area']['id']] = array('parent_id'=>$sibVal['Area']['parent_id'],'id'=>$sibVal['Area']['id'],'name'=>$sibVal['Area']['name'],'visible'=>$sibVal['Area']['visible']);               
				 if(isset($arrDisabledList[$sibVal['Area']['parent_id']])){
					if($arrDisabledList[$sibVal['Area']['parent_id']]['visible'] == 0){
						$sibVal['Area']['visible'] = 0;
						$arrDisabledList[$sibVal['Area']['id']]['visible'] = 0;
					}
					 
				 }
            }
            foreach($siblings as $sibVal2){
                $o = array('name'=>$sibVal2['Area']['name'],'value'=>$sibVal2['Area']['id']);
                if($sibVal2['Area']['visible'] == 0){
                    $o['disabled'] = 'disabled';
                }
                $opt[] = $o;
            }
            $colInfo['area_level_'.$index]['options'] = $opt;
        }
		
		if (isset($options['empty_arealevel_placeholder'])) {
			if ($siteId<1) { //don't force selection of country 3
				array_unshift($colInfo['area_level_0']['options'] , array('0'=>$options['empty_arealevel_placeholder']));
			}
			$maxAreaIndex = (sizeof($areas)==0) ? -1 : max(array_keys($areas));//starts with 0
			$totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
			for($i = $maxAreaIndex; $i < $totalAreaLevel;$i++ ){
				$colInfo['area_level_'.($i+1)]['options'] = array('0'=>$options['empty_arealevel_placeholder']);
			}
		}
        return $colInfo;
    }
	
}
?>
