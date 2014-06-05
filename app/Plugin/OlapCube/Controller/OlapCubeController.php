<?php
App::uses('HttpSocket', 'Network/Http');


class OlapCubeController extends OlapCubeAppController {
 
    public $uses = array(
        'OlapCube.OlapCubeDimension'
     );
    
    public $modules = array(
        'olap_report' => 'OlapCube.OlapCubeDimension'
    ); 

    public function beforeFilter() {
        parent::beforeFilter();
        $this->bodyTitle = 'Report';
        $this->Navigation->addCrumb('Administration', array('controller' => '../Setup', 'action' => 'index'));
    }

    public function getDimension(){
        $this->autoRender = false;

        if(isset($this->params['pass'][0]) && !empty($this->params['pass'][0])) {


            $id = $this->params['pass'][0];
            $cubeId = $this->params['pass'][1];
            $data = $this->OlapCubeDimension->find('all', 
                array(
                    'fields' => array('OlapCubeDimension.*'),   
                    'conditions'=>array('OlapCubeDimension.id !=' .$id, 'OlapCubeDimension.olap_cube_id'=>$cubeId), 
                    'order'=>array('order'),
                    'recursive' => -1)
            );
            
            echo json_encode($data);
        }

    }



}
?>