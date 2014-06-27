
/*INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1029, 'Training Course Report', '$this->autoRender = false; $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code AS CourseCode'',''TrainingCourse.title AS CourseTitle'',\n            ''TrainingStatus.name AS Status'',''TrainingCourse.description as CourseDescription'',\n            ''TrainingCourse.objective AS GoalObjective'',''TrainingCourse.credit_hours as Credit'',\n            ''TrainingCourse.duration as Duration'', ''TrainingModeDelivery.name as ModeOfDelivery'',\n            ''GROUP_CONCAT(TrainingProvider.name) as Provider'',''TrainingRequirement.name as Requirement'', \n            ''TrainingLevel.name as Level'', ''GROUP_CONCAT(TrainingCoursePrerequisiteCourse.title) as Prerequisite''\n        ),\n        ''joins'' => array(\n            array(''table'' => ''training_statuses'',''alias'' => ''TrainingStatus'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingStatus.id = TrainingCourse.training_status_id'')\n            ),\n            array(''table'' => ''training_mode_deliveries'',''alias'' => ''TrainingModeDelivery'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingModeDelivery.id = TrainingCourse.training_mode_delivery_id'')\n            ),\n            array(''table'' => ''training_course_providers'',''alias'' => ''TrainingCourseProvider'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingCourseProvider.training_course_id'')\n            ),\n            array(''table'' => ''training_providers'',''alias'' => ''TrainingProvider'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingProvider.id = TrainingCourseProvider.training_provider_id'')\n            ), \n            array(''table'' => ''training_requirements'',''alias'' => ''TrainingRequirement'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingRequirement.id = TrainingCourse.training_requirement_id'')\n            ), \n            array(''table'' => ''training_levels'',''alias'' => ''TrainingLevel'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingLevel.id = TrainingCourse.training_level_id'')\n            ), \n            array(''table'' => ''training_course_prerequisites'',''alias'' => ''TrainingCoursePrerequisite'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingCoursePrerequisite.training_course_id'')\n            ), \n            array(''table'' => ''training_courses'',''alias'' => ''TrainingCoursePrerequisiteCourse'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCoursePrerequisiteCourse.id = TrainingCoursePrerequisite.training_course_id'')\n            )\n         ),\n        ''group''=>array(''TrainingCourse.id'')\n        ));', 'CourseCode,CourseTitle,Status,CourseDescription,GoalObjective,Credit,Duration,ModeOfDelivery,Provider,Requirement,Level,Prerequisite', 1, 1029, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1030, 'Training Course Completed Report', '$this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code AS CourseCode'',''TrainingCourse.title AS CourseTitle'',\n            ''GROUP_CONCAT(TrainingProvider.name) as Provider'',''TrainingCourse.credit_hours AS Credit'', \n            ''TrainingSession.location as Location'', ''TrainingSession.start_date as StartDate'',\n            ''TrainingSession.end_date as EndDate'', ''IFNULL(Staff.identification_no, Teacher.identification_no) as OpenEmisID'',\n            ''IFNULL(Staff.first_name, Teacher.first_name) as FirstName'', ''IFNULL(Staff.last_name, Teacher.last_name) as LastName'', \n            ''TrainingSessionTrainee.result as Result'',''((CASE WHEN TrainingSessionTrainee.pass=-1 THEN "-" WHEN TrainingSessionTrainee.pass=1 THEN "Passed"\n             ELSE "Failed" END)) AS Completed''\n            \n        ),\n        ''joins'' => array(\n            array(''table'' => ''training_sessions'',''alias'' => ''TrainingSession'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingSession.training_course_id'')\n            ),\n            array(''table'' => ''training_course_providers'',''alias'' => ''TrainingCourseProvider'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingCourseProvider.training_course_id'')\n            ),\n            array(''table'' => ''training_providers'',''alias'' => ''TrainingProvider'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingProvider.id = TrainingCourseProvider.training_provider_id'')\n            ), \n            array(''table'' => ''training_session_trainees'',''alias'' => ''TrainingSessionTrainee'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionTrainee.training_session_id'')\n            ), \n            array(''table'' => ''staff'',''alias'' => ''Staff'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Staff.id = TrainingSessionTrainee.identification_id'', ''TrainingSessionTrainee.identification_table''=>''staff'')\n            ), \n            array(''table'' => ''teachers'',''alias'' => ''Teacher'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Teacher.id = TrainingSessionTrainee.identification_id'', ''TrainingSessionTrainee.identification_table''=>''teachers'')\n            )\n         ),\n         ''group'' => array(''TrainingCourse.id'',''TrainingSessionTrainee.identification_table'', ''TrainingSessionTrainee.identification_id''),\n         ''order'' =>array(''TrainingCourse.title'', ''TrainingSessionTrainee.identification_first_name'')\n        ));', 'CourseCode,CourseTitle,Provider,Credit,Location,StartDate,EndDate,OpenEmisID,FirstName,LastName,Result,Completed', 1, 1030, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1031, 'Staff Training Need Report', ' $this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code AS CourseCode'',''TrainingCourse.title AS CourseTitle'',\n            ''TrainingRequirement.name AS Requirement'', ''TrainingCourse.credit_hours AS Credit'', \n            ''TrainingPriority.name AS Priority'', ''StaffTrainingNeed.comments AS Comment'', \n            ''Staff.identification_no AS OpenEmisID'', ''Staff.first_name AS FirstName'', \n            ''Staff.last_name AS LastName''\n        ),\n        ''joins'' => array(\n            array(''table'' => ''staff_training_needs'',''alias'' => ''StaffTrainingNeed'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = StaffTrainingNeed.training_course_id'')\n            ), \n            array(''table'' => ''staff'',''alias'' => ''Staff'',''type'' => ''INNER'',\n                ''conditions'' => array(''Staff.id = StaffTrainingNeed.staff_id'')\n            ),\n            array(''table'' => ''training_statuses'',''alias'' => ''TrainingStatus'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingStatus.id = StaffTrainingNeed.training_status_id'')\n            ),\n            array(''table'' => ''training_priorities'',''alias'' => ''TrainingPriority'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingPriority.id = StaffTrainingNeed.training_priority_id'')\n            ),\n            array(''table'' => ''training_requirements'',''alias'' => ''TrainingRequirement'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingRequirement.id = TrainingCourse.training_requirement_id'')\n            )\n         ),\n         ''conditions'' => array(''StaffTrainingNeed.training_status_id''=>3),\n         ''order'' => array(''TrainingCourse.title'')\n        ));', 'CourseCode,CourseTitle,Requirement,Credit,Priority,Comment,OpenEmisID,FirstName,LastName', 1, 1031, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1032, 'Teacher Training Need Report', '$this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code AS CourseCode'',''TrainingCourse.title AS CourseTitle'',\n            ''TrainingRequirement.name AS Requirement'', ''TrainingCourse.credit_hours AS Credit'', \n            ''TrainingPriority.name AS Priority'', ''TeacherTrainingNeed.comments AS Comment'', \n            ''Teacher.identification_no AS OpenEmisID'', ''Teacher.first_name AS FirstName'', \n            ''Teacher.last_name AS LastName''\n        ),\n        ''joins'' => array(\n            array(''table'' => ''teacher_training_needs'',''alias'' => ''TeacherTrainingNeed'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = TeacherTrainingNeed.training_course_id'')\n            ), \n            array(''table'' => ''teachers'',''alias'' => ''Teacher'',''type'' => ''INNER'',\n                ''conditions'' => array(''Teacher.id = TeacherTrainingNeed.teacher_id'')\n            ),\n            array(''table'' => ''training_statuses'',''alias'' => ''TrainingStatus'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingStatus.id = TeacherTrainingNeed.training_status_id'')\n            ),\n            array(''table'' => ''training_priorities'',''alias'' => ''TrainingPriority'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingPriority.id = TeacherTrainingNeed.training_priority_id'')\n            ),\n            array(''table'' => ''training_requirements'',''alias'' => ''TrainingRequirement'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingRequirement.id = TrainingCourse.training_requirement_id'')\n            )\n         ),\n            ''conditions'' => array(''TeacherTrainingNeed.training_status_id''=>3),\n         ''order'' => array(''TrainingCourse.title'')\n        ));', 'CourseCode,CourseTitle,Requirement,Credit,Priority,Comment,OpenEmisID,FirstName,LastName', 1, 1032, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1033, 'Training Course Uncompleted Report', '$this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code AS CourseCode'',''TrainingCourse.title AS CourseTitle'',\n            ''TrainingCourse.credit_hours AS Credit'', ''TrainingSession.location AS Location'',\n            ''IFNULL(Staff.identification_no, Teacher.identification_no) as OpenEmisID'',\n            ''IFNULL(Staff.first_name, Teacher.first_name) as FirstName'', ''IFNULL(Staff.last_name, Teacher.last_name) as LastName'', \n            ''TrainingSession.start_date AS StartDate'', ''TrainingSession.end_date AS EndDate'', \n        ),\n        ''joins'' => array(\n            array(''table'' => ''training_sessions'',''alias'' => ''TrainingSession'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingSession.training_course_id'')\n            ), \n            array(''table'' => ''training_session_trainees'',''alias'' => ''TrainingSessionTrainee'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionTrainee.training_session_id'', ''TrainingSession.training_status_id''=>3)\n            ),\n            array(''table'' => ''training_session_results'',''alias'' => ''TrainingSessionResult'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionResult.training_session_id'', ''NOT'' => array(''TrainingSessionResult.training_status_id''=>3))\n            ), \n            array(''table'' => ''staff'',''alias'' => ''Staff'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Staff.id = TrainingSessionTrainee.identification_id'', ''TrainingSessionTrainee.identification_table''=>''staff'')\n            ), \n            array(''table'' => ''teachers'',''alias'' => ''Teacher'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Teacher.id = TrainingSessionTrainee.identification_id'', ''TrainingSessionTrainee.identification_table''=>''teachers'')\n            )\n         ),\n         ''order'' => array(''TrainingCourse.title'')\n        ));', 'CourseCode,CourseTitle,Credit,Location,OpenEmisID,FirstName,LastName,StartDate,EndDate', 1, 1033, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1034, 'Training Trainer Report', ' $this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingSession.trainer as Trainer'',''TrainingCourse.code AS CourseCode'',\n            ''TrainingCourse.title AS CourseTitle'',''TrainingCourse.credit_hours AS Credit'', \n            ''TrainingCourse.duration AS Duration'',''TrainingSession.location AS Location'', \n            ''TrainingSession.start_date AS StartDate'', ''TrainingSession.end_date AS EndDate''\n        ),\n        ''joins'' => array(\n            array(''table'' => ''training_sessions'',''alias'' => ''TrainingSession'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingSession.training_course_id'' , ''TrainingSession.training_status_id'' => 3)\n            )\n         ),\n         ''order'' => array(''TrainingCourse.title'')\n        ));', 'Trainer,CourseCode,CourseTitle,Credit,Duration,Location,StartDate,EndDate', 1, 1034, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1035, 'Training Exception Report', '$this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingSessionTrainee'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''IFNULL(Staff.identification_no, Teacher.identification_no) as OpenEmisID'', ''IFNULL(Staff.first_name, Teacher.first_name) as FirstName'', \n            ''IFNULL(Staff.last_name, Teacher.last_name) as LastName'', ''IFNULL(StaffPositionTitle.name, TeacherPositionTitle.name) as Position'',\n            ''TrainingCourse1.code AS CourseCode'',''TrainingCourse1.title AS CourseTitle'',\n            ''TrainingSession1.location as Location'', ''TrainingSession1.start_date as StartDate'', \n            ''TrainingSession1.end_date as EndDate''\n        ),\n        ''joins'' => array(\n            array(\n                ''table'' => ''training_sessions'',''alias'' => ''TrainingSession1'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingSession1.id = TrainingSessionTrainee.training_session_id'', ''TrainingSession1.training_status_id''=>3)\n            ),\n            array(\n                ''table'' => ''training_sessions'',''alias'' => ''TrainingSession2'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingSession2.id = TrainingSessionTrainee.training_session_id'', ''TrainingSession2.training_status_id''=>3)\n            ),\n             array(\n                ''table'' => ''training_courses'',''alias'' => ''TrainingCourse1'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse1.id = TrainingSession1.training_course_id'', ''TrainingCourse1.training_status_id''=>3)\n            ), \n             array(\n                ''table'' => ''training_courses'',''alias'' => ''TrainingCourse2'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse2.id = TrainingSession2.training_course_id'', ''TrainingCourse2.training_status_id''=>3)\n            ), \n            array(''table'' => ''staff'',''alias'' => ''Staff'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Staff.id = TrainingSessionTrainee.identification_id'', ''TrainingSessionTrainee.identification_table''=>''staff'')\n            ), \n            array(''table'' => ''teachers'',''alias'' => ''Teacher'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Teacher.id = TrainingSessionTrainee.identification_id'', ''TrainingSessionTrainee.identification_table''=>''teachers'')\n            ), \n            array(''table'' => ''institution_site_staff'',''alias'' => ''InstitutionSiteStaff'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Staff.id = InstitutionSiteStaff.staff_id'')\n            ), \n            array(''table'' => ''staff_position_titles'',''alias'' => ''StaffPositionTitle'',''type'' => ''LEFT'',\n                ''conditions'' => array(''StaffPositionTitle.id = InstitutionSiteStaff.staff_position_title_id'')\n            ), \n            array(''table'' => ''institution_site_teachers'',''alias'' => ''InstitutionSiteTeacher'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Teacher.id = InstitutionSiteTeacher.teacher_id'')\n            ), \n            array(''table'' => ''teacher_position_titles'',''alias'' => ''TeacherPositionTitle'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TeacherPositionTitle.id = InstitutionSiteTeacher.teacher_position_title_id'')\n            )\n         ),\n         ''conditions'' => \n         array(''TrainingSession1.start_date <= TrainingSession2.start_date'', \n            ''TrainingSession1.end_date >= TrainingSession2.start_date''\n         ),\n         ''group'' => array(''identification_table'', ''identification_id HAVING COUNT(identification_id) > 1''),\n         ''order'' => array(''TrainingCourse1.title'')\n        ));', 'OpenEmisID,FirstName,LastName,Position,CourseCode,CourseTitle,Location,StartDate,EndDate', 1, 1035, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1036, 'Training Staff Statistic Report', ' $this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code as CourseCode'', ''TrainingCourse.title as CourseTitle'', ''StaffPositionTitle.name as TargetGroup'', ''COUNT(DISTINCT IFNULL(InstitutionSiteStaff.staff_id, Staff.id)) as TotalTargetGroup'', ''COUNT(DISTINCT TrainingSessionTrainee.identification_id) as TotalTrained'',\n            ''round(((COUNT(DISTINCT TrainingSessionTrainee.identification_id)/IFNULL(COUNT(DISTINCT IFNULL(InstitutionSiteStaff.staff_id, Staff.id)),0)) * 100),2)  as Percentage''\n        ),\n        ''joins'' => array(\n            array(\n                ''table'' => ''training_sessions'',''alias'' => ''TrainingSession'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingSession.training_course_id'', ''TrainingSession.training_status_id''=>3)\n            ),\n            array(\n                ''table'' => ''training_course_target_populations'',''alias'' => ''TrainingCourseTargetPopulation'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id'', ''TrainingCourseTargetPopulation.position_title_table'' => ''staff_position_titles'')\n            ),\n            array(''table'' => ''institution_site_staff'',''alias'' => ''InstitutionSiteStaff'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourseTargetPopulation.position_title_id = InstitutionSiteStaff.staff_position_title_id'')\n            ), \n            array(''table'' => ''staff_position_titles'',''alias'' => ''StaffPositionTitle'',''type'' => ''LEFT'',\n                ''conditions'' => array(''StaffPositionTitle.id = TrainingCourseTargetPopulation.position_title_id'')\n            ),\n            array(''table'' => ''staff'',''alias'' => ''Staff'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Staff.id IS NOT NULL'')\n            ),\n             array(\n                ''table'' => ''training_session_trainees'',''alias'' => ''TrainingSessionTrainee'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionTrainee.training_session_id'', ''TrainingSessionTrainee.identification_table''=>''staff'')\n            ), \n             array(\n                ''table'' => ''training_session_results'',''alias'' => ''TrainingSessionResult'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionResult.training_session_id'', ''TrainingSessionResult.training_status_id''=>3)\n            ),\n         ),\n         ''conditions'' => \n         array(''TrainingCourse.training_status_id''=>3\n         ),\n         ''group'' => array(''TrainingCourse.id'', ''TrainingCourseTargetPopulation.position_title_table'', ''TrainingCourseTargetPopulation.position_title_id''),\n         ''order'' => array(''TrainingCourse.title'')\n        ));', 'CourseCode,CourseTitle,TargetGroup,TotalTargetGroup,TotalTrained,TargetGroupPercentage', 1, 1036, NULL, NULL, 1, '0000-00-00 00:00:00');
INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1037, 'Training Teacher Statistic Report', '\n        $this->autoRender = false;\n        $TrainingCourse = ClassRegistry::init(''TrainingCourse'');\n        $TrainingCourse->formatResult = true;\n        $data = $TrainingCourse->find(''all'', \n        array(''fields'' => array(\n            ''TrainingCourse.code as CourseCode'', ''TrainingCourse.title as CourseTitle'', ''TeacherPositionTitle.name as TargetGroup'', ''COUNT(DISTINCT IFNULL(InstitutionSiteTeacher.teacher_id, Teacher.id)) as TotalTargetGroup'', ''COUNT(DISTINCT TrainingSessionTrainee.identification_id) as TotalTrained'',\n            ''round(((COUNT(DISTINCT TrainingSessionTrainee.identification_id)/IFNULL(COUNT(DISTINCT IFNULL(InstitutionSiteTeacher.teacher_id, Teacher.id)),0)) * 100),2)  as TargetGroupPercentage''\n        ),\n        ''joins'' => array(\n            array(\n                ''table'' => ''training_sessions'',''alias'' => ''TrainingSession'',''type'' => ''INNER'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingSession.training_course_id'', ''TrainingSession.training_status_id''=>3)\n            ),\n            array(\n                ''table'' => ''training_course_target_populations'',''alias'' => ''TrainingCourseTargetPopulation'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id'', ''TrainingCourseTargetPopulation.position_title_table'' => ''teacher_position_titles'')\n            ),\n            array(''table'' => ''institution_site_teachers'',''alias'' => ''InstitutionSiteTeacher'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingCourseTargetPopulation.position_title_id = InstitutionSiteTeacher.teacher_position_title_id'')\n            ), \n            array(''table'' => ''teacher_position_titles'',''alias'' => ''TeacherPositionTitle'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TeacherPositionTitle.id = TrainingCourseTargetPopulation.position_title_id'')\n            ),\n            array(''table'' => ''teachers'',''alias'' => ''Teacher'',''type'' => ''LEFT'',\n                ''conditions'' => array(''Teacher.id IS NOT NULL'')\n            ),\n             array(\n                ''table'' => ''training_session_trainees'',''alias'' => ''TrainingSessionTrainee'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionTrainee.training_session_id'', ''TrainingSessionTrainee.identification_table''=>''teachers'')\n            ), \n             array(\n                ''table'' => ''training_session_results'',''alias'' => ''TrainingSessionResult'',''type'' => ''LEFT'',\n                ''conditions'' => array(''TrainingSession.id = TrainingSessionResult.training_session_id'', ''TrainingSessionResult.training_status_id''=>3)\n            ),\n         ),\n         ''conditions'' => \n         array(''TrainingCourse.training_status_id''=>3\n         ),\n         ''group'' => array(''TrainingCourse.id'', ''TrainingCourseTargetPopulation.position_title_table'', ''TrainingCourseTargetPopulation.position_title_id''),\n         ''order'' => array(''TrainingCourse.title'')\n        ));', 'CourseCode,CourseTitle,TargetGroup,TotalTargetGroup,TotalTrained,TargetGroupPercentage', 1, 1037, NULL, NULL, 1, '0000-00-00 00:00:00');
*/
DELETE from batch_reports where id between 1029 and 1037;
INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1029, "Training Course Report", "$this->autoRender = false; 
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
        ));", "CourseCode,CourseTitle,Status,CourseDescription,GoalObjective,Credit,Duration,ModeOfDelivery,Provider,Requirement,Level,Prerequisite", 1, 1029, NULL, NULL, 1, "0000-00-00 00:00:00");


INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1030, "Training Course Completed Report", "$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingCourse.code AS CourseCode','TrainingCourse.title AS CourseTitle',
            'GROUP_CONCAT(TrainingProvider.name) as Provider','TrainingCourse.credit_hours AS Credit', 
            'TrainingSession.location as Location', 'TrainingSession.start_date as StartDate',
            'TrainingSession.end_date as EndDate', 'Staff.identification_no as OpenEmisID',
            'Staff.first_name as FirstName', 'Staff.last_name as LastName', 
            'TrainingSessionTrainee.result as Result','((CASE WHEN TrainingSessionTrainee.pass=-1 THEN ""-"" WHEN TrainingSessionTrainee.pass=1 THEN ""Passed""
             ELSE ""Failed"" END)) AS Completed'
            
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
        ));", "CourseCode,CourseTitle,Provider,Credit,Location,StartDate,EndDate,OpenEmisID,FirstName,LastName,Result,Completed", 1, 1030, NULL, NULL, 1, "0000-00-00 00:00:00");


INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1031, "Staff Training Need Report", "$this->autoRender = false;
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
            '((CASE WHEN StaffTrainingNeed.ref_course_table =""TrainingNeedCategory"" THEN TrainingNeedCategory.name
             ELSE ""Course Catalogue"" END)) AS NeedType',
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
        ));", "NeedType,CourseCode,CourseTitle,Requirement,Priority,Comment,OpenEmisID,FirstName,LastName", 1, 1031, NULL, NULL, 1, "0000-00-00 00:00:00");

INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1032, "Training Course Uncompleted Report", "$this->autoRender = false;
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
        ));", "CourseCode,CourseTitle,Credit,Location,OpenEmisID,FirstName,LastName,StartDate,EndDate", 1, 1032, NULL, NULL, 1, "0000-00-00 00:00:00");


INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1033, "Training Trainer Report", "$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingSessionTrainer.ref_trainer_name as Trainer', 
            '((CASE WHEN TrainingSessionTrainer.ref_trainer_table =""Staff"" THEN ""Internal""
             ELSE ""External"" END)) AS TrainerType', 'TrainingCourse.code AS CourseCode',
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
        ));", 
	"Trainer,TrainerType,CourseCode,CourseTitle,Credit,Duration,Location,StartDate,EndDate", 1, 1033, NULL, NULL, 1, "0000-00-00 00:00:00");


INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1034, "Training Exception Report", " $this->autoRender = false;
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
        ));", "OpenEmisID,FirstName,LastName,Position,CourseCode,CourseTitle,Location,StartDate,EndDate", 1, 1034, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO `batch_reports` (`id`, `name`, `query`, `template`, `order`, `report_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1035, "Training Staff Statistic Report", "$this->autoRender = false;
        $TrainingCourse = ClassRegistry::init('TrainingCourse');
        $TrainingCourse->formatResult = true;
        $data = $TrainingCourse->find('all', 
        array('fields' => array(
            'TrainingCourse.code as CourseCode', 'TrainingCourse.title as CourseTitle', 'StaffPositionTitle.name as TargetGroup', 'COUNT(DISTINCT IFNULL(InstitutionSiteStaff.staff_id, Staff.id)) as TotalTargetGroup', 'COUNT(DISTINCT TrainingSessionTrainee.staff_id) as TotalTrained',
            'round(((COUNT(DISTINCT TrainingSessionTrainee.staff_id)/IFNULL(COUNT(DISTINCT IFNULL(InstitutionSiteStaff.staff_id, Staff.id)),0)) * 100),2)  as TargetGroupPercentage'
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
             array(
                'table' => 'institution_site_staff','alias' => 'InstitutionSiteStaff','type' => 'LEFT',
                'conditions' => array('TrainingSessionTrainee.staff_id = InstitutionSiteStaff.staff_id')
            ),  
            array(
                'table' => 'institution_site_positions','alias' => 'InstitutionSitePosition','type' => 'LEFT',
                'conditions' => array('InstitutionSiteStaff.institution_site_position_id = InstitutionSitePosition.id', 'TrainingCourseTargetPopulation.staff_position_title_id = InstitutionSitePosition.staff_position_title_id')
            ), 
            array(
                'table' => 'staff_position_titles','alias' => 'StaffPositionTitle','type' => 'LEFT',
                'conditions' => array('StaffPositionTitle.id = InstitutionSitePosition.staff_position_title_id')
            ), 
            array(
                'table' => 'staff','alias' => 'Staff','type' => 'LEFT',
                'conditions' => array('Staff.id IS NOT NULL')
            ),
            array(
                'table' => 'training_session_results','alias' => 'TrainingSessionResult','type' => 'LEFT',
                'conditions' => array('TrainingSession.id = TrainingSessionResult.training_session_id', 'TrainingSessionResult.training_status_id'=>3)
            ),
         ),
         'conditions' => array('TrainingCourse.training_status_id'=>3),
         'group' => array('TrainingCourse.id', 'TrainingCourseTargetPopulation.staff_position_title_id'),
         'order' => array('TrainingCourse.title')
        ));", "CourseCode,CourseTitle,TargetGroup,TotalTargetGroup,TotalTrained,TargetGroupPercentage", 1, 1035, NULL, NULL, 1, '0000-00-00 00:00:00');



