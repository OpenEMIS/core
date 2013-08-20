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

class AreasController extends AppController {
	public $uses = array('Area', 'AreaLevel','AreaEducation', 'AreaEducationLevel');

    /**
     * Created by: Eugene Wong
     * @return [type] [description]
     */
    public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Settings';
		$this->Navigation->addCrumb('Settings', array('controller' => 'Setup', 'action' => 'index'));
		$this->Navigation->addCrumb('Administrative Boundaries', array('controller' => 'Areas', 'action' => 'index'));
    }
	
    /**
     * Updated by: Eugene
     * @return [type] [description]
     */
	public function index() {
		$this->Navigation->addCrumb('Areas');

		$areas = array();
        $levels = $this->AreaLevel->find('list', array('order'=>array('AreaLevel.level ASC')));
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1')));
        $this->unshift_array($topArea, array('0'=>__('--Select--')));
        $areas[] = $topArea;


        if($this->request->is('post')) {
            if(isset($this->request->data['Area'])){
                for ($i = 0; $i < count($this->request->data['Area'])-1; $i++) {
                    $area = $this->Area->find('list',array(
                                                    'conditions' => array(
                                                        'Area.parent_id' => $this->request->data['Area']['area_level_'.$i]
                                                    )
                                                )
                                            );
                    
                    $this->unshift_array($area, array('0'=>__('--Select--')));
                    $areas[] = $area;
                }
                if(end($this->request->data['Area']) == 0 ){
                    array_pop($this->request->data['Area']);
                }
            }
            $this->set('initAreaSelection', (isset($this->request->data['Area']) && count($this->request->data['Area']) > 0)?$this->request->data['Area']: null);

        }

        if(count($topArea)<2) $this->Utility->alert($this->Utility->getMessage('AREAS_NO_AREA_LEVEL'));

		// Checking if user has access to _view for area levels
		$_view_levels = false;
		if($this->AccessControl->check($this->params['controller'], 'levels')) {
			$_view_levels = true;
		}
		$this->set('_view_levels', $_view_levels);
		// End Access Control
        $this->set('topArea', $topArea);
		$this->set('levels', $levels);
        $this->set('highestLevel',$areas);	
	}

    /**
     * Create by: Eugene Wong
     * @return [type] [description]
     */
    public function edit() {
		$this->Navigation->addCrumb('Edit Areas');
		
        $areas = array();
        $levels = $this->AreaLevel->find('list');
        $topArea = $this->Area->find('list',array('conditions'=>array('Area.parent_id' => '-1')));
        $this->unshift_array($topArea, array('0'=>__('--Select--')));
        $areas[] = $topArea;

        if($this->request->is('post')) {
            if(isset($this->request->data['Area'])){
                for ($i = 0; $i < count($this->request->data['Area'])-1; $i++) {
                    $area = $this->Area->find('list',array(
                            'conditions'=> array(
                                'Area.parent_id' => $this->request->data['Area']['area_level_'.$i]
                            )
                        )
                    );
                    
                    $this->unshift_array($area, array('0'=>__('--Select--')));
                    $areas[] = $area;
                }
                
            }

            if(isset($this->request->data['Area']) &&  end($this->request->data['Area']) == 0 ){
                array_pop($this->request->data['Area']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['Area']))?$this->request->data['Area']: null);

        }

        $this->set('levels', $levels);
        $this->set('highestLevel',$areas);
    }

    /**
     * Created by Eugene Wong
     * @return [type] [description]
     */
    public function levels() {
		$this->Navigation->addCrumb('Area Levels');

        $areas = array();
        $levels = $this->AreaLevel->find('all', array(
            'fields' => array('AreaLevel.id', 'AreaLevel.name', 'AreaLevel.level'),
            'order' => array('AreaLevel.level ASC'),
            'recursive' => 0, //int
        ));

        $this->set('levels', $this->Utility->formatResult($levels));
  //       $this->set('highestLevel',$areas);  
        
        $this->render('/AreaLevels/index');
    }

    /**
     * Created by Eugene Wong
     * @return [type] [description]
     */
	public function levelsEdit() {
		$this->Navigation->addCrumb('Edit Area Levels');

		$areas = array();
        $levels = $this->AreaLevel->find('all', array(
            'fields' => array('AreaLevel.id', 'AreaLevel.name', 'AreaLevel.level'),
            'order' => array('AreaLevel.level ASC'),
            'recursive' => 0, //int
        ));

        if($this->request->is('post')) {
            $this->AreaLevel->saveAreaLevelData($this->data['AreaLevel']);
            $this->redirect('/Areas/levels');
            // pr($this->data);
        }

        $this->set('levels', $this->Utility->formatResult($levels));
  //       $this->set('highestLevel',$areas);  
        
        $this->render('/AreaLevels/edit');

	}

    /**
     * Created by: Eugene Wong
     * @param  integer $parentId Find the next children levels data by parent id.
     * @return json            
     */
    public function viewAreaChildren($id,$arrMap = array('Area','AreaLevel')) {
        $arrMap = ($arrMap == 'education')?  array('AreaEducation','AreaEducationLevel') : array('Area','AreaLevel');
        $AreaLevelfk = Inflector::underscore($arrMap[1]);

        $this->autoRender = false;
        $value =$this->{$arrMap[0]}->find('list',array('conditions'=>array($arrMap[0].'.parent_id' => $id,$arrMap[0].'.visible' => 1)));
        $this->Utility->unshiftArray($value, array('0'=>'--'.__('Select').'--'));
        echo json_encode($value);
    }

    public function checkLowestLevel($id,$arrMap = array('Area','AreaLevel')) {
        if(!is_array($arrMap)){
            $arrMap = ($arrMap == 'education')?  array('AreaEducation','AreaEducationLevel') : array('Area','AreaLevel');
        }

        $this->autoRender = false;
        $fkAreaLevel = Inflector::underscore($arrMap[1]);
        $area_table_name = Inflector::tableize($arrMap[0]);;
        $area_level_table_name = Inflector::tableize($arrMap[1]);;

        $db = $this->{$arrMap[0]}->getDataSource();

        $query = "SELECT `$area_table_name`.`".$fkAreaLevel."_id`
                    FROM `$area_table_name`
                    WHERE `$area_table_name`.`id` = ?
                            AND `$area_table_name`.`".$fkAreaLevel."_id` = (
                            SELECT MAX(`$area_level_table_name`.`level`) as `".$fkAreaLevel."_id`
                                FROM `$area_level_table_name`
                  )";

        $listAreaLevels = $db->fetchAll($query, array($id));

        if(count($listAreaLevels)<1){
            echo 'false';
        }else{
            echo 'true';
        }
    }

    public function getAreaLevel($id,$arrMap = array('Area','AreaLevel')) {
        if(!is_array($arrMap)){
            $arrMap = ($arrMap == 'education')?  array('AreaEducation','AreaEducationLevel') : array('Area','AreaLevel');
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
            echo $levelname[0][$arrMap[1]]['name'];
        }else{
            echo '&nbsp;&nbsp;';
        }
    }
    
    /**
     * Created by: Eugene Wong
     * @param  integer $parentId         Find the next children levels data by parent id.
     * @param  integer $previousParentId Find the previous parent id.
     * @return json                    
     */
	public function viewData($parentId = 1, $arrModels = array('Area','AreaLevel')) {
		$request = @$this->request['pass'][0];
		if($request == 'Education' || $request == 'AreaEducation'){
			$arrModels = 'Education';
			
		}

        $parentId = ($parentId<1)? 1:$request;

		$arrModels = (($arrModels == 'Education'|| $arrModels == 'AreaEducation') && !is_array($arrModels)) ? array('AreaEducation','AreaEducationLevel') :  array('Area','AreaLevel') ;

		$this->autoRender = false;
		$area = $arrModels[0];
		$arealevel = $arrModels[1];
		$fkAreaLevel = Inflector::underscore($arrModels[1]);
		$area_table_name = Inflector::tableize($arrModels[0]);;
		$area_level_table_name = Inflector::tableize($arrModels[1]);;
		$this->{$area}->formatResult = false;
		$listAreas = $this->{$area}->find('all', array(
			'recursive' => 0,
	        'conditions' => array($arrModels[0].'.parent_id' => $parentId),
		    'fields' => array($area.'.id', $area.'.code', $area.'.name', $area.'.visible', $area.'.order', $area.'.'.$fkAreaLevel.'_id AS area_level_id', $arealevel.'.name AS level_name'),
            'order' => array($area.'.order ASC', $area.'.id ASC')
	    ));


        $db = $this->{$arealevel}->getDataSource();
		
        $query = "SELECT DISTINCT `$area_level_table_name`.`name`, `$area_level_table_name`.`id`
                        FROM  `$area_level_table_name` 
                        WHERE `$area_level_table_name`.`id` > (
                            SELECT `$area_table_name`.`".$fkAreaLevel."_id`
                            FROM `$area_table_name`
                            WHERE `$area_table_name`.`id` = ?
                        )";
		
        $listAreaLevels = $db->fetchAll($query, array($parentId));


        if(count($listAreaLevels)<1 && $parentId==1){
            $query = "SELECT DISTINCT `$area_level_table_name`.name, `$area_level_table_name`.`id`
                        FROM  `$area_level_table_name` order by id asc limit 1";
            $listAreaLevels = $db->fetchAll( $query, array($parentId));
        }
	    //$this->Area->formatResult($listAreas);

	    echo json_encode(array('data' => $this->Utility->formatResult($listAreas), 'area_levels' => $this->Utility->formatResult($listAreaLevels)));

	}

    /**
     * Created by: Eugene Wong
     * @return [type] [description]
     */
    public function areaAjax($type='Area') {
        $this->autoRender = false;
        $validateFailed = false; 
        //header("Status: 404 Not Found");
        $return = array();
        $return['data'] = array('new'=>array(),'error'=>array());
        foreach ($this->data as $key => $element) {
            $this->{$type}->set($element);
            if(!$this->{$type}->validates()) {

                // $this->Utility->setAjaxResult('error', $return);
                // $msg = $this->Utility->getFirstError($this->action, $this->Area, $this->Auth->user());
                // $return['msg'] = $msg;
                //pr('error');
                $return['data']['error'][$key+1] = $element;
                $validateFailed = true;

            } else {

                $result = $this->{$type}->save($element);
                // $$result['data']['new'][$key+1] = $result;
                
                if($result) {
                    $areaId = $result[$type]['id'];
                    $return['data']['new'][$key+1] = $areaId;
                    // $return['id'] = $subjectId;
                    // $return['name'] = $name;
                    // $return['msg'] = /*$name . */' has been added successfully.';
                    // $this->Utility->setAjaxResult('success', $return);
                }
            }
        }

        if(!$validateFailed){
            $return['msg'] = /*$name . */ __('Record have been added/updated successfully.');
            $this->Utility->setAjaxResult('success', $return);
        }else{
            $this->Utility->setAjaxResult('error', $return);
            $msg = $this->Utility->getFirstError($this->action, $this->{$type}, $this->Auth->user());
            $return['msg'] = __($msg);
        }
        //$return['data']= $this->data;
        echo json_encode($return);
        // $keys = $this->Area->saveAreaData($this->data);
        //throw new NotFoundException('Could not find that post');
    }

    /**
     * Created by: Eugene Wong
     * @param  [type] $origArray [description]
     * @param  array  $newArray  [description]
     * @return [type]            [description]
     */
    private function unshift_array(&$origArray,$newArray = array()){
        $tmpArray = array(); 
        foreach($newArray as $key => $val){
            $tmpArray[$key] = $val;
        }
        foreach($origArray as $key => $val){
            $tmpArray[$key] = $val;
        }
        $origArray = $tmpArray;
    }
	//AREA 2
	
    public function AreaEducationLevels() {
		$this->Navigation->addCrumb('Area Levels (Education');

        $areas = array();
        $levels = $this->AreaEducationLevel->find('all', array(
            'fields' => array('AreaEducationLevel.id', 'AreaEducationLevel.name', 'AreaEducationLevel.level'),
            'order' => array('AreaEducationLevel.level ASC'),
            'recursive' => 0, //int
        ));

        $this->set('levels', $this->Utility->formatResult($levels));
  //       $this->set('highestLevel',$areas);  
        
        $this->render('/AreaEducationLevels/index');
    }

    
	public function AreaEducationLevelsEdit() {
		$this->Navigation->addCrumb('Edit Area Levels (Education)');

		$areas = array();
        $levels = $this->AreaEducationLevel->find('all', array(
            'fields' => array('AreaEducationLevel.id', 'AreaEducationLevel.name', 'AreaEducationLevel.level'),
            'order' => array('AreaEducationLevel.level ASC'),
            'recursive' => 0, //int
        ));

        if($this->request->is('post')) {
            $this->AreaEducationLevel->saveAreaLevelData($this->data['AreaEducationLevel']);
            $this->redirect('/Areas/AreaEducationLevels');
            // pr($this->data);
        }

        $this->set('levels', $this->Utility->formatResult($levels));
  //       $this->set('highestLevel',$areas);  
        
        $this->render('/AreaEducationLevels/edit');

	}
	
	public function AreaEducation() {
		$this->Navigation->addCrumb('Areas');

		$areas = array();
        $levels = $this->AreaEducationLevel->find('list', array('order'=>array('AreaEducationLevel.level ASC')));
        $topArea = $this->AreaEducation->find('list',array('conditions'=>array('AreaEducation.parent_id' => '-1')));
        $this->unshift_array($topArea, array('0'=>__('--Select--')));
        $areas[] = $topArea;


        if($this->request->is('post')) {
            if(isset($this->request->data['AreaEducation'])){
                for ($i = 0; $i < count($this->request->data['AreaEducation'])-1; $i++) {
                    $area = $this->AreaEducation->find('list',array(
                                                    'conditions' => array(
                                                        'AreaEducation.parent_id' => $this->request->data['AreaEducation']['area_education_level_'.$i]
                                                    )
                                                )
                                            );
                    
                    $this->unshift_array($area, array('0'=>__('--Select--')));
                    $areas[] = $area;
                }
                if(end($this->request->data['AreaEducation']) == 0 ){
                    array_pop($this->request->data['AreaEducation']);
                }
            }
            $this->set('initAreaSelection', (isset($this->request->data['AreaEducation']) && count($this->request->data['AreaEducation']) > 0)?$this->request->data['AreaEducation']: null);

        }

        if(count($topArea)<2)  $this->Utility->alert($this->Utility->getMessage('AREAS_NO_AREA_LEVEL'));

		// Checking if user has access to _view for area levels
		$_view_levels = false;
		if($this->AccessControl->check($this->params['controller'], 'levels')) {
			$_view_levels = true;
		}

        if(isset($initAreaSelection['area_id'])){
            pr($initAreaSelection['area_id']);
        }

		$this->set('_view_levels', $_view_levels);
		// End Access Control
        $this->set('topArea', $topArea);
		$this->set('levels', $levels);
        $this->set('highestLevel',$areas);
		$this->render('/AreaEducation/index');
	}

    public function AreaEducationEdit() {
		
		$this->Navigation->addCrumb('Edit Areas (Education)');
		
        $areas = array();
        $levels = $this->AreaEducationLevel->find('list');
        $topArea = $this->AreaEducation->find('list',array('conditions'=>array('AreaEducation.parent_id' => '-1')));
        $this->unshift_array($topArea, array('0'=>__('--Select--')));
        $areas[] = $topArea;

        if($this->request->is('post')) {
            if(isset($this->request->data['AreaEducation'])){
                for ($i = 0; $i < count($this->request->data['AreaEducation'])-1; $i++) {
                    $area = $this->AreaEducation->find('list',array(
                            'conditions'=> array(
                                'AreaEducation.parent_id' => $this->request->data['AreaEducation']['area_education_level_'.$i]
                            )
                        )
                    );
                    
                    $this->unshift_array($area, array('0'=>__('--Select--')));
                    $areas[] = $area;
                }
                
            }

            if(isset($this->request->data['AreaEducation']) &&  end($this->request->data['AreaEducation']) == 0 ){
                array_pop($this->request->data['AreaEducation']);
            }
            $this->set('initAreaSelection', (isset($this->request->data['AreaEducation']))?$this->request->data['AreaEducation']: null);

        }

        $this->set('levels', $levels);
        $this->set('highestLevel',$areas);
		$this->render('/AreaEducation/edit');
    }
}
