<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
if (count($data) > 0) {
	$this->assign('contentHeader', __(ucwords(key($data))));
} else {
	$this->assign('contentHeader', __('Custom Reports'));
}
$this->start('contentActions');
echo $this->Html->link(__('List'), array('action' => 'index'), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'report-list');
$this->start('contentBody');
?>
<style type="text/css">
	.col_age { width: 90px; text-align: center; }
	.col_name { width: 130px; }
	.col_desc { width: 300px; }
	.col_lastgen { width: 70px; }
</style>

<script>
	var maskId;
	var Report = {
		id: 0,
		Part: 0,
		TotalRecords: 0,
		LimitPerRun: 0,
		ProgressTpl: '<div id="prog_wrapper_{id}" >Complete <div id="prog_count_{id}" style="display:inline">0%</div> <img id="prog_img_{id}" style="vertical-align:middle" src="http://dev.openemis.org/demo/img/icons/loader.gif" ></div>',
		init: function() {
		},
		progressComplete: function() {
			$("#progressbar").html('0%');

			window.location = getRootURL() + 'Reports/download/' + Report.id;
			$.closeDialog();


		},
		genReport: function(batch) {

			$.ajax({
				type: 'GET',
				dataType: "json",
				url: getRootURL() + "Reports/genReport/" + Report.id + '/' + batch,
				beforeSend: function(jqXHR) {

				},
				success: function(data, textStatus) {
					if (data.processed_records >= Report.TotalRecords) {
						percentage = 100;
						Report.progressComplete();
					} else {
						var percentage = Math.floor(100 * parseInt(data.processed_records) / parseInt(Report.TotalRecords));
						console.log(percentage);
						Report.part = data.batch;
						Report.genReport(Report.part);
					}
					//$("#uploadprogressbar").progressBar(percentage);
					$("#progressbar").html(percentage + '%');

				}
			});

		}

	}
	$(document).ready(function() {
		Report.init();

		setTimeout(function() {
			$('#alertError').fadeOut(2000);
		}, 3000);
		// $("#progressbar").progressbar({ value: 37 });

	});
</script>
<?php
$ctr = 0;
if (@$enabled === true):
	// pr($data);
	if (count($data) > 0):
		?>
		<?php foreach ($data as $category => $arrCategoryVals): ?>
			<div id="alertError" title="Click to dismiss" class="alert alert-error" style="position:relative; margin-bottom: 10px;display: <?php echo ($msg != '') ? 'block' : 'none'; ?>; opacity: 0.891195;"><div class="alert-icon"></div><div class="alert-content"><?php echo __('The selected report is currently being processed.'); ?></div></div>
			<?php foreach ($arrCategoryVals as $module => $arrModuleVals): ?>
				<?php if (count($arrCategoryVals) > 1): ?>
					<fieldset class="section_group">
						<legend><?php echo __($module); ?></legend>
					<?php endif; ?>
					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered">
							<thead>
								<tr>
									<td class="col_name"><?php echo __('Name'); ?></td>
									<td class="col_desc"><?php echo __('Description'); ?></td>
								</tr> 
							</thead> 

							<tbody>
								<?php
								$ctr = 1;
								foreach ($arrModuleVals as $key => $value):
									?>
									<tr row-id="<?php echo $value['id']; ?>">
										<td class="col_name"><?php echo $this->Html->link(__($value['name']), array('action' => $this->action, $value['id']), array('escape' => false)); ?></td>
										<td class="col_desc"><?php echo __($value['description']); ?></td>
									</tr>
									<?php
									$ctr++;
								endforeach;
								?>
							</tbody>
						</table>
					</div>
					<?php if (count($arrCategoryVals) > 1): ?>
					</fieldset>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
		<?php
		$ctr++;
	else:
		?> 
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<td class="col_name"><?php echo __('Name'); ?></td>
						<td class="col_desc"><?php echo __('Description'); ?></td>
					</tr>
				</thead>

			</table>	
		</div>
		<div style="width:100%;margin:15px 5px;"><?php echo __('Please contact'); ?> <a href="<?php echo $this->Html->url(array('plugin' => null, 'controller' => 'Home', 'action' => 'support')) ?>"> <?php echo __('support'); ?> </a> <?php echo __('for more information on Custom Reports.'); ?></div>
	<?php
	endif;
else:
	echo __('Report Feature disabled');

endif;
?>
<?php $this->end(); ?>