/*
INSERT INTO `reports` (`id`, `name`, `description`, `file_type`, `module`, `category`, `header`, `footer`, `order`, `visible`, `enabled`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1029, 'Training Course Report', 'Report on all available courses by status', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1030, 'Training Course Completed Report', 'Report on all completed courses by teacher, date, location and results', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1031, 'Staff Training Need Report', 'Report on Staff training needs by course', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1032, 'Teacher Training Need Report', 'Report on Teacher training needs by course', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1033, 'Training Course Uncompleted Report', 'Report on what who has not completed a course by location', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1034, 'Training Trainer Report', 'Report of trainers by name, course and date', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1035, 'Training Exception Report', 'Report of Exceptions to see if a teacher is enrolled in two courses at the same time or has already completed the course', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1036, 'Training Staff Statistic Report', 'Report on the number of staff actually trained verses target groups for each program', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1037, 'Training Teacher Statistic Report', 'Report on the number of teachers actually trained verses target groups for each program', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');*/

DELETE from reports where id between 1029 and 1037;
INSERT INTO `reports` (`id`, `name`, `description`, `file_type`, `module`, `category`, `header`, `footer`, `order`, `visible`, `enabled`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1029, 'Training Course Report', 'Report on all available courses by status', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1030, 'Training Course Completed Report', 'Report on all completed courses by staff, date, location and results', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1031, 'Staff Training Need Report', 'Report on Staff training needs by course', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1032, 'Training Course Uncompleted Report', 'Report on who has not completed a course by location', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1033, 'Training Trainer Report', 'Report of trainers by name, course and date', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1034, 'Training Exception Report', 'Report of Exceptions to see if a staff is enrolled in two courses at the same time or has already completed the course', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),
(1035, 'Training Staff Statistic Report', 'Report on the number of staff actually trained verses target groups for each program', 'csv', 'Trainings', 'Training Reports', NULL, NULL, 1, 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
