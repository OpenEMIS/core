--
-- 1. Navigations
--

UPDATE `navigations` SET `plugin` = NULL, `controller` = 'Quality', `action` = 'rubricsTemplates' , `pattern` = 'rubricsTemplates' WHERE `module` = 'Administration' AND `controller` = 'QualityRubrics' AND `header` = 'Quality' AND `title` = 'Rubrics';
UPDATE `navigations` SET `plugin` = NULL, `controller` = 'Quality', `action` = 'status' , `pattern` = 'status' WHERE `module` = 'Administration' AND `controller` = 'QualityStatuses' AND `header` = 'Quality' AND `title` = 'Status';

UPDATE `navigations` SET `plugin` = NULL, `controller` = 'Quality', `action` = 'qualityRubric' , `pattern` = 'qualityRubric' WHERE `module` = 'Institution' AND `controller` = 'InstitutionSites' AND `header` = 'Quality' AND `title` = 'Rubrics';
UPDATE `navigations` SET `plugin` = NULL, `controller` = 'Quality', `action` = 'qualityVisit' , `pattern` = 'qualityVisit' WHERE `module` = 'Institution' AND `controller` = 'InstitutionSites' AND `header` = 'Quality' AND `title` = 'Visits';

--
-- 2. Security Functions
--

UPDATE `security_functions` SET `controller` = 'Quality', `_view` = 'rubricsTemplates|rubricsTemplatesView|rubricsTemplatesHeader|rubricsTemplatesHeaderView|rubricsTemplatesSubheader|rubricsTemplatesSubheaderView|rubricsTemplatesCriteria|rubricsTemplatesCriteriaView', `_edit` = '_view:rubricsTemplatesEdit|rubricsTemplatesHeaderEdit|rubricsTemplatesHeaderOrder|rubricsTemplatesSubheaderEdit|rubricsTemplatesCriteriaEdit|rubricsTemplatesCriteriaOrder' , `_add` = '_view:rubricsTemplatesAdd|rubricsTemplatesHeaderAdd|rubricsTemplatesSubheaderAdd|rubricsTemplatesCriteriaAdd', `_delete` = '_view:rubricsTemplatesDelete|rubricsTemplatesHeaderDelete|rubricsTemplatesSubheaderDelete|rubricsTemplatesCriteriaDelete' WHERE `controller` = 'QualityRubrics' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Rubrics';
UPDATE `security_functions` SET `controller` = 'Quality', `_view` = 'status|statusView', `_edit` = '_view:statusEdit' , `_add` = '_view:statusAdd', `_delete` = '_view:statusDelete' WHERE `controller` = 'QualityStatuses' AND `module` = 'Administration' AND `category` = 'Quality' AND `name` = 'Status';

UPDATE `security_functions` SET `controller` = 'Quality', `_view` = 'qualityRubric|qualityRubricView|qualityRubricHeader|qualityRubricAnswerView', `_edit` = '_view:qualityRubricEdit' , `_add` = '_view:qualityRubricAdd', `_delete` = '_view:qualityRubricDelete', `_execute` = '_view:qualityRubricAnswerExec' WHERE `controller` = 'InstitutionSites' AND `module` = 'Institutions' AND `category` = 'Quality' AND `name` = 'Rubrics';
UPDATE `security_functions` SET `controller` = 'Quality', `_view` = 'qualityVisit|qualityVisitView', `_edit` = '_view:qualityVisitEdit' , `_add` = '_view:qualityVisitAdd', `_delete` = '_view:qualityVisitDelete', `_execute` = '_view:qualityVisitExcel' WHERE `controller` = 'InstitutionSites' AND `module` = 'Institutions' AND `category` = 'Quality' AND `name` = 'Visits';

--
-- 3. Drop new tables
--

DROP TABLE IF EXISTS `rubric_templates`;
DROP TABLE IF EXISTS `rubric_template_options`;
DROP TABLE IF EXISTS `rubric_sections`;
DROP TABLE IF EXISTS `rubric_criterias`;
DROP TABLE IF EXISTS `rubric_criteria_options`;
DROP TABLE IF EXISTS `quality_statuses`;
DROP TABLE IF EXISTS `quality_status_periods`;
DROP TABLE IF EXISTS `quality_status_programmes`;
DROP TABLE IF EXISTS `quality_status_roles`;

DROP TABLE IF EXISTS `institution_site_quality_rubrics`;
DROP TABLE IF EXISTS `institution_site_quality_rubric_answers`;
DROP TABLE IF EXISTS `institution_site_quality_visits`;
DROP TABLE IF EXISTS `institution_site_quality_visit_attachments`;

--
-- 4. Restore tables
--

RENAME TABLE z_1285_rubrics_templates TO rubrics_templates;
RENAME TABLE z_1285_rubrics_template_answers TO rubrics_template_answers;
RENAME TABLE z_1285_rubrics_template_column_infos TO rubrics_template_column_infos;
RENAME TABLE z_1285_rubrics_template_grades TO rubrics_template_grades;
RENAME TABLE z_1285_rubrics_template_headers TO rubrics_template_headers;
RENAME TABLE z_1285_rubrics_template_items TO rubrics_template_items;
RENAME TABLE z_1285_rubrics_template_subheaders TO rubrics_template_subheaders;
RENAME TABLE z_1285_quality_statuses TO quality_statuses;

RENAME TABLE z_1285_quality_institution_rubrics TO quality_institution_rubrics;
RENAME TABLE z_1285_quality_institution_rubrics_answers TO quality_institution_rubrics_answers;
RENAME TABLE z_1285_quality_institution_visits TO quality_institution_visits;
RENAME TABLE z_1285_quality_institution_visit_attachments TO quality_institution_visit_attachments;
