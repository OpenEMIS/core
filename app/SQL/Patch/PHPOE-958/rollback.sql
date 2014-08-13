--
-- 1. rollback changes to navigation institution bank account
--

UPDATE `reports` SET 
`name` = 'Institution Bank Account Report',
`category` = 'Institution Reports' 
WHERE `reports`.`id` =14;

--
-- 2. rollback changes to reports institution quality
--

UPDATE `reports` SET 
`name` = 'QA Schools Report',
`category` = 'Quality Assurance Reports' 
WHERE `reports`.`id` =3000;

UPDATE `reports` SET 
`name` = 'QA Results Report',
`category` = 'Quality Assurance Reports' 
WHERE `reports`.`id` =3001;

UPDATE `reports` SET 
`name` = 'QA Rubric Not Completed Report',
`category` = 'Quality Assurance Reports' 
WHERE `reports`.`id` =3002;

--
-- 3. rollback changes to reports student results
--

UPDATE `reports` SET 
`name` = 'Student Assessment Report',
`category` = 'Student Reports' 
WHERE `reports`.`id` =83;


