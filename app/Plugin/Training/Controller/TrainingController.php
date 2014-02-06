<?php
App::uses('HttpSocket', 'Network/Http');


class TrainingController extends TrainingAppController {
    public $uses = array(
        'Training.TrainingCourse', 
        'Teachers.TeacherPositionTitle', 
        'Training.TrainingSession',
        'Training.TrainingSessionTrainee'
     );

    public $modules = array(
        'course' => 'Training.TrainingCourse',
        'session' => 'Training.TrainingSession',
        'result' => 'Training.TrainingSessionResult',
        'health_allergy' => 'Students.StudentHealthAllergy',
        'health_test' => 'Students.StudentHealthTest',
        'health_consultation' => 'Students.StudentHealthConsultation',
        'health' => 'Students.StudentHealth',
        'special_need' => 'Students.StudentSpecialNeed',
        'award' => 'Students.StudentAward'
    ); 


    public function beforeFilter() {
        parent::beforeFilter();
        $this->bodyTitle = 'Administration';
        $this->Navigation->addCrumb('Administration', array('controller' => '../Setup', 'action' => 'index'));
    }


    public function ajax_add_target_population() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);
        $this->render('/Elements/target_population');
    }

    public function ajax_find_target_population($index) {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TeacherPositionTitle->autocomplete($search,$index);
 
            return json_encode($data);
        }
    }

    public function ajax_add_prerequisite() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);
        $this->render('/Elements/prerequisite');
    }

    public function ajax_find_prerequisite($index) {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TrainingCourse->autocomplete($search,$index);
 
            return json_encode($data);
        }
    }

    //----------------------------------------------------------------------------

    public function ajax_find_session($type) {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TrainingSession->autocomplete($search,$type);
 
            return json_encode($data);
        }
    }

     public function ajax_add_trainee() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);
        $this->render('/Elements/trainee');
    }

    public function ajax_find_trainee($index) {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TrainingSessionTrainee->autocomplete($search,$index);
 
            return json_encode($data);
        }
    }
}