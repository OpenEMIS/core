
TRUNCATE TABLE `student_categories`;
--
-- Dumping data for table `student_categories`
--

INSERT INTO `student_categories` (`id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'Promoted or New Enrolment', 1, 1, NULL, NULL, 1, '2013-01-24 13:57:26', 1, '2010-10-12 14:57:00'),
(2, 'Promoted (Transferred in)', 2, 1, NULL, NULL, 1, '2013-01-24 13:57:26', 1, '2010-10-12 14:57:00'),
(3, 'Repeated', 3, 1, NULL, NULL, 1, '2013-01-24 13:57:26', 1, '2010-10-12 14:57:00'),
(4, 'Repeated (Transferred in)', 4, 1, NULL, NULL, 1, '2013-01-24 13:57:26', 1, '2010-10-12 14:57:00'),
(5, 'Transfer Student (Local)', 5, 1, NULL, NULL, 1, '2013-05-08 12:47:27', 1, '2013-01-24 13:57:26'),
(6, 'Transfer Student (Overseas)', 6, 1, NULL, NULL, 1, '2013-05-08 12:47:27', 1, '2013-01-24 13:57:26');


Update institution_site_class_students set student_category_id=1
where student_category_id is null or student_category_id = 0;
