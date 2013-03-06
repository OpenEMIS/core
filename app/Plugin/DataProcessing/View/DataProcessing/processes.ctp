<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$status = array(
	'-1'=> '<span class="red">' . __('Error') . '</span>',
	'1'=> '<span class="orange">' . __('Pending') . '</span>',
	'2'=> '<span class="orange">' . __('Processing') . '</span>',
	'3'=> '<span class="green">' . __('Completed') . '</span>',
	'4'=> '<span class="red">' . __('Aborted') . '</span>'
);
?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
.cell_status { width: 60px; }
.cell_log { min-width: 30px !important; width: 30px; }
.cell_date { width: 110px; }
.cell_process { width: 150px; }
.cell_abort { min-width: 45px !important; width: 45px;  }
</style>
<script>
function killallprocess(){
	window.location = getRootURL()+'/DataProcessing/processes/kill';
}
function clearallprocess(){
	$.ajax({
		url: getRootURL()+'/DataProcessing/processes/clear',
		success: function(data){
			if(data == ''){
				$('.table_body').html('');
			}

		}
	});
	
}
</script>

<div id="processes" class="content_wrapper">
	<?php
	echo $this->Form->create('DataProcessing', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'DataProcessing', 'action' => 'processes')
	));
	?>
	<h1>
		<span><?php echo __('Processes'); ?></span>
		<a class="void divider" href="javascript: void(0);" onclick="killallprocess();"><?php echo __('Abort All'); ?></a>
		<a class="void divider" href="javascript: void(0);" onclick="clearallprocess();"><?php echo __('Clear All'); ?></a>
	</h1>
	
	<div class="table full_width" style="margin-left: 3px;">
		<div class="table_head">
			<div class="table_cell cell_process"><?php echo __('Process'); ?></div>
			<div class="table_cell"><?php echo __('Started By'); ?></div>
			<div class="table_cell cell_date"><?php echo __('Started Date'); ?></div>
			<div class="table_cell cell_date"><?php echo __('Finished Date'); ?></div>
			<div class="table_cell cell_status"><?php echo __('Status'); ?></div>
			<div class="table_cell cell_log"><?php echo __('Log'); ?></div>
			<!--div class="table_cell cell_abort">Abort</div-->
		</div>
		<?php // pr($data); ?>
		<div class="table_body">
			<?php foreach($data as $k => $v) { //pr($v);die;?>
			<div class="table_row">
				<div class="table_cell"><?php echo __($v['BatchProcess']['name']) .((isset($v['Report']))?' ('.$v['Report']['file_type'].') ':'');?></div>
				<div class="table_cell"><?php echo $v['BatchProcess']['startedBy'];?></div>
				<div class="table_cell center"><?php echo $v['BatchProcess']['start_date'];?></div>
				<div class="table_cell center"><?php echo $v['BatchProcess']['finish_date'];?></div>
				<div class="table_cell center"><?php echo $status[$v['BatchProcess']['status']];?></div>
				<div class="table_cell center"><a href="javascript:void(0);" onClick="window.location=getRootURL()+'/DataProcessing/downloadLog/<?php echo $v['BatchProcess']['id']; ?>'"><?php echo ($v['BatchProcess']['file_exists'])?'Log':''; ?></a></div>
				<!--div class="table_cell"><input type="button" value="Abort" <?php echo ($v['BatchProcess']['status'] == 1?'':'Disabled');?>/></div-->
			</div>
			<?php } ?>
		</div>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>