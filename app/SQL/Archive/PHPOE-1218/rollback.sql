--
-- 1. navigations
--

UPDATE `navigations`
SET `visible`='1' 
WHERE `controller`='census'
	AND `action`='infrastructure'

--
-- 2. security_functions
--

UPDATE `security_functions`
SET `visible`='1' 
WHERE `controller`='census'
	AND `module`='institutions'
	AND `category`='totals'
	AND `_view`='infrastructure'
