<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($reportNameCrumb));
$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => $this->action), array('class' => 'divider'));
$this->end();
$this->assign('contentId', 'report-list');
$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php if(count($files) > 0) {?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
	<thead>
		<tr>
			<th class="col_name"><?php echo __('Name'); ?></th>
			<th class="col_name"><?php echo __('File size'); ?></th>
			<th class="col_lastgen"><?php echo __('File Type'); ?></th>
			<th class="col_name"><?php echo __('Generated'); ?></th>
		</tr>
	</thead>
	<?php foreach($files as $fileType => $arrV): ?>
	
	<tbody>
	<?php foreach($arrV as $time => $arrFileInfo):	?>
	
		<tr row-id="<?php echo $arrFileInfo['basename'];?>">
			<td class="col_name"><?php echo __($arrFileInfo['name']); ?></td>
			<td class="col_name center"><?php echo $arrFileInfo['size'];?></td>
			<?php  
			if($fileType === 'csv'):
			?>
				<td class="col_lastgen center">
					<?php echo $this->Html->link(strtoupper($fileType), array('action' => $this->action.'Download', $arrFileInfo['basename']), array('escape' => false)); ?>, 
					<?php echo $this->Html->link('HTML', array('action' => $this->action.'ViewHtml', $arrFileInfo['basename']), array('escape' => false, 'target' => '_blank')); ?>
				</td>
			<?php 
			else:
			?>
				<td class="col_lastgen center">
					<?php echo $this->Html->link(strtoupper($fileType), array('action' => $this->action.'Download', $arrFileInfo['basename']), array('escape' => false)); ?>
				</td>
			<?php 
			endif;
			?>
			<td class="col_name"><?php echo $arrFileInfo['time'];?></td>  
		</tr>
	
	<?php endforeach; ?>
	</tbody>
	
	<?php endforeach; ?>
</table>
</div>
<?php } ?>
<?php $this->end(); ?>
