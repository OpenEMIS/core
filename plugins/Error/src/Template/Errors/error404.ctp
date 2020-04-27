<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', '404 Forbidden');
$this->start('contentBody');

$baseUrl = $this->Url->build('/');
$baseUrl .= 'Dashboard';
?>

<div class="panel">
	<div class="panel-body">
		
		<div class="error-wrapper">
			<div class="error-icon">
				<i class="fa kd-404-error fa-5x"></i>
			</div>
			<div class="error-text">
				<h1><?php echo __('404 Forbidden: Page Not Found'); ?></h1>
				<h5><?php echo __('The page you are looking for might have been removed, renamed or is temporarily unavailable. If you have any enquiries, please contact the administrator'); ?> <a href="../About"><?php echo __('here'); ?></a>.</h5>
			</div>
			<div class="error-buttons">
				<a class="btn btn-default" href="javascript:history.back()"><i class="fa fa-chevron-left"></i> <?php echo __('Back');?></a>
				<a class="btn btn-default" href="<?=$baseUrl ?>"><i class="fa fa-home"></i> <?php echo __('Home');?></a>
			</div>	
		</div>
	</div>
</div> 

<style type="text/css">
	.breadcrumb,
	.page-header {
		display: none;
	}
</style>

<?php echo $this->end() ?>