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