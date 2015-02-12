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
	public $Area;
	public $AreaLevel;
    public $AreaAdministrative;
    public $AreaAdministrativeLevel;
	
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
        $this->AreaAdministrative = ClassRegistry::init('AreaAdministrative');
        $this->AreaAdministrativeLevel = ClassRegistry::init('AreaAdministrativeLevel');
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {}
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {}
	
	//called after Controller::render()
	public function shutdown(Controller $controller) {}
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}

    public function getAreaPaths($id){
        $parents = $this->Area->getPath($id);
        return $parents;
    }
	
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
        if(!is_array($arrMap)){
            $arrMap = ($arrMap == 'education')?  array('AreaAdministrative','AreaAdministrativeLevel') : array('Area','AreaLevel');
        }
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

    public function checkAreaExist($id, $arrMap = array('Area','AreaLevel')){
        $arr = array_keys($this->{$arrMap[0]}->find('list',array('conditions' => array('id' => $id))));
        $myid = '';
        if(is_array($arr)){
            $myid = $arr[0];
        }

        return $myid;
    }

    public function getTopArea($arrMap = array('Area','AreaLevel')){
        $arr = array_keys($this->{$arrMap[0]}->find('list',array('recursive'=>0, 'conditions' => array('parent_id' => '-1'))));
        $id = $arr[0];
        return $id;
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

                    } while ($list[$arrMap[0]][$AreaLevelfk.'_id'] != 1 && is_array($list));
                }
            }
        }
        return $arrVals;
    }

    public function getAllSiteAreaToParent($siteId,$arrMap = array('Area','AreaLevel'), $filterArr = array()) {
        $AreaLevelfk = Inflector::underscore($arrMap[1]);
        $lowest =  $siteId;

        $areas = $this->getAreatoParent($lowest,$arrMap);
        $areas = array_reverse($areas);

        $arrDisabledList = array();
        foreach($areas as $index => &$arrVals){

            $siblings = $this->{$arrMap[0]}->find('all',array('fields'=>Array($arrMap[0].'.id',$arrMap[0].'.name',$arrMap[0].'.parent_id',$arrMap[0].'.visible'),'conditions'=>array($arrMap[0].'.parent_id' => $arrVals['parent_id'])));

            ($arrVals['parent_id']!=-1)?  $opt =  array('0'=>'--'.__('Select').'--'): ''; // No select for top tier

            foreach($siblings as &$sibVal){

                $arrDisabledList[$sibVal[$arrMap[0]]['id']] = array('parent_id'=>$sibVal[$arrMap[0]]['parent_id'],'id'=>$sibVal[$arrMap[0]]['id'],'name'=>$sibVal[$arrMap[0]]['name'],'visible'=>$sibVal[$arrMap[0]]['visible']);

                if(isset($arrDisabledList[$sibVal[$arrMap[0]]['parent_id']])){
                    if($arrDisabledList[$sibVal[$arrMap[0]]['parent_id']]['visible'] == 0){
                        $sibVal[$arrMap[0]]['visible'] = 0;
                        $arrDisabledList[$sibVal[$arrMap[0]]['id']]['visible'] = 0;
                    }
                }
            }
            foreach($siblings as $sibVal2){
                $o = array('name'=>$sibVal2[$arrMap[0]]['name'],'value'=>$sibVal2[$arrMap[0]]['id']);

                if($sibVal2[$arrMap[0]]['visible'] == 0){
                    $o['disabled'] = 'disabled';

                }
                $opt[] = $o;
            }
            $colInfo[$AreaLevelfk.'_'.$index]['options'] = $opt;
        }
        if(count($areas)>0){
            $maxAreaIndex = max(array_keys($areas));//starts with 0
        }else{
            $maxAreaIndex = 0;
        }

        $totalAreaLevel = $this->AreaLevel->find('count'); //starts with 1
        for($i = $maxAreaIndex; $i < $totalAreaLevel;$i++ ){
            $colInfo[$AreaLevelfk.'_'.($i+1)]['options'] = array('0'=>'--'.__('Select').'--');
        }

        return $colInfo;
    }

    public function searchChildren($userAreas, &$options, &$parentAreaIsAuthorised, $parentId, $dataType) {
        foreach($userAreas as $id=>$ua) {
            // To reset $parentAreaIsAuthorised array if a new branch is being search
            if (isset($ua['area_level_id'])) {
                foreach ($parentAreaIsAuthorised as $i => $value) {
                    if ($i == $ua['area_level_id'] || $i > $ua['area_level_id']) {
                        $parentAreaIsAuthorised = array();
                    }
                }
            }
            // Save to $parentAreaIsAuthorised array by area level if current parent is authorised. The allAreas data will be used instead of selected few.
            if(isset($ua['isAuthorised'])) {
                $parentAreaIsAuthorised[$ua['area_level_id']] = $id;
            }
            // When parent found, get children as options and break iteration
            if ($id == $parentId) {
                if (isset($ua['children'])) {
                    foreach($ua['children'] as $child) {
                        if($dataType=='all'){
                            $options[$child['area_id']] = array(
                                'Area'=>array(
                                    'id'=>$child['area_id'],
                                    'name'=>$child['area_name'],
                                ),
                                'AreaLevel'=>array(
                                    'id'=>$child['area_level_id'],
                                    'name'=>$child['area_level_name'],
                                ),
                            );
                        } else {
                            $options[$child['area_id']] = $child['area_name'];
                        }
                    }
                } else {
                    // If parent has no children, set options as 'last' to break iteration totally since the required parent is found
                    $options['last'] = true;
                }
                break;
    
            // if current node id is lesser than the required parentId, search deeper in the branch else go to the next branch
            } elseif ($id < $parentId) {
                if(isset($ua['children'])) {
                    $results = $this->searchChildren($ua['children'], $options, $parentAreaIsAuthorised, $parentId, $dataType);
                    $options = $results['options'];
                    $parentAreaIsAuthorised = $results['parentAreaIsAuthorised'];
                    if (count($options)>0) {
                        //if options has children, break iteration
                        break;
                    }
                } else {
                    // If parent has no children, break iteration to go to the next branch
                    break;
                }
            }
        }
        return array('options'=>$options, 'parentAreaIsAuthorised'=>$parentAreaIsAuthorised);
    }

    public function getChildren($params=array()) {
        if(isset($params['model'])){
            $model = $params['model'];
        } else {
            $model = 'Area';
        }
        if(isset($params['parentId'])){
            $parentId = $params['parentId'];
        } else {
            $parentId = -1;
        }
        if(isset($params['dataType'])){
            $dataType = $params['dataType'];
        } else {
            $dataType = 'list';
        }

        // original search
        $allAreas = $this->{$model}->find($dataType, array(
                        'conditions' => array(
                            $model.'.parent_id' => $parentId,
                            $model.'.visible' => 1
                        ),
                        'order' => array($model.'.order')
                    ));

        if($model=='Area'){
            $userAreas = $this->getUserAreas(array('getWithParents' => true));
            $options = array();
            $parentAreaIsAuthorised = array();
            if(count($userAreas)>0) {
                //search for area children based on user's authorised area tree
                $results = $this->searchChildren($userAreas, $options, $parentAreaIsAuthorised, $parentId, $dataType);
                $options = $results['options'];
                $parentAreaIsAuthorised = $results['parentAreaIsAuthorised'];
            }
            if (count($parentAreaIsAuthorised)>0) {
                return $allAreas;
            } else {
                return $options;
            }
        }else{
            return $allAreas;
        }
    }

    public function getDefaultAreaId($model){
        $areaId = null;
        if($model=='Area'){
            $userAreas = $this->getUserAreas(array('getWithParents' => false));
            if(count($userAreas)>0){
                foreach($userAreas as $ua){
                    $areaId = !$areaId ? $ua['area_id'] : (($ua['area_id']<$areaId) ? $ua['area_id'] : $areaId);
                }
            }
        }
        return $areaId;
    }

    protected function getUserAreas($params=array()){
        //$params = array('getWithParents' => true);
        
        $SecurityGroupUser = ClassRegistry::init('SecurityGroupUser');
        $SecurityGroupArea = ClassRegistry::init('SecurityGroupArea');
        if(AuthComponent::user('id')){
            $userId = AuthComponent::user('id');
        }else{
            $userId = 0;
        }
        $groupId = $SecurityGroupUser->getGroupIdsByUserId($userId);
        if(isset($params['getWithParents']) && $params['getWithParents']){
            return $SecurityGroupArea->getAreasWithParents($groupId);
        } else {
            return $SecurityGroupArea->getAreas($groupId);
        }
    }
}
?>
