Update batch_reports set query="$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        
        $IdentityType = ClassRegistry::init('IdentityType');
        $identityTypes = $IdentityType->find('list', array('fields'=>array('id', 'name')));
        $fields = array('TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle','GROUP_CONCAT(TrainingProvider.name) as Provider','TrainingCourse.credit_hours AS Credit', 'TrainingSession.location as Location', 'TrainingSession.start_date as StartDate',
                      'TrainingSession.end_date as EndDate', 'Staff.identification_no as OpenEmisID');
        $templateFormat = 'CourseCode,CourseTitle,Provider,Credit,Location,StartDate,EndDate,OpenEmisID%s,FirstName,LastName,Result,Completed';
        $templateVF = '';
        if(!empty($identityTypes)){
            $c = 0;
            foreach($identityTypes as $key=>$val){
                $fields[] = '(Select StaffIdentity.number from staff_identities as StaffIdentity where TrainingSessionTrainee.staff_id = StaffIdentity.staff_id and StaffIdentity.identity_type_id='.$key.') as `'.$val.'`';
                $templateVF .= ','. $val;
            }
        }

        $templateFormat = sprintf($templateFormat, $templateVF);
        $BatchReport = ClassRegistry::init('BatchReport');
        $BatchReport->id = 1030;
        $BatchReport->saveField('template', $templateFormat);

        $fields = array_merge($fields, array('Staff.first_name as FirstName', 'Staff.last_name as LastName', 'TrainingSessionTrainee.result as Result','((CASE WHEN TrainingSessionTrainee.pass=-1 THEN ""-"" WHEN TrainingSessionTrainee.pass=1 THEN ""Passed"" ELSE ""Failed"" END)) AS Completed'));
        
        $data = $TrainingCourse->find('all',
            array('fields' => $fields,
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
                'order' =>array('TrainingCourse.title', 'Staff.id')
                ));" 
where id = 1030;

Update batch_reports set query="$this->autoRender = false;
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
        $IdentityType = ClassRegistry::init('IdentityType');
        $identityTypes = $IdentityType->find('list', array('fields'=>array('id', 'name')));
        $fields = array('((CASE WHEN StaffTrainingNeed.ref_course_table =""TrainingNeedCategory"" THEN TrainingNeedCategory.name ELSE ""Course Catalogue"" END)) AS NeedType',
                      'StaffTrainingNeed.ref_course_code AS CourseCode','StaffTrainingNeed.ref_course_title AS CourseTitle','StaffTrainingNeed.ref_course_requirement AS Requirement', 'TrainingPriority.name AS Priority', 'StaffTrainingNeed.comments AS Comment', 'Staff.identification_no AS OpenEmisID');
        $templateFormat = 'NeedType,CourseCode,CourseTitle,Requirement,Priority,Comment,OpenEmisID%s,FirstName,LastName';
        $templateVF = '';
        if(!empty($identityTypes)){
            $c = 0;
            foreach($identityTypes as $key=>$val){
                $fields[] = '(Select StaffIdentity.number from staff_identities as StaffIdentity where StaffTrainingNeed.staff_id = StaffIdentity.staff_id and StaffIdentity.identity_type_id='.$key.') as '.$val;
                $templateVF .= ','. $val;
            }
        }
       
        $templateFormat = sprintf($templateFormat, $templateVF);
        $BatchReport = ClassRegistry::init('BatchReport');
        $BatchReport->id = 1031;
        $BatchReport->saveField('template', $templateFormat);
        $fields = array_merge($fields, array('Staff.first_name as FirstName', 'Staff.last_name as LastName'));
    

        $data = $StaffTrainingNeed->find('all',
        array('fields' => $fields,
            'joins' => array(
                array('table' => 'staff', 'alias' => 'Staff', 'type' => 'INNER',
                    'conditions' => array('Staff.id = StaffTrainingNeed.staff_id')
                ),
                array(
                    'table' => 'training_statuses','alias' => 'TrainingStatus','type' => 'INNER',
                    'conditions' => array('TrainingStatus.id = StaffTrainingNeed.training_status_id')
                ),
                array(
                    'table' => 'training_priorities','alias' => 'TrainingPriority','type' => 'INNER',
                    'conditions' => array('TrainingPriority.id = StaffTrainingNeed.training_priority_id')
                )
            ),
            'conditions'=> array('StaffTrainingNeed.training_status_id'=>3),
            'order' => array('StaffTrainingNeed.ref_course_title')
            ));" 
