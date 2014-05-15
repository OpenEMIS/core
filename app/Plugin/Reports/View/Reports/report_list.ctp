<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Generated Files'));
$this->start('contentActions');
$this->end();
$this->assign('contentId', 'report-list');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php if(count($files) > 0) {?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
	<thead class="table_head">
		<tr>
			<td class="table_cell col_name"><?php echo __('Name'); ?></td>
			<td class="table_cell col_name"><?php echo __('File size'); ?></td>
			<td class="table_cell col_lastgen"><?php echo __('File Type'); ?></td>
			<td class="table_cell col_name"><?php echo __('Generated'); ?></td>
		</tr>
	</thead>
	<?php foreach($files as $fileType => $arrV){ ?>
	
	<tbody class="table_body">
	<?php foreach($arrV as $time => $arrFileInfo){	?>
	
		<tr row-id="<?php echo $arrFileInfo['basename'];?>" class="table_row">
			<td class="table_cell col_name"><?php echo $this->Html->link($arrFileInfo['name'], array('action' => $this->action.'Download', $arrFileInfo['basename']), array('escape' => false)); ?></td>
			<td class="table_cell col_name center"><?php echo $arrFileInfo['size'];?></td>
			<td class="table_cell col_lastgen center"><?php echo $fileType;?></td>
			<td class="table_cell col_name center"><?php echo $arrFileInfo['time'];?></td>  
		</tr>
	
	<?php } ?>
	</tbody>
	
	<?php } ?>
</table>
</div>
<?php } ?>
<?php $this->end(); ?>