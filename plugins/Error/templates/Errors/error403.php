<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', '403 Forbidden');
$this->start('contentBody');

$baseUrl = $this->Url->build('/');
$baseUrl .= 'Dashboard';
?>

<div class="panel">
	<div class="panel-body">
		
		<div class="error-wrapper">
			<div class="error-icon">
				<i class="fa kd-403-error fa-5x"></i>
			</div>
			<div class="error-text">
				<h1><?php echo __('403 Forbidden: No Permission to Access');?></h1>
				<h5><?php echo __('You don\'t have permission to access "/Main/permissionError" on this server. If you believe you should be able to view this directory or page, please contact the administrator <a href="../About">here</a>'); ?> .</h5>
			</div>
			<div class="error-buttons">
				<a class="btn btn-default" href="javascript:history.back()"><i class="fa fa-chevron-left"></i> Back</a>
				<a class="btn btn-default" href="<?=$baseUrl?>"><i class="fa fa-home"></i> Home</a>
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