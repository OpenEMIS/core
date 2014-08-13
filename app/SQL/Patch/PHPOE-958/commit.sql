--
-- 1. changes to reports institution bank account
--

UPDATE `reports` SET 
`name` = 'Bank Accounts',
`category` = 'Institution Finance Reports' 
WHERE `reports`.`id` =14;

--
-- 2. changes to reports institution quality
--

UPDATE `reports` SET 
`name` = 'Schools',
`category` = 'Institution Quality Reports' 
WHERE `reports`.`id` =3000;

UPDATE `reports` SET 
`name` = 'Results',
`category` = 'Institution Quality Reports' 
WHERE `reports`.`id` =3001;

UPDATE `reports` SET 
`name` = 'Rubric Not Completed',
`category` = 'Institution Quality Reports' 
WHERE `reports`.`id` =3002;

--
-- 3. changes to reports student results
--

UPDATE `reports` SET 
`name` = 'Results',
`category` = 'Student Details Reports' 
WHERE `reports`.`id` =83;

