<?php
$description = __d('open_emis', 'OpenEMIS: The Open Source Education Management Information System');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo $lang_locale; ?>" dir="<?php echo $lang_dir; ?>">

<head>
	<?php echo $this->Html->charset(); ?>
	<title><?php echo $description ?></title>
	<?php
		echo $this->Html->meta('icon');
		
		echo $this->Html->css('common');
		echo $this->Html->css('login');
		echo $this->Html->css('style');
		
		if($lang_dir=='rtl') {
			echo $this->Html->css('rtl');
		}
		
		echo $this->Html->script('jquery');
		echo $this->Html->script('css_browser_selector');
	?>
	<style>
	.clear {
	*zoom: 1;
	clear: both;
}

.clear:before, .clear:after {
	display: table;
	line-height: 0;
	content: "";
}

.clear:after {
	clear: both;
}
</style>
</head>

<body onload="$('#SecurityUserUsername').focus()">

<div class="header">
    <div class="header_logo">
        <a href="http://www.openemis.org"><?php echo $this->Html->image('openemis_logo.png', array('title' => 'OpenEMIS')) ?></a>
    </div><!-- end header_logo -->
</div><!-- end header -->

<!--<div id="container_bg"></div>-->

<div class="container">
	<div id="open_title">
    	<span><?php echo __('Education Management Information System'); ?></span><br />
        <!-- <?php //echo __('Demo'); ?>-->
    </div>
	<!--<div id="country_design">
		<?php //echo $this->Html->image('flag/un.gif', array('title' => 'UN')) ?>
    </div>  end country_design -->
	<div class="login_container">
    	
		<div class="login_content">
			<h1><?php echo __('Login'); ?></h1>
			
			<?php
			echo $this->Form->create('SecurityUser', array(
				'url' => array(
					'controller' => 'Security',
					'action' => 'login')
					)
				);
			?>
			
			<?php echo $this->Session->flash(); ?>
			<?php echo $this->Session->flash('auth'); ?>
			
			<p><?php echo __('Username'); ?></p>
			<div class="login_input">
				<?php echo $this->Form->input('username', array('label' => false, 'div' => false, 'value' => $username)); ?>
			</div>
			<p><?php echo __('Password'); ?></p>
			<div class="login_input">
				<?php echo $this->Form->input('password', array('label' => false, 'div' => false, 'value' => $password)); ?>
			</div>
			
			<!--<div class="login_line"></div>-->
			
			<div class="login_btn">
				<?php echo $this->Form->submit('Login', array('class' => 'btn')); ?>
			</div>
			
			<?php echo $this->Form->end() ?>
		</div><!-- end login_content -->
		
        
	</div><!-- end login_container -->
</div><!-- end container -->

<div class="login_footer" style="position:fixed;">
<!-- footer -->
    <!-- (2)*****************************************-->
    <div class="footer">
        <div class="language" dir="ltr">
        	<div class="clear">
        	<?php
        		foreach($images as $image){
					 echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $image["id"]), array(
		                'height'=>"125px"
		            	));
				}
			?>
			</div>
			<br />
            <?php 
				if($this->Session->check('footer')){
					echo $this->Session->read('footer');
				} else {
					echo "&copy; ".date("Y")." openemis.org";
				}
			?>
        </div>
    </div><!-- end footer -->
    <!-- ******************************************end footer(2) -->
</div>

</body>
</html>
