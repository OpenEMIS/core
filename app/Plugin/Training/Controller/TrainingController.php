<?php
App::uses('HttpSocket', 'Network/Http');


class TrainingController extends TrainingAppController {
    public $uses = array(
        'Training.TrainingCourse', 
        'Teachers.TeacherPositionTitle', 
        'Training.TrainingSession',
        'Training.TrainingSessionTrainee',
        'Training.TrainingSessionTrainer',
        'Training.TrainingCourseAttachment',
        'TrainingProvider',
        'Training.TrainingCourseProvider',
        'Training.TrainingCourseResultType',
        'Staff.Staff'
     );

    public $helpers = array('Js' => array('Jquery'));

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

     public $components = array(
        'FileUploader',
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->bodyTitle = 'Administration';
        $this->Navigation->addCrumb('Administration', array('controller' => '../Setup', 'action' => 'index'));
    }

    public function array2csv($results=NULL, $fieldName=NULL)
    {
        ob_end_clean();
        ob_start();
        $df = fopen("php://output", 'w');
        $this->fputcsv($df, $fieldName);
        if(!empty($results)){
            foreach($results as $key=>$value){
                $this->fputcsv($df, $value);
            }
        }
        fclose($df);
        return ob_get_clean();
    }

    public function fputcsv(&$handle, $fields = array(), $delimiter = ',', $enclosure = '"') {
        $str = '';
        $escape_char = '\\';
        foreach ($fields as $value) {
          if (strpos($value, $delimiter) !== false ||
              strpos($value, $enclosure) !== false ||
              strpos($value, "\n") !== false ||
              strpos($value, "\r") !== false ||
              strpos($value, "\t") !== false ||
              strpos($value, ' ') !== false) {
            $str2 = $enclosure;
            $escaped = 0;
            $len = strlen($value);
            for ($i=0;$i<$len;$i++) {
              if ($value[$i] == $escape_char) {
                $escaped = 1;
              } else if (!$escaped && $value[$i] == $enclosure) {
                $str2 .= $enclosure;
              } else {
                $escaped = 0;
              }
              $str2 .= $value[$i];
            }
            $str2 .= $enclosure;
            $str .= $str2.$delimiter;
          } else {
            $str .= $value.$delimiter;
          }
        }
        $str = substr($str,0,-1);
        $str .= "\n";
        return fwrite($handle, $str);
    }

    public function download($name){
        if( ! $name)
        {
            $name = md5(uniqid() . microtime(TRUE) . mt_rand()). '.csv';
        }
        header('Expires: 0');
        header('Content-Encoding: UTF-8');
        // force download  
        header("Content-Type: application/force-download; charset=UTF-8'");
        header("Content-Type: application/octet-stream; charset=UTF-8'");
        header("Content-Type: application/download; charset=UTF-8'");
        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$name}");
        header("Content-Transfer-Encoding: binary");
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

