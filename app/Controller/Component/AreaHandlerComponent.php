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

class AreaHandlerComponent extends Component {
	private $controller;
	private $Area;
	private $AreaLevel;
    private $AreaEducation;
    private $AreaEducationLevel;
	
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
        $this->AreaEducation = ClassRegistry::init('AreaEducation');
        $this->AreaEducationLevel = ClassRegistry::init('AreaEducationLevel');
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

    public function getAreaLevel($id,$arrMap = array('Area','AreaLevel')) {
        $arrMap = ($arrMap == 'admin')?  array('AreaEducation','AreaEducationLevel') : array('Area','AreaLevel') ;
        $AreaLevelfk = Inflector::underscore($arrMap[1]);
        $this->autoRender = false;

        $levelname = $this->{$arrMap[0]}->find('all', array(
            'contain' => array($arrMap[1]),
            'conditions' => array(
                $arrMap[0].'.id' => $id
            ),
            'fields' => array($arrMap[1].'.name')
        ));
        if (array_key_exists(0, $levelname)) {
            return $levelname[0][$arrMap[1]]['name'];
        }else{
            return '';
        }
    }

	public function getAreaList($arrMap = array('Area','AreaLevel')){
		return $this->{$arrMap[1]}->find('list',array('recursive'=>0));
	}

    public function getAreatoParent($lowest, $arrMap = array('Area','AreaLevel')){
        $AreaLevelfk = Inflector::underscore($arrMap[1]);
        $arrVals = Array();

        $this->{$arrMap[0]}->formatResult = false;
        $list = $this->{$arrMap[0]}->find('first', array(
            'fields' => array($arrMap[0].'.id', $arrMap[0].'.name', $arrMap[0].'.parent_id', $arrMap[0].'.'.$AreaLevelfk.'_id',$arrMap[1].'.name'),
            'conditions' => array($arrMap[0].'.id' => $lowest)));

        //check if not false
        if($list){
            $arrVals[$list[$arrMap[0]][$AreaLevelfk.'_id']] = Array('level_id'=>$list[$arrMap[0]][$AreaLevelfk.'_id'],'id'=>$list[$arrMap[0]]['id'],'name'=>$list[$arrMap[0]]['name'],'parent_id'=>$list[$arrMap[0]]['parent_id'],'AreaLevelName'=>$list[$arrMap[1]]['name']);

            if($list[$arrMap[0]][$AreaLevelfk.'_id'] > 1){
                if($list[$arrMap[0]][$AreaLevelfk.'_id']){
                    do {
                        $list = $this->{$arrMap[0]}->find('first', array(
                            'fields' => array($arrMap[0].'.id', $arrMap[0].'.name', $arrMap[0].'.parent_id', $arrMap[0].'.'.$AreaLevelfk.'_id',$arrMap[1].'.name', $arrMap[0].'.visible'),
                            'conditions' => array($arrMap[0].'.id' => $list[$arrMap[0]]['parent_id'])));
                        $arrVals[$list[$arrMap[0]][$AreaLevelfk.'_id']] = Array('visible'=>$list[$arrMap[0]]['visible'],'level_id'=>$list[$arrMap[0]][$AreaLevelfk.'_id'],'id'=>$list[$arrMap[0]]['id'],'name'=>$list[$arrMap[0]]['name'],'parent_id'=>$list[$arrMap[0]]['parent_id'],'AreaLevelName'=>$list[$arrMap[1]]['name']);
                    } while ($list[$arrMap[0]][$AreaLevelfk.'_id'] != 1);
                }
            }

        }

        return $arrVals;
    }

    public function getAllSiteAreaToParent($siteId,$arrMap = array('Area','AreaLevel')) {
        $AreaLevelfk = Inflector::underscore($arrMap[1]);

        $lowest =  $siteId;

        $areas = $this->getAreatoParent($lowest,$arrMap);

        $areas = array_reverse($areas);

        /*foreach($areas as $index => &$arrVals){
            $siblings = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => $arrVals['parent_id'])));
            $this->Utility->unshiftArray($siblings,array('0'=>'--'.__('Select').'--'));
            pr($siblings);
            $colInfo['area_level_'.$index]['options'] = $siblings;
        }*/
        $arrDisabledList = array();
        foreach($areas as $index => &$arrVals){

            $siblings = $this->{$arrMap[0]}->find('all',array('fields'=>Array($arrMap[0].'.id',$arrMap[0].'.name',$arrMap[0].'.parent_id',$arrMap[0].'.visible'),'conditions'=>array($arrMap[0].'.parent_id' => $arrVals['parent_id'])));
            //echo "<br>";

            $opt =  array('0'=>'--'.__('Select').'--');
            foreach($siblings as &$sibVal){

                $arrDisabledList[$sibVal[$arrMap[0]]['id']] = array('parent_id'=>$sibVal[$arrMap[0]]['parent_id'],'id'=>$sibVal[$arrMap[0]]['id'],'name'=>$sibVal[$arrMap[0]]['name'],'visible'=>$sibVal[$arrMap[0]]['visible']);

                if(isset($arrDisabledList[$sibVal[$arrMap[0]]['parent_id']])){

                    //echo $sibVal['Area']['name']. ' '.$arrDisabledList[$sibVal['Area']['parent_id']]['visible'].' <br>';
                    if($arrDisabledList[$sibVal[$arrMap[0]]['parent_id']]['visible'] == 0){
                        $sibVal[$arrMap[0]]['visible'] = 0;
                        $arrDisabledList[$sibVal[$arrMap[0]]['id']]['visible'] = 0;
                    }

                }
            }
            //pr($arrDisabledList);
            foreach($siblings as $sibVal2){
                $o = array('name'=>$sibVal2[$arrMap[0]]['name'],'value'=>$sibVal2[$arrMap[0]]['id']);

                if($sibVal2[$arrMap[0]]['visible'] == 0){
                    $o['disabled'] = 'disabled';

                }
                $opt[] = $o;
            }



            //pr($opt);

            $colInfo[$AreaLevelfk.'_'.$index]['options'] = $opt;
        }

        $maxAreaIndex = max(array_keys($areas));//starts with 0
        $totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
        for($i = $maxAreaIndex; $i < $totalAreaLevel;$i++ ){
            $colInfo[$AreaLevelfk.'_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
        }

        return $colInfo;
    }
}
?>
