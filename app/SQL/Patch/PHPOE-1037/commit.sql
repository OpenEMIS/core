DELETE FROM `navigations` WHERE `controller` = 'Visualizer';

SET @funcId := 0;
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Visualizer';

DELETE FROM `security_role_functions` WHERE `security_function_id` = @funcId;
DELETE FROM `security_functions` WHERE `id` = @funcId;
