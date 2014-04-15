<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$description = __d('open_emis', 'OpenEMIS: The Open Source Education Management Information System');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo $lang_locale; ?>" dir="<?php echo $lang_dir; ?>">
<head>
	<?php echo $this->Html->charset(); ?>
	<title><?php echo $description ?></title>
	<?php
		echo $this->Html->meta('favicon', $this->webroot . 'favicon.ico?v=2', array('type' => 'icon'));
		echo $this->fetch('meta');
		
		echo $this->Html->css('default/bootstrap.min');
		echo $this->Html->css('default/font-awesome.min');
		echo $this->Html->css('style');
		echo $this->Html->css('icons');
		echo $this->Html->css('fieldset');
		echo $this->Html->css('common');
		echo $this->Html->css('body_common');
		echo $this->Html->css('mask'); // for masking
		echo $this->Html->css('dialog'); // for dialogs
		echo $this->Html->css('alert'); // for alerts
		echo $this->fetch('css');
		if($lang_dir=='rtl') {
			echo $this->Html->css('rtl');
		}
		
		echo $this->Html->script('default/jquery-1.9.1.min');
		echo $this->Html->script('default/bootstrap.min');
		echo $this->Html->script('css_browser_selector');
		echo $this->Html->script('jquery.plugins');
		echo $this->Html->script('app.table');
		echo $this->Html->script('app');

		if($this->Session->check('WizardMode') && $this->Session->read('WizardMode')==true){
			echo $this->Html->script('wizard');
		}
		
		echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getI18n');
		echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');
		
		echo $this->fetch('script');
	?>
</head>

<body>
	<?php echo $this->element('layout/header'); ?>
	<div class="container">
		<?php echo $this->fetch('content'); ?>
		<?php echo $this->element('layout/footer'); ?>
	</div>
	<?php echo $this->element('debug/sql'); ?>
	<?php echo $this->fetch('scriptBottom'); ?>
</body>

</html>
