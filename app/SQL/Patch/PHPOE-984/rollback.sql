DELETE FROM `config_items` WHERE `name` LIKE 'alert_frequency' AND `type` LIKE 'Alerts';
DELETE FROM `config_items` WHERE `name` LIKE 'alert_retry' AND `type` LIKE 'Alerts';

UPDATE `navigations` SET `header` = 'SMS' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms' AND `header` LIKE 'Communications';
UPDATE `navigations` SET `title` = 'Messages' WHERE `module` LIKE 'Administration' AND `header` LIKE 'SMS' AND `title` LIKE 'Questions';

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;

--
-- new menu item Alerts to navigations
--

DELETE FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Communications' AND `title` LIKE 'Alerts';

SET @commuQuestionsOrderId := 0;
SELECT `order` INTO @commuQuestionsOrderId FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Communications' AND `title` LIKE 'Questions';

UPDATE `navigations` SET `order` = `order` - 1 WHERE `order` >= @commuQuestionsOrderId;

--
-- Table structure for table `alert_roles`
--

DROP TABLE IF EXISTS `alert_roles`;

--
-- Table structure for table `alert_logs`
--

DROP TABLE IF EXISTS `alert_logs`;

--
-- Table structure for table `system_processes`
--

DROP TABLE IF EXISTS `system_processes`;

--
-- part 2
--

UPDATE `security_functions` SET `name` = 'Messages' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms'  AND `name` LIKE 'Questions';

UPDATE `security_functions` SET `category` = 'SMS' WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Sms' AND `category` LIKE 'Communications';

SET @firstItemOrder := 0;
SELECT `order` INTO @firstItemOrder FROM `security_functions` WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Alerts'  AND `name` LIKE 'Alerts';

DELETE FROM `security_functions` WHERE `module` LIKE 'Administration' AND `controller` LIKE 'Alerts'  AND `name` LIKE 'Alerts';

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @firstItemOrder;