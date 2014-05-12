<?php 
$firstName = AuthComponent::user('first_name');
$lastName = AuthComponent::user('last_name');
?>

<div class="header">
	<div class="header_content">
		<div class="header_logo">
			<a href="<?php echo $this->base . '/Home' ?>">
				<?php echo $this->Html->image('openemis_logo.png', array('title' => 'OpenEMIS')) ?>
			</a>
		</div><!-- end header_logo -->
		
		<div>
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
					echo sprintf('<a href="%s%s" class="logout">%s</a>', $this->webroot, 'Security/logout', __('Logout'));
					?>
				</div>
			</div><!-- end header_side_nav -->
		</div>
		<?php echo $this->element('layout/top_nav'); ?>
	</div><!-- end header_content -->
</div><!-- end header -->