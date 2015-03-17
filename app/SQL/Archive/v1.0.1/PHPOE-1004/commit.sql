UPDATE `navigations` SET `pattern` = 'Education|reorder' WHERE `controller` = 'Education' AND `title` = 'Education Structure';

UPDATE `security_functions` SET 
`name` = 'Education Systems',
`_view` = 'index|EducationSystem.index|EducationSystem.view',
`_edit` = '_view:EducationSystem.edit|reorder',
`_add` = '_view:EducationSystem.add'
WHERE `controller` = 'Education'
AND `name` = 'Setup';

SET @ordering := 0;
SET @parentId := 0;

SELECT MAX(`order`) INTO @ordering FROM `security_functions`;
SELECT `id` INTO @parentId FROM `security_functions` WHERE `controller` = 'Education' AND `name` = 'Education Systems';

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'Education Levels', 'Education', 'Administration', 'Education', @parentId, 'EducationLevel.index|EducationLevel.view', 'EducationLevel.edit', 'EducationLevel.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Cycles', 'Education', 'Administration', 'Education', @parentId, 'EducationCycle.index|EducationCycle.view', 'EducationCycle.edit', 'EducationCycle.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Programmes', 'Education', 'Administration', 'Education', @parentId, 'EducationProgramme.index|EducationProgramme.view', 'EducationProgramme.edit', 'EducationProgramme.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Grades', 'Education', 'Administration', 'Education', @parentId, 'EducationGrade.index|EducationGrade.view', 'EducationGrade.edit', 'EducationGrade.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Grade - Subjects', 'Education', 'Administration', 'Education', @parentId, 'EducationGradeSubject.index', 'EducationGradeSubject.edit', NULL, (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Subjects', 'Education', 'Administration', 'Education', @parentId, 'EducationSubject.index|EducationSubject.view', 'EducationSubject.edit', 'EducationSubject.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Certifications', 'Education', 'Administration', 'Education', @parentId, 'EducationCertification.index|EducationCertification.view', 'EducationCertification.edit', 'EducationCertification.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Field of Study', 'Education', 'Administration', 'Education', @parentId, 'EducationFieldOfStudy.index|EducationFieldOfStudy.view', 'EducationFieldOfStudy.edit', 'EducationFieldOfStudy.add', (@ordering := @ordering + 1), 1, 1, NOW()),
(NULL, 'Education Programme Orientations', 'Education', 'Administration', 'Education', @parentId, 'EducationProgrammeOrientation.index|EducationProgrammeOrientation.view', 'EducationProgrammeOrientation.edit', 'EducationProgrammeOrientation.add', (@ordering := @ordering + 1), 1, 1, NOW());
