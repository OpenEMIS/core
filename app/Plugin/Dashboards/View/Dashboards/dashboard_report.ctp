<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
	.col_age { width: 90px; text-align: center; }
	.col_name { width: 130px; }
	.col_desc { width: 290px; }
	.col_lastgen { width: 70px; }
</style>

<?php
$ctr = 0;
if (@$enabled === true) {
	if (count($data) > 0) {
		//pr($data);
		?>
		<div id="report-list" class="content_wrapper">
			<?php foreach ($data as $module => $arrVals) { ?>
				<h1>
					<span><?php echo __(ucwords($module)); ?></span> 
				</h1>

				<div id="alertError" title="Click to dismiss" class="alert alert-error" style="position:relative; margin-bottom: 10px;display: <?php echo ($msg != '') ? 'block' : 'none'; ?>; opacity: 0.891195;"><div class="alert-icon"></div><div class="alert-content"><?php echo __('The selected report is currently being processed.'); ?></div></div>

				<?php foreach ($arrVals as $type => $arrTypVals) { ?>
				<?php /*	<fieldset class="section_group">
						<legend><?php echo __($type); ?></legend> */ ?>
				<div class="table allow_hover" style="width:688px" action="Dashboards/overview/">
							<div class="table_head">
								<div class="table_cell col_name"><?php echo __('Name'); ?></div>
								<div class="table_cell col_desc"><?php echo __('Description'); ?></div> 
							</div> 

							<div class="table_body">
								<?php
								$ctr = 1;
								foreach ($arrTypVals as $key => $value) {
									?>
									<div class="table_row" row-id="<?php echo $value['id']; ?>">
										<div class="table_cell col_name"><?php echo __($value['name']); ?></div>
										<div class="table_cell col_desc"><?php echo __($value['description']); ?></div>
									</div>
									<?php $ctr++;
								} ?>
							</div>
						</div>
				<?php /*	</fieldset> */ ?>
				<?php } ?>
		<?php } ?>
		</div>
		<?php
		$ctr++;
	}
} else {
	echo __('Report Feature disabled');
}
?>