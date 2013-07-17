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
	public $uses = array('Area', 'AreaLevel');

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
		
		// Checking if user has access to _view for area levels
		$_view_levels = false;
		if($this->AccessControl->check($this->params['controller'], 'levels')) {
			$_view_levels = true;
		}
		$this->set('_view_levels', $_view_levels);
		// End Access Control
		
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
            $this->redirect('/Areas/levelsEdit');
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
	public function viewAreaChildren($parentId = 0) {
		$this->autoRender = false;

		$listAreas = $this->Area->find('list', array(
            'fields' => array('Area.id', 'Area.name'),
	        'conditions' => array('Area.parent_id' => $parentId),
            'order' => array('Area.order ASC')
	    ));

	    $this->unshift_array($listAreas, array(0 => __('--Select--')));

	    echo json_encode($listAreas);

	}
    
    /**
     * Created by: Eugene Wong
     * @param  integer $parentId         Find the next children levels data by parent id.
     * @param  integer $previousParentId Find the previous parent id.
     * @return json                    
     */
	public function viewData($parentId = -1, $previousParentId = 0) {
		$this->autoRender = false;
		$this->Area->formatResult = false;
		$listAreas = $this->Area->find('all', array(
			'recursive' => 0,
	        'conditions' => array('Area.parent_id' => $parentId),
		    'fields' => array('Area.id', 'Area.code', 'Area.name', 'Area.visible', 'Area.order', 'Area.area_level_id AS area_level_id', 'AreaLevel.name AS level_name'),
            'order' => array('Area.order ASC', 'Area.id ASC')
	    ));

        $db = $this->AreaLevel->getDataSource();
		
        $query = "SELECT DISTINCT `area_levels`.name, `area_levels`.`id`
                        FROM  `area_levels` 
                        WHERE `area_levels`.`id` > (
                            SELECT `areas`.`area_level_id`
                            FROM `areas`
                            WHERE `areas`.`id` = ?
                        )";

        // $query = "SELECT DISTINCT `area_levels`.name, `area_levels`.id
        //                 FROM  `area_levels` 
        //                 LEFT JOIN `areas` ON `areas`.`area_level_id` = `area_levels`.`id` 
        //                 WHERE `areas`.`parent_id` >= ?";

        // $query = "SELECT `area_levels`.id, `area_levels`.name
        //                         FROM  `area_levels` 
        //                         WHERE `area_levels`.id > ( 
        //                         SELECT  `areas`.`area_level_id` 
        //                         FROM  `areas` 
        //                         WHERE  `areas`.`id` = ?
        //                         LIMIT 1 ) ";
        if($parentId == -1){
            $query = "SELECT DISTINCT `area_levels`.name, `area_levels`.`id`
                        FROM  `area_levels` ";

            // $query = "SELECT DISTINCT `area_levels`.id, `area_levels`.name
            //             FROM  `area_levels` 
            //             LEFT JOIN `areas` ON `areas`.`area_level_id` = `area_levels`.`id` 
            //             WHERE `areas`.`parent_id` = ? OR `areas`.`id` IS NULL";

            // $query = "SELECT `area_levels`.id, `area_levels`.name
            //                     FROM  `area_levels` 
            //                     WHERE `area_levels`.id >= ( 
            //                     SELECT  `areas`.`area_level_id` 
            //                     FROM  `areas` 
            //                     WHERE  `areas`.`parent_id` = ?
            //                     LIMIT 1 ) ";

        }
        $listAreaLevels = $db->fetchAll( $query, array($parentId));

	    //$this->Area->formatResult($listAreas);

	    echo json_encode(array('data' => $this->Utility->formatResult($listAreas), 'area_levels' => $this->Utility->formatResult($listAreaLevels)));

	}

    /**
     * Created by: Eugene Wong
     * @return [type] [description]
     */
    public function areaAjax() {
        $this->autoRender = false;
        $validateFailed = false; 
        //header("Status: 404 Not Found");
        $return = array();
        $return['data'] = array('new'=>array(),'error'=>array());
        foreach ($this->data as $key => $element) {
            $this->Area->set($element);
            if(!$this->Area->validates()) {

                // $this->Utility->setAjaxResult('error', $return);
                // $msg = $this->Utility->getFirstError($this->action, $this->Area, $this->Auth->user());
                // $return['msg'] = $msg;
                //pr('error');
                $return['data']['error'][$key+1] = $element;
                $validateFailed = true;

            } else {

                $result = $this->Area->save($element);
                // $$result['data']['new'][$key+1] = $result;
                
                if($result) {
                    $areaId = $result['Area']['id'];
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
            $msg = $this->Utility->getFirstError($this->action, $this->Area, $this->Auth->user());
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
}
