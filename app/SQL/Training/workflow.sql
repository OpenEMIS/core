/*
INSERT INTO `workflows` (`id`, `model_name`, `workflow_name`, `action`, `approve`, `visible`, `order`, `parent_id`, `lft`, `rght`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'TrainingCourse', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 1, 2, NULL, '2014-06-19 10:58:26', 1, '2014-04-09 00:00:00'),
(2, 'TrainingCourse', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 27, 28, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(3, 'TrainingCourse', 'Pending for Accreditation', 'Accredit', 'Accredited', 1, 3, NULL, 25, 26, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(4, 'TrainingSession', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 23, 24, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(5, 'TrainingSession', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 21, 22, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(6, 'TrainingSession', 'Pending for Registration', 'Register', 'Registered', 1, 3, NULL, 19, 20, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(7, 'TrainingSessionResult', 'Pending for Evaluation', 'Evaluate', '', 1, 1, NULL, 17, 18, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(8, 'TrainingSessionResult', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 15, 16, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(9, 'TrainingSessionResult', 'Pending for Posting', 'Post', 'Posted', 1, 3, NULL, 13, 14, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(10, 'StaffTrainingNeed', 'Pending for Approval', 'Approve', 'Approved', 1, 1, NULL, 11, 12, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(11, 'StaffTrainingSelfStudy', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 9, 10, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(12, 'StaffTrainingSelfStudy', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 7, 8, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(13, 'StaffTrainingSelfStudy', 'Pending for Accreditation', 'Accredit', 'Accredited', 1, 3, NULL, 3, 6, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(14, 'StaffTrainingSelfStudyResult', 'Pending for Result Approval', 'Approve', 'Approved', 1, 2, 13, 4, 5, NULL, '2014-06-19 10:58:26', 1, '2014-06-17 00:00:00');*/

truncate table workflows;

INSERT INTO `workflows` (`id`, `model_name`, `workflow_name`, `action`, `approve`, `visible`, `order`, `parent_id`, `lft`, `rght`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'TrainingCourse', 'معلقة لتوصية', 'نوصي', '', 1, 1, NULL, 1, 2, NULL, '2014-06-19 10:58:26', 1, '2014-04-09 00:00:00'),
(2, 'TrainingCourse', 'في انتظار الموافقة ل', 'وافق', '', 1, 2, NULL, 27, 28, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(3, 'TrainingCourse', 'بانتظار الاعتماد', 'فوض', 'المعتمدة', 1, 3, NULL, 25, 26, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(4, 'TrainingSession', 'معلقة لتوصية', 'نوصي', '', 1, 1, NULL, 23, 24, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(5, 'TrainingSession', 'في انتظار الموافقة ل', 'وافق', '', 1, 2, NULL, 21, 22, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(6, 'TrainingSession', 'معلقة لتسجيل', 'تسجيل', 'مسجل', 1, 3, NULL, 19, 20, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(7, 'TrainingSessionResult', 'معلقة لتقييم', 'تقييم', '', 1, 1, NULL, 17, 18, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(8, 'TrainingSessionResult', 'في انتظار الموافقة ل', 'وافق', '', 1, 2, NULL, 15, 16, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(9, 'TrainingSessionResult', 'معلقة لإرسال', 'بعد', 'نشرت', 1, 3, NULL, 13, 14, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(10, 'StaffTrainingNeed', 'في انتظار الموافقة ل', 'وافق', 'وافق', 1, 1, NULL, 11, 12, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(11, 'StaffTrainingSelfStudy', 'معلقة لتوصية', 'نوصي', '', 1, 1, NULL, 9, 10, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(12, 'StaffTrainingSelfStudy', 'في انتظار الموافقة ل', 'وافق', '', 1, 2, NULL, 7, 8, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(13, 'StaffTrainingSelfStudy', 'بانتظار الاعتماد', 'فوض', 'المعتمدة', 1, 3, NULL, 3, 6, NULL, '2014-06-19 10:58:26', 1, '2014-04-11 00:00:00'),
(14, 'StaffTrainingSelfStudyResult', 'في انتظار لنتائج القبول', 'وافق', 'وافق', 1, 2, 13, 4, 5, NULL, '2014-06-19 10:58:26', 1, '2014-06-17 00:00:00');