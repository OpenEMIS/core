<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'institution-list');
$this->assign('contentHeader', __('Contacts'));
$this->start('contentActions');
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'contactsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<?php foreach($contactOptions as $key=>$ct){  ?>
<fieldset class="section_group">
<legend><?php echo __($ct);?></legend>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.description'); ?></th>
				<th><?php echo $this->Label->get('general.value'); ?></th>
				<th><?php echo __('Preferred'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			foreach($list as $obj):
				if($obj['ContactType']['contact_option_id']==$key):
			?>
			<tr row-id="<?php echo $obj['StudentContact']['id']; ?>">
				<td><?php echo $obj['ContactType']['name']; ?></td>
				<td><?php echo $this->Html->link($obj['StudentContact']['value'], array('action' => 'contactsView', $obj['StudentContact']['id'])); ?></td>
				<td><?php echo $this->Utility->checkOrCrossMarker($obj['StudentContact']['preferred']==1); ?></td>
			</tr>
			<?php
				endif;
			endforeach;
			?>
		</tbody>
	</table>
</div>
</fieldset>
<?php } ?>
<?php $this->end(); ?>