where id = 1031;

Update batch_reports set query="$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;

        $IdentityType = ClassRegistry::init('IdentityType');
        $identityTypes = $IdentityType->find('list', array('fields'=>array('id', 'name')));
        $fields = array('TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle','TrainingCourse.credit_hours AS Credit', 'TrainingSession.location AS Location','Staff.identification_no as OpenEmisID');
        $templateFormat = 'CourseCode,CourseTitle,Credit,Location,OpenEmisID%s,FirstName,LastName,StartDate,EndDate';
        $templateVF = '';
        if(!empty($identityTypes)){
            $c = 0;
            foreach($identityTypes as $key=>$val){
                $fields[] = '(Select StaffIdentity.number from staff_identities as StaffIdentity where TrainingSessionTrainee.staff_id = StaffIdentity.staff_id and StaffIdentity.identity_type_id='.$key.') as '.$val;
                $templateVF .= ','. $val;
            }
        }

        $templateFormat = sprintf($templateFormat, $templateVF);
        $BatchReport = ClassRegistry::init('BatchReport');
        $BatchReport->id = 1032;
        $BatchReport->saveField('template', $templateFormat);

        $fields = array_merge($fields, array('Staff.first_name as FirstName', 'Staff.last_name as LastName', 'TrainingSession.start_date AS StartDate', 'TrainingSession.end_date AS EndDate'));

        $data = $TrainingCourse->find('all', 
            array('fields' => $fields,
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
        ));" 
where id = 1032;


Update batch_reports set query="
$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingSessionTrainee');
        $TrainingCourse->formatResult = true;

        $IdentityType = ClassRegistry::init('IdentityType');
        $identityTypes = $IdentityType->find('list', array('fields'=>array('id', 'name')));
        $fields = array('Staff.identification_no as OpenEmisID');
        $templateFormat = 'OpenEmisID%s,FirstName,LastName,Position,CourseCode,CourseTitle,Location,StartDate,EndDate';
        $templateVF = '';
        if(!empty($identityTypes)){
            $c = 0;
            foreach($identityTypes as $key=>$val){
                $fields[] = '(Select StaffIdentity.number from staff_identities as StaffIdentity where TrainingSessionTrainee.staff_id = StaffIdentity.staff_id and StaffIdentity.identity_type_id='.$key.') as '.$val;
                $templateVF .= ','. $val;
            }
        }

        $templateFormat = sprintf($templateFormat, $templateVF);
        $BatchReport = ClassRegistry::init('BatchReport');
        $BatchReport->id = 1034;
        $BatchReport->saveField('template', $templateFormat);

        $fields = array_merge($fields, array('Staff.first_name as FirstName', 'Staff.last_name as LastName', 'StaffPositionTitle.name as Position',
                'TrainingCourse1.code AS CourseCode','TrainingCourse1.title AS CourseTitle','TrainingSession1.location as Location', 'TrainingSession1.start_date as StartDate', 'TrainingSession1.end_date as EndDate'));
        

        $data = $TrainingCourse->find('all', 
            array('fields' => $fields,
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
            'conditions' =>  array('TrainingSession1.start_date <= TrainingSession2.start_date', 'TrainingSession1.end_date >= TrainingSession2.start_date'),
            'group' => array('TrainingSessionTrainee.staff_id HAVING COUNT(TrainingSessionTrainee.staff_id) > 1'),
            'order' => array('TrainingCourse1.title')));" 
where id = 1034;