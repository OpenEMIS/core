<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('DataProcessing.process'));
$this->start('contentActions');
if($_execute) { ?>
	<a class="void divider" href="javascript: void(0);" onclick="killallprocess();"><?php echo __('Abort All'); ?></a>
	<a class="void divider" href="javascript: void(0);" onclick="clearallprocess();"><?php echo __('Clear All'); ?></a>
<?php }
$this->end();
$status = array(
	'-1'=> '<span class="red">' . __('Error') . '</span>',
	'1'=> '<span class="orange">' . __('Pending') . '</span>',
	'2'=> '<span class="orange">' . __('Processing') . '</span>',
	'3'=> '<span class="green">' . __('Completed') . '</span>',
	'4'=> '<span class="red">' . __('Aborted') . '</span>'
);
$this->assign('contentId', 'processes');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<style type="text/css">
.cell_status { width: 60px; }
.cell_log { min-width: 30px !important; width: 30px; }
.cell_date { width: 110px; }
.cell_process { width: 150px; }
.cell_abort { min-width: 45px !important; width: 45px;  }
</style>
<script>
function killallprocess(){
	window.location = getRootURL()+'DataProcessing/processes/kill';
}
function clearallprocess(){
	$.ajax({
		url: getRootURL()+'DataProcessing/processes/clear',
		success: function(data){
			if(data == ''){
				$('.table_body').html('');
			}

		}
	});
	
}
</script>
<?php
echo $this->Form->create('DataProcessing', array(
	'id' => 'submitForm',
	'inputDefaults' => array('label' => false, 'div' => false),	
	'url' => array('controller' => 'DataProcessing', 'action' => 'processes')
));
?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
	<thead class="table_head">
		<tr>
			<td class="table_cell cell_process"><?php echo __('Process'); ?></td>
			<td class="table_cell"><?php echo __('Started By'); ?></td>
			<td class="table_cell cell_date"><?php echo __('Started Date'); ?></td>
			<td class="table_cell cell_date"><?php echo __('Finished Date'); ?></td>
			<td class="table_cell cell_status"><?php echo __('Status'); ?></td>
			<td class="table_cell cell_log"><?php echo __('Log'); ?></td>
		</tr>
	</thead>
	<?php // pr($data); ?>
	<tbody class="table_body">
		<?php foreach($data as $k => $v) { //pr($v);die;?>
		<tr class="table_row">
			<td class="table_cell"><?php echo __($v['BatchProcess']['name']) .((isset($v['Report']))?' ('.$v['Report']['file_type'].') ':'');?></td>
			<td class="table_cell"><?php echo $v['BatchProcess']['startedBy'];?></td>
			<td class="table_cell center"><?php echo $v['BatchProcess']['start_date'];?></td>
			<td class="table_cell center"><?php echo $v['BatchProcess']['finish_date'];?></td>
			<td class="table_cell center"><?php echo $status[$v['BatchProcess']['status']];?></td>
			<td class="table_cell center"><a href="javascript:void(0);" onClick="window.location=getRootURL()+'/DataProcessing/downloadLog/<?php echo $v['BatchProcess']['id']; ?>'"><?php echo ($v['BatchProcess']['file_exists'])?'Log':''; ?></a></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>  