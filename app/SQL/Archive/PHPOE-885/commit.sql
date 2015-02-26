Update batch_reports set query="$this->autoRender = false;   
            $TrainingCourse = ClassRegistry::init('TrainingCourse');
            $TrainingCourse->formatResult = true;
            
            $data = $TrainingCourse->find('all', 
            array('fields' => array(
                'TrainingCourse.code as CourseCode', 'TrainingCourse.title as CourseTitle', 'StaffPositionTitle.name as TargetGroup', 
                '(Select CASE WHEN COUNT(DISTINCT InstitutionSiteStaff.staff_id) > 0 THEN COUNT(DISTINCT InstitutionSiteStaff.staff_id) ELSE (SELECT COUNT(*) from staff as Staff) END from institution_site_staff as InstitutionSiteStaff INNER JOIN institution_site_positions as InstitutionSitePosition on InstitutionSitePosition.id =  InstitutionSiteStaff.institution_site_position_id WHERE StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id) as TotalTargetGroup', 
                'COUNT(DISTINCT TrainingSessionTrainee.staff_id) as TotalTrained',
                'round(((COUNT(DISTINCT TrainingSessionTrainee.staff_id)/IFNULL((Select CASE WHEN COUNT(DISTINCT InstitutionSiteStaff.staff_id) > 0 THEN COUNT(DISTINCT InstitutionSiteStaff.staff_id) ELSE (select count(*) from staff as Staff) END from institution_site_staff as InstitutionSiteStaff INNER JOIN institution_site_positions as InstitutionSitePosition on InstitutionSitePosition.id =  InstitutionSiteStaff.institution_site_position_id WHERE StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id),0)) * 100),2)  as TargetGroupPercentage'
            ),         
            'joins' => array(
                array('table' => 'training_sessions','alias' => 'TrainingSession','type' => 'INNER',
                    'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id', 'TrainingSession.training_status_id'=>3)
                ), 
                array('table' => 'training_session_trainees','alias' => 'TrainingSessionTrainee','type' => 'LEFT',
                    'conditions' => array('TrainingSession.id = TrainingSessionTrainee.training_session_id')
                ),
                array('table' => 'training_course_target_populations','alias' => 'TrainingCourseTargetPopulation','type' => 'LEFT',
                     'conditions' => array('TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id')
                ),        
                array('table' => 'staff_position_titles','alias' => 'StaffPositionTitle','type' => 'LEFT', 
                     'conditions' => array('StaffPositionTitle.id = TrainingCourseTargetPopulation.staff_position_title_id')
                ), 
                array('table' => 'training_session_results','alias' => 'TrainingSessionResult','type' => 'LEFT',
                    'conditions' => array('TrainingSession.id = TrainingSessionResult.training_session_id', 'TrainingSessionResult.training_status_id'=>3)
                ), 
             ), 
            'conditions' => array('TrainingCourse.training_status_id'=>3),
            'group' => array('TrainingCourse.id', 'TrainingCourseTargetPopulation.staff_position_title_id'),
            'order' => array('TrainingCourse.title')
            ));" 
where id = 1035;