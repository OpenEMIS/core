--
-- 1. restore the original table students
--

DROP TABLE IF EXISTS `students`;

RENAME TABLE `students_bak` TO `students` ;

--
-- 2. restore the original table staff
--

DROP TABLE IF EXISTS `staff`;

RENAME TABLE `staff_bak` TO `staff` ;