     public function ajax_add_result_type() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);
        $this->set('trainingResultTypeOptions', $this->TrainingCourseResultType->TrainingResultType->getList());
        $this->render('/Elements/result_type');
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

    public function trainingCourseAjaxAddField() {
        $this->render =false;
        $this->set('model', 'TrainingCourse');
        $fileId = $this->request->data['size'];
        $multiple = true;
        $this->set(compact('fileId', 'multiple'));
        $this->render('/Elements/templates/file_upload_field');
    }

    //----------------------------------------------------------------------------

    public function ajax_find_location() {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TrainingSession->autocomplete($search);
 
            return json_encode($data);
        }
    }

     public function ajax_add_trainee() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);
        $this->render('/Elements/trainee');
    }

    public function ajax_find_trainee($index,$trainingCourseID) {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TrainingSessionTrainee->autocomplete($search,$index,$trainingCourseID);
 
            return json_encode($data);
       }
    }  

    public function ajax_add_trainer() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);
        $this->set('trainerType', $this->params->query['trainer_type']);
        $this->render('/Elements/trainer');
    }

    public function ajax_find_trainer($index) {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->TrainingSessionTrainer->autocomplete($search,$index);
 
            return json_encode($data);
       }
    }  

    public function ajax_upload_trainee($index, $trainingCourseID) {
        $this->autoRender = false;
        $this->layout = 'ajax';
        $data = array();
        $message = '';
        $errorMessage = '';
        $respond = '';

        $errorFlag = false;
        $count = 0;

        if(isset($_FILES) && !empty($_FILES)){
            if ($_FILES[0]['error'] == UPLOAD_ERR_OK               //checks for errors
                  && is_uploaded_file($_FILES[0]['tmp_name'])) { //checks that file is uploaded
                $tmpName = $_FILES[0]['tmp_name']; 

                ini_set("auto_detect_line_endings", true);
                $handle = fopen($tmpName, "r");
                $row = 0;
                $errorFormat = __('Row %s: %s');
                $i = 0;
                while (($rowData = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rowData = array_map("utf8_encode", $rowData);
                    
                    if($row>0){
                        try{
                            $openEmisID = $rowData[0];

                            $staff = $this->Staff->find('first', array('recursive'=>-1, 'conditions'=>array('Staff.identification_no'=>$openEmisID)));

                            if(empty($staff)){
                               $errorMessage .= '<br />' . sprintf($errorFormat, ($i+1), sprintf(__('Staff with OpenEmis ID %s does not exist.'), $openEmisID));
                            }else{
                                $conditions[] = "Staff.identification_no IN ('" . $openEmisID . "')";
                                $list = $this->TrainingSessionTrainee->searchCriteria($conditions, $trainingCourseID);
                                if(!empty($list)){
                                    $val = array();
                                    foreach($list as $obj){
                                        $firstName = $obj['Staff']['first_name'];
                                        $lastName = $obj['Staff']['last_name'];
                                        $id = $obj['Staff']['id'];
                                        $val['staff_id'] = $id;
                                        $val['first_name'] = $firstName;
                                        $val['last_name'] = $lastName;
                                        $trainingSessionTraineesVal[] = $val;
                                    }
                                    $count++;
                                }else{
                                    $errorMessage .= '<br />' . sprintf($errorFormat, ($i+1), sprintf(__('Staff with OpenEmis ID %s does not meet the Course requirement.', $openEmisID)));
                                }
                            }
                            $i++;
                        } catch (\Exception $e) {
                            $errorMessage .= '<br />' . sprintf($errorFormat, ($i+1), $e);
                        }
                    }
                    $row++;
                }
                fclose($handle);
                if($row<=1){
                    $errorMessage .= '<br />' . sprintf($errorFormat, ($i+1), __('Columns/Data do not match.'));
                }

            }
        }
        $message .= sprintf(__('%s Record(s) have been updated'),$count);

        if(!empty($errorMessage)){
            $errorFlag = true;
            $message .= __('Invalid File Format').$errorMessage;
        }
        $view = new View($this, false);
        $view->set(compact('index'));
        $view->request->data['TrainingSessionTrainee'] = $trainingSessionTraineesVal; 
        $view->viewPath = 'Elements';
        $respond = $view->render('added_trainee');

        $data['layout'] = $respond;
        $data['errorFlag'] =  $errorFlag;
        $data['message'] =  $message;
        //$this->layout = 'ajax';
        //$this->set('index', $this->params->query['index']);
        return json_encode($data);

    }

    //----------------------------------------------------------------------------


     public function ajax_add_provider() {
        $this->layout = 'ajax';
        $this->set('index', $this->params->query['index']);

        $trainingProviderOptions = $this->TrainingProvider->find('list', array('fields'=> array('id', 'name')));
        $this->set('trainingProviderOptions', $trainingProviderOptions);

        $this->render('/Elements/provider');
    }


    //----------------------------------------------------------------------------

     public function getTrainingCoursesById(){
        $this->autoRender = false;

        if(isset($this->params['pass'][0]) && !empty($this->params['pass'][0])) {
            $id = $this->params['pass'][0];
            $courseData = $this->TrainingCourseProvider->find('all', 
                array(
                     'fields' => array('TrainingCourseProvider.*', 'TrainingProvider.*'),
                    'joins' => array(
                        array(
                            'type' => 'INNER',
                            'table' => 'training_providers',
                            'alias' => 'TrainingProvider',
                            'conditions' => array('TrainingProvider.id = TrainingCourseProvider.training_provider_id')
                        )
                     ),   
                    'conditions'=>array('TrainingCourseProvider.training_course_id'=>$id), 
                    'recursive' => -1)
            );
            
            echo json_encode($courseData);
        }
    }


}