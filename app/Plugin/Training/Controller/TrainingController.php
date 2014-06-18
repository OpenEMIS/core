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
        'Training.TrainingCourseResultType'
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


    public function report(){
        
        //Training Course Report
        /*$this->autoRender = false; 
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle',
            'TrainingStatus.name AS Status','TrainingCourse.description as CourseDescription',
            'TrainingCourse.objective AS GoalObjective','TrainingCourse.credit_hours as Credit',
            'TrainingCourse.duration as Duration', 'TrainingModeDelivery.name as ModeOfDelivery',
            'GROUP_CONCAT(TrainingProvider.name) as Provider','TrainingRequirement.name as Requirement', 
            'TrainingLevel.name as Level', 'GROUP_CONCAT(TrainingCoursePrerequisiteCourse.title) as Prerequisite'
        ),
        'joins' => array(
            array('table' => 'training_statuses','alias' => 'TrainingStatus','type' => 'LEFT',
                'conditions' => array('TrainingStatus.id = TrainingCourse.training_status_id')
            ),
            array('table' => 'training_mode_deliveries','alias' => 'TrainingModeDelivery','type' => 'LEFT',
                'conditions' => array('TrainingModeDelivery.id = TrainingCourse.training_mode_delivery_id')
            ),
            array('table' => 'training_course_providers','alias' => 'TrainingCourseProvider','type' => 'LEFT',
                'conditions' => array('TrainingCourse.id = TrainingCourseProvider.training_course_id')
            ),
            array('table' => 'training_providers','alias' => 'TrainingProvider','type' => 'LEFT',
                'conditions' => array('TrainingProvider.id = TrainingCourseProvider.training_provider_id')
            ), 
            array('table' => 'training_requirements','alias' => 'TrainingRequirement','type' => 'LEFT',
                'conditions' => array('TrainingRequirement.id = TrainingCourse.training_requirement_id')
            ), 
            array('table' => 'training_levels','alias' => 'TrainingLevel','type' => 'LEFT',
                'conditions' => array('TrainingLevel.id = TrainingCourse.training_level_id')
            ), 
            array('table' => 'training_course_prerequisites','alias' => 'TrainingCoursePrerequisite','type' => 'LEFT',
                'conditions' => array('TrainingCourse.id = TrainingCoursePrerequisite.training_course_id')
            ), 
            array('table' => 'training_courses','alias' => 'TrainingCoursePrerequisiteCourse','type' => 'LEFT',
                'conditions' => array('TrainingCoursePrerequisiteCourse.id = TrainingCoursePrerequisite.training_course_id')
            )
         ),
        'group'=>array('TrainingCourse.id')
        ));*/
        //Training Course Completed Report
        /*
        $this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle',
            'GROUP_CONCAT(TrainingProvider.name) as Provider','TrainingCourse.credit_hours AS Credit', 
            'TrainingSession.location as Location', 'TrainingSession.start_date as StartDate',
            'TrainingSession.end_date as EndDate', 'Staff.identification_no as OpenEmisID',
            'Staff.first_name as FirstName', 'Staff.last_name as LastName', 
            'TrainingSessionTrainee.result as Result','((CASE WHEN TrainingSessionTrainee.pass=-1 THEN "-" WHEN TrainingSessionTrainee.pass=1 THEN "Passed"
             ELSE "Failed" END)) AS Completed'
            
        ),
        'joins' => array(
            array('table' => 'training_sessions','alias' => 'TrainingSession','type' => 'INNER',
                'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
            ),
            array('table' => 'training_course_providers','alias' => 'TrainingCourseProvider','type' => 'LEFT',
                'conditions' => array('TrainingCourse.id = TrainingCourseProvider.training_course_id')
            ),
            array('table' => 'training_providers','alias' => 'TrainingProvider','type' => 'LEFT',
                'conditions' => array('TrainingProvider.id = TrainingCourseProvider.training_provider_id')
            ), 
            array('table' => 'training_session_trainees','alias' => 'TrainingSessionTrainee','type' => 'INNER',
                'conditions' => array('TrainingSession.id = TrainingSessionTrainee.training_session_id')
            ), 
            array('table' => 'staff','alias' => 'Staff','type' => 'INNER',
                'conditions' => array('Staff.id = TrainingSessionTrainee.staff_id')
            )
         ),
         'group' => array('TrainingCourse.id','TrainingSessionTrainee.staff_id'),
         'order' =>array('TrainingCourse.title', 'Staff.first_name')
        ));*/
        //Staff Training Need Report
        /*$this->autoRender = false;
        $StaffTrainingNeed = ClassRegistry::init('StaffTrainingNeed');

        $StaffTrainingNeed->bindModel(
            array('belongsTo'=>
                array(
                    'TrainingCourse' => array(
                        'className' => 'TrainingCourse',
                        'foreignKey' => 'ref_course_id',
                        'conditions' => array('ref_course_table' => 'TrainingCourse'),
                    ),
                    'TrainingNeedCategory' => array(
                        'className' => 'FieldOptionValue',
                        'foreignKey' => 'ref_course_id',
                        'conditions' => array('ref_course_table' => 'TrainingNeedCategory'),
                    )
                )
            )
        );
        $StaffTrainingNeed->formatResult = true;
        $data = $StaffTrainingNeed->find('all', 
        array('fields' => array(
            '((CASE WHEN StaffTrainingNeed.ref_course_table ="TrainingNeedCategory" THEN TrainingNeedCategory.name
             ELSE "Course Catalogue" END)) AS NeedType',
            'StaffTrainingNeed.ref_course_code AS CourseCode','StaffTrainingNeed.ref_course_title AS CourseTitle',
            'StaffTrainingNeed.ref_course_requirement AS Requirement', 'TrainingPriority.name AS Priority', 'StaffTrainingNeed.comments AS Comment', 
            'Staff.identification_no AS OpenEmisID', 'Staff.first_name AS FirstName', 
            'Staff.last_name AS LastName'
        ),
        'joins' => array(
            array('table' => 'staff','alias' => 'Staff','type' => 'INNER',
                'conditions' => array('Staff.id = StaffTrainingNeed.staff_id')
            ),
            array('table' => 'training_statuses','alias' => 'TrainingStatus','type' => 'INNER',
                'conditions' => array('TrainingStatus.id = StaffTrainingNeed.training_status_id')
            ),
            array('table' => 'training_priorities','alias' => 'TrainingPriority','type' => 'INNER',
                'conditions' => array('TrainingPriority.id = StaffTrainingNeed.training_priority_id')
            )
         ),
         'conditions' => array('StaffTrainingNeed.training_status_id'=>3),
         'order' => array('StaffTrainingNeed.ref_course_title')
        ));*/
        //Training Course Uncompleted Report
        /*$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle',
            'TrainingCourse.credit_hours AS Credit', 'TrainingSession.location AS Location',
            'Staff.identification_no as OpenEmisID',
            'Staff.first_name as FirstName', 'Staff.last_name as LastName', 
            'TrainingSession.start_date AS StartDate', 'TrainingSession.end_date AS EndDate', 
        ),
        'joins' => array(
            array('table' => 'training_sessions','alias' => 'TrainingSession','type' => 'INNER',
                'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
            ), 
            array('table' => 'training_session_trainees','alias' => 'TrainingSessionTrainee','type' => 'INNER',
                'conditions' => array('TrainingSession.id = TrainingSessionTrainee.training_session_id', 'TrainingSession.training_status_id'=>3)
            ),
            array('table' => 'training_session_results','alias' => 'TrainingSessionResult','type' => 'INNER',
                'conditions' => array('TrainingSession.id = TrainingSessionResult.training_session_id', 'NOT' => array('TrainingSessionResult.training_status_id'=>3))
            ), 
            array('table' => 'staff','alias' => 'Staff','type' => 'INNER',
                'conditions' => array('Staff.id = TrainingSessionTrainee.staff_id')
            )
         ),
         'order' => array('TrainingCourse.title')
        ));*/
        //Training Trainer Report
        /*
        $this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingSessionTrainer.ref_trainer_name as Trainer', 
            '((CASE WHEN TrainingSessionTrainer.ref_trainer_table ="Staff" THEN "Internal"
             ELSE "External" END)) AS TrainerType', 'TrainingCourse.code AS CourseCode',
            'TrainingCourse.title AS CourseTitle','TrainingCourse.credit_hours AS Credit', 
            'TrainingCourse.duration AS Duration','TrainingSession.location AS Location', 
            'TrainingSession.start_date AS StartDate', 'TrainingSession.end_date AS EndDate'
        ),
        'joins' => array(
            array('table' => 'training_sessions','alias' => 'TrainingSession','type' => 'INNER',
                'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id' , 'TrainingSession.training_status_id' => 3)
            ),
            array('table' => 'training_session_trainers','alias' => 'TrainingSessionTrainer','type' => 'INNER',
                'conditions' => array('TrainingSession.id = TrainingSessionTrainer.training_session_id')
            )
         ),
         'order' => array('TrainingCourse.title')
        ));*/
        //Training Exception Report
        /*
        $this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingSessionTrainee');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'Staff.identification_no as OpenEmisID', 'Staff.first_name as FirstName', 
            'Staff.last_name as LastName', 'StaffPositionTitle.name as Position',
            'TrainingCourse1.code AS CourseCode','TrainingCourse1.title AS CourseTitle',
            'TrainingSession1.location as Location', 'TrainingSession1.start_date as StartDate', 
            'TrainingSession1.end_date as EndDate'
        ),
        'joins' => array(
            array(
                'table' => 'training_sessions','alias' => 'TrainingSession1','type' => 'INNER',
                'conditions' => array('TrainingSession1.id = TrainingSessionTrainee.training_session_id', 'TrainingSession1.training_status_id'=>3)
            ),
            array(
                'table' => 'training_sessions','alias' => 'TrainingSession2','type' => 'INNER',
                'conditions' => array('TrainingSession2.id = TrainingSessionTrainee.training_session_id', 'TrainingSession2.training_status_id'=>3)
            ),
             array(
                'table' => 'training_courses','alias' => 'TrainingCourse1','type' => 'INNER',
                'conditions' => array('TrainingCourse1.id = TrainingSession1.training_course_id', 'TrainingCourse1.training_status_id'=>3)
            ), 
             array(
                'table' => 'training_courses','alias' => 'TrainingCourse2','type' => 'INNER',
                'conditions' => array('TrainingCourse2.id = TrainingSession2.training_course_id', 'TrainingCourse2.training_status_id'=>3)
            ), 
            array('table' => 'staff','alias' => 'Staff','type' => 'LEFT',
                'conditions' => array('Staff.id = TrainingSessionTrainee.staff_id')
            ), 
            array('table' => 'institution_site_staff','alias' => 'InstitutionSiteStaff','type' => 'LEFT',
                'conditions' => array('Staff.id = InstitutionSiteStaff.staff_id')
            ), 
            array('table' => 'institution_site_positions','alias' => 'InstitutionSitePosition','type' => 'LEFT',
                'conditions' => array('InstitutionSiteStaff.institution_site_position_id = InstitutionSitePosition.id')
            ), 
            array('table' => 'staff_position_titles','alias' => 'StaffPositionTitle','type' => 'LEFT',
                'conditions' => array('StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id')
            ), 
         ),
         'conditions' => 
         array('TrainingSession1.start_date <= TrainingSession2.start_date', 
            'TrainingSession1.end_date >= TrainingSession2.start_date'
         ),
         'group' => array('TrainingSessionTrainee.staff_id HAVING COUNT(TrainingSessionTrainee.staff_id) > 1'),
         'order' => array('TrainingCourse1.title')
        ));
        */
        //Training Staff Statistic Report
        $this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingCourse.code as CourseCode', 'TrainingCourse.title as CourseTitle', /*'StaffPositionTitle.name as TargetGroup', 'COUNT(DISTINCT IFNULL(InstitutionSiteStaff.staff_id, Staff.id)) as TotalTargetGroup', 'COUNT(DISTINCT TrainingSessionTrainee.staff_id) as TotalTrained',
            'round(((COUNT(DISTINCT TrainingSessionTrainee.staff_id)/IFNULL(COUNT(DISTINCT IFNULL(InstitutionSiteStaff.staff_id, Staff.id)),0)) * 100),2)  as Percentage'*/
        ),
        'joins' => array(
            array(
                'table' => 'training_sessions','alias' => 'TrainingSession','type' => 'INNER',
                'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id', 'TrainingSession.training_status_id'=>3)
            ),
            array(
                'table' => 'training_session_trainees','alias' => 'TrainingSessionTrainee','type' => 'LEFT',
                'conditions' => array('TrainingSession.id = TrainingSessionTrainee.training_session_id')
            ), 
           array(
                'table' => 'training_course_target_populations','alias' => 'TrainingCourseTargetPopulation','type' => 'LEFT',
                'conditions' => array('TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id')
            ),
             array('table' => 'institution_site_staff','alias' => 'InstitutionSiteStaff','type' => 'LEFT',
                'conditions' => array('TrainingSessionTrainee.staff_id = InstitutionSiteStaff.staff_id')
            ),  
            array('table' => 'institution_site_positions','alias' => 'InstitutionSitePosition','type' => 'LEFT',
                'conditions' => array('InstitutionSiteStaff.institution_site_position_id = InstitutionSitePosition.id', 'TrainingCourseTargetPopulation.staff_position_title_id = InstitutionSitePosition.staff_position_title_id')
            ), 
            array('table' => 'staff_position_titles','alias' => 'StaffPositionTitle','type' => 'LEFT',
                'conditions' => array('StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id')
            ), */
            array('table' => 'staff','alias' => 'Staff','type' => 'LEFT',
                'conditions' => array('Staff.id IS NOT NULL')
            ),/*
            
             array(
                'table' => 'training_session_results','alias' => 'TrainingSessionResult','type' => 'LEFT',
                'conditions' => array('TrainingSession.id = TrainingSessionResult.training_session_id', 'TrainingSessionResult.training_status_id'=>3)
            ),*/
         ),
        /* 'conditions' => 
         array('TrainingCourse.training_status_id'=>3
         ),
         'group' => array('TrainingCourse.id', 'TrainingCourseTargetPopulation.staff_position_title_id'),
         'order' => array('TrainingCourse.title')*/
        ));
        pr($data);
    }

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