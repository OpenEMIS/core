<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));
?>
<?php if($action == 'index') { ?>
<?php $this->start('contentActions');
$this->end();

$this->start('contentBody'); ?>
<div class="row select_row form-group">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('type', array(
                'options' => $typeOptions,
                'default' => $selectedType,
                'label' => false,
                'url' => 'Config/index',
                'onchange' => 'jsForm.change(this)',
                'div' => false
            ));
        ?>
    </div>
</div>
	<!-- Items -->
	<?php
		if(isset($items)) {
			// pr($items);
			foreach($items as $key => $element){ 
			// pr($element);
				if(isset($element) && sizeof($element) > 0) { 
	?>

		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<td><?php echo __('Host');?></td>
					<td><?php echo __('Port');?></td>
					<td><?php echo __('Version');?></td>
					<td><?php echo __('Base Dn');?></td>
				</tr>
			</thead>

			<tbody class="table_body">

				<tr>
				<?php 
				$i = 0;
				foreach($element as $innerKey => $innerElement){
					$item = $innerElement;
					if($i++==4){
						continue;
					}
				 ?>

					<td>
						<?php echo ($i!=1)? $item['value'] : $this->Html->link($item['value'], array('action' => 'view', 'LDAP Configuration'), array('escape' => false));?>
					</td>
				
				<?php } ?>
				
				</tr>
			</tbody>
			</table>
		</div>
		<?php 
				}
			}
		} 
		?>
		<?php $this->end(); ?> 
<?php } ?>
<?php if($action == 'view') { ?>
	<?php $this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.list'), array('action' => 'index', $type), array('class' => 'divider'));
	if($_edit && $editable) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', 'LDAP Configuration'), array('class' => 'divider'));
	}
	$this->end();

	$this->start('contentBody'); ?>
	<?php echo $this->element('layout/view', array('fields' => $fields, 'data' => $data)); ?>
	<?php $this->end(); ?> 
<?php } ?>
<?php if($action == 'edit') { ?>
<?php
$this->start('contentActions');
echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'index') , array('class' => 'divider link_view'));
$this->end();

$this->start('contentBody'); ?>

<?php echo $this->element('alert'); ?>
<div class="ldap">
<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, $id));
	echo $this->Form->create('ConfigItem', $formOptions);

	echo $this->Form->input('hostId', array('type' => 'hidden'));
	echo $this->Form->input('portId', array('type' => 'hidden'));
	echo $this->Form->input('versionId', array('type' => 'hidden'));
	echo $this->Form->input('base_dnId', array('type' => 'hidden'));
	echo $this->Form->input('typeId', array('value'=> $this->request->data['ConfigItem']['type'],'type' => 'hidden'));

	echo $this->Form->input('host');
	echo $this->Form->input('port');
	echo $this->Form->input('version');
	echo $this->Form->input('base_dn');

	echo $this->Form->input('type', array('disabled' => 'disabled'));
	
	?>
	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo __('Test Connection');?></label>
		<div class="col-md-4"><?php echo $this->Form->button('Connect',array('div' => false, 'type'=>'button', 'onclick'=>'Config.checkLDAPconn()')); ?>
		</div>
	</div>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'view', $id), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?> 
 <?php } ?>