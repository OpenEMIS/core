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
		
		echo $this->Html->script('css_browser_selector');
		echo $this->Html->script('jquery');
		echo $this->Html->script('jquery.plugins');
		echo $this->Html->script('app');
		echo $this->Html->script('bootstrap');
		
		echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getI18n');
		echo sprintf('<script type="text/javascript" src="%s%s"></script>', $this->webroot, 'Config/getJSConfig');
		
		echo $this->fetch('script');
		
		//echo $this->Js->writeBuffer(array('cache'=>FALSE));
	?>
</head>
<?php 
$firstName = AuthComponent::user('first_name');
$lastName = AuthComponent::user('last_name');
?>
<body>
	<div class="header">
    	<div class="header_content">
        	<div class="header_logo">
            	<a href="<?php echo $this->base . '/Home' ?>">
					<?php echo $this->Html->image('openemis_logo.png', array('title' => 'OpenEMIS')) ?>
				</a>
            </div><!-- end header_logo -->
			
			<div style="overflow: hidden;">
				<div class="header_side_nav">
					<div id="user_name"><?php echo sprintf('%s, %s %s', __('Welcome'), $firstName, $lastName); ?></div>
					<div id="header_side_nav_container">
						<?php
						$link = sprintf('<a href="%s%%s">%%s</a>', $this->webroot);
						$divider = '<div class="header_side_nav_function_divi"></div>';
						echo sprintf($link, 'Home/index', __('Home'));
						echo $divider;
						echo sprintf($link, 'Home/details', __('Account'));
						echo $divider;
						echo sprintf($link, 'Home/support', __('Support'));
						echo $divider;
						echo sprintf($link, 'Security/logout', __('Logout'));
						?>
					</div>
				</div><!-- end header_side_nav -->
            </div>
			
			<?php echo $this->element('top_nav'); ?>
        </div><!-- end header_content -->
    </div><!-- end header -->
	
	<div class="container">
		<?php echo $this->Session->flash(); ?>
		<?php echo $this->Session->flash('auth'); ?>
		
		<?php if(strlen($bodyTitle) > 0) { // bodyTitle comes together with left navigation ?>
		<div class="body_title"><?php echo __($bodyTitle); ?></div>
		<div class="body_content">
			<?php echo $this->element('left_nav'); ?>
			<div class="body_content_right"><?php echo $this->fetch('content'); ?></div>
		</div>
		<?php
		} else {
			echo $this->fetch('content');
		} 
		?>
	
		<div class="footer" lang="en" dir="ltr">
			<div class="language">
				<!-- &copy; 2012 openemis.org -->
				<?php 
				if($this->Session->check('footer')){
					echo $this->Session->read('footer');
				}
				?>
			</div>
		</div><!-- end footer -->
		
	</div>
	
	<?php if(strpos($_SERVER['SERVER_NAME'], 'dev') !== false
		  || strpos($_SERVER['SERVER_NAME'], 'tst') !== false
		  || strpos($_SERVER['SERVER_NAME'], 'localhost') !== false) { ?>
	
	<style type="text/css">
	.sql-dump { margin-top: 30px; }
	.sql-dump table { border: 1px solid #CCC; margin: 10px; }
	.sql-dump th { padding: 3px; }
	.sql-dump td { border-top: 1px solid #CCC; padding: 5px 0; }
	.sql-dump .query { width: 900px; }
	</style>
	
    <div class="sql-dump" align="center">
		<?php echo $sql_dump = $this->element('sql_dump'); ?>
	</div>
	
	<?php } ?>
</body>

</html>


