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
<div class="row select_row page-controls">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('type', array(
                'options' => $typeOptions,
                'default' => $selectedType,
                'label' => false,
                'url' => 'Config/index',
                'class' => 'form-control',
                'onchange' => 'jsForm.change(this)',
                'div' => false
            ));
        ?>
    </div>
</div>
	<!-- Items -->
	<?php
		if(isset($items)) {
			foreach($items as $key => $element){ 
				if(isset($element) && sizeof($element) > 0) { 
	?>

		<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo __('Host');?></th>
					<th><?php echo __('Port');?></th>
					<th><?php echo __('Version');?></th>
					<th><?php echo __('Base Dn');?></th>
				</tr>
			</thead>

			<tbody>
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
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index', $type), array('class' => 'divider'));
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
echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'view', $type) , array('class' => 'divider link_view'));
$this->end();

$this->start('contentBody'); ?>

<div class="ldap">
<?php
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, $id));
	echo $this->Form->create('ConfigItem', $formOptions);

	echo $this->Form->input('hostId', array('type' => 'hidden'));
	echo $this->Form->input('portId', array('type' => 'hidden'));
	echo $this->Form->input('versionId', array('type' => 'hidden'));
	echo $this->Form->input('base_dnId', array('type' => 'hidden'));
	echo $this->Form->input('type', array('value'=> $this->request->data['ConfigItem']['type'],'type' => 'hidden'));

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

	<?php
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));
	echo $this->Form->end();
	?>
</div>
<?php $this->end(); ?> 
<?php } ?>
