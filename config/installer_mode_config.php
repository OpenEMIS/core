<?php
    
	$mode = array(
			'core' => true,
			'census' => false,
			'school' => false,
			'vaccinations' => false
	);

	$count_mode_value = array_sum($mode);
	$database_dump_file = 'prd_cor_zip';
	$application_name = $application_colour = $application_login_image = $application_sql_name = $application_db_user_name = $application_mode = $application_theme = $application_favicon = '';
	
	if($mode['core'] == true){
		$application_mode = 'core';
		$application_name = 'OpenEMIS Core';
		$application_colour = '6699CC';
		$application_login_image = 'OpenEMIS_Core_Login_Image_Reduced.jpg';
		$application_sql_name = 'prd_cor_dmo';
		$application_db_user_name = 'prd_core_user';
		$application_theme = 'core';
		$application_favicon = '_core';
	}else if($mode['census'] == true){
		$application_mode = 'census';
		$application_name = 'OpenEMIS Census';
		$application_colour = '0099FF';
		$application_login_image = 'OpenEMIS_Census_Login_Image_Reduced.jpg';
		$application_sql_name = 'prd_cen_dmo';
		$application_db_user_name = 'prd_cen_user';
		$application_theme = 'census';
		$application_favicon = '_census';
	}else if($mode['school'] == true){
		$application_mode = 'school';
		$application_name = 'OpenEMIS School';
		$application_colour = '3366CC';
		$application_login_image = 'OpenEMIS_School_Login_Image_Reduced.jpg';
		$application_sql_name = 'prd_school_dmo';
		$application_db_user_name = 'prd_school_user';
		$application_theme = 'school';
		$application_favicon = '_school';
	}else if($mode['vaccinations'] == true){
		$application_mode = 'vaccinations';
		$application_name = 'OpenEMIS Vaccinations';
		$application_colour = '00CCFF';
		$application_login_image = 'OpenEMIS_Vaccinations_Login_Image_Reduced.jpg';
		$application_sql_name = 'prd_vac_dmo';
		$application_db_user_name = 'prd_vac_user';
		$application_theme = 'vaccinations';
		$application_favicon = '_vaccinations';
	}


	if (!defined('APPLICATION_NAME')) define('APPLICATION_NAME', $application_name);
	if (!defined('APPLICATION_MODE')) define('APPLICATION_MODE', $application_mode);
	if (!defined('APPLICATION_COLOUR')) define('APPLICATION_COLOUR', $application_colour);
	if (!defined('APPLICATION_LOGIN_IMAGE')) define('APPLICATION_LOGIN_IMAGE', $application_login_image);
	if (!defined('APPLICATION_DB_NAME')) define('APPLICATION_DB_NAME', $application_sql_name);
	if (!defined('APPLICATION_DB_USER_NAME')) define('APPLICATION_DB_USER_NAME', $application_db_user_name);
	if (!defined('APPLICATION_THEME')) define('APPLICATION_THEME', $application_theme);
	if (!defined('APPLICATION_FAVICON')) define('APPLICATION_FAVICON', $application_favicon);
	if (!defined('DATABASE_DUMP_FILE')) define('DATABASE_DUMP_FILE', $database_dump_file);
	if (!defined('APPLICATION_MODE_COUNT')) define('APPLICATION_MODE_COUNT', $count_mode_value);
	
	
	
	
    
