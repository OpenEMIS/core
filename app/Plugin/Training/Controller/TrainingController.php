<?php
App::uses('HttpSocket', 'Network/Http');


class TrainingController extends TrainingAppController {
    public $uses = array(
        'Training.TrainingCourse', 
        'Teachers.TeacherPositionTitle', 
        'Training.TrainingSession',
        'Training.TrainingSessionTrainee',
        'Training.TrainingCourseAttachment'
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
            $data = $this->TrainingCourse->autocompletePosition($search,$index);
 
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


    public function attachmentsCourseAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->set('_model', 'TrainingCourseAttachment');
        $this->set('jsname', 'objTrainingCourses');
        $this->render('/Elements/attachment/compact_add');
    }

    public function attachmentsCourseDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model'=>'Training.TrainingCourseAttachment', 'foreignKey' => 'training_course_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
            
            if($FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }
            
            return json_encode($result);
        }
    }
        
    public function attachmentsCourseDownload($id) {
        $arrMap = array('model'=>'Training.TrainingCourseAttachment', 'foreignKey' => 'training_course_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $FileAttachment->download($id);
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