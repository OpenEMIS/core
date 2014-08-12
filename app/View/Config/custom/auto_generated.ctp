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
					<th><?php echo __('Name');?></th>
					<th><?php echo __('Value');?></th>
				</tr>
			</thead>

			<tbody>
				<?php 
				$i = 0;
				foreach($element as $innerKey => $innerElement){
					$item = $innerElement;
				 ?>
				<tr>
					<td><?php echo $this->Html->link($item['label'], array('action' => 'view', $item['id']), array('escape' => false));?></td>
					<td>
						 <?php 
							$val = '';
			                if(substr($item['value'], -1)>0) {
			                    $val = str_replace(",","",substr($item['value'],0,-1));
								echo __('Enabled');
								if($val!=''){
									echo ' ';
									echo __('('.$val.')');
								}
			                }
						?>
					</td>
				</tr>

				<?php } ?>
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
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'autoGeneratedEdit', $id), array('class' => 'divider'));
	}
	$this->end();

	$this->start('contentBody'); ?>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.type');?></div>
		<div class="col-md-6"><?php echo $data['ConfigItem']['type'];?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.label');?></div>
		<div class="col-md-6"><?php echo $data['ConfigItem']['label'];?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.value');?></div>
		<div class="col-md-6">
		<?php 
			$val = '';
			$val = str_replace(",","",substr($data['ConfigItem']['value'],0,-1));
			if(substr($data['ConfigItem']['value'], -1)>0) {
				echo $this->Label->get('general.enabled');
			}else{
				echo $this->Label->get('general.disabled');
			}
			if($val!=''){
				echo ' ';
				echo __('('.$val.')');
			}
		?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.modified_by');?></div>
		<div class="col-md-6"><?php echo $data['ModifiedUser']['first_name'] . ' ' . $data['ModifiedUser']['last_name'];?></div>
	</div>
	
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.modified');?></div>
		<div class="col-md-6"><?php echo $data['ConfigItem']['modified'];?></div>
	</div>
	
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.created_by');?></div>
		<div class="col-md-6"><?php echo $data['CreatedUser']['first_name'] . ' ' . $data['CreatedUser']['last_name'];?></div>
	</div>
	
	<div class="row">
		<div class="col-md-3"><?php echo $this->Label->get('general.created');?></div>
		<div class="col-md-6"><?php echo $data['ConfigItem']['created'];?></div>
	</div>
	<?php $this->end(); ?> 
<?php } ?>
<?php if($action == 'edit') { ?>
<?php
$this->start('contentActions');
echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'index') , array('class' => 'divider link_view'));
$this->end();

$this->start('contentBody'); ?>

<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'autoGeneratedEdit', $id));
echo $this->Form->create('ConfigItem', $formOptions);

echo $this->Form->input('id', array('type' => 'hidden'));
echo $this->Form->input('name', array('type' => 'hidden'));
echo $this->Form->input('field_type', array('type' => 'hidden'));
echo $this->Form->input('option_type', array('type' => 'hidden'));

echo $this->Form->input('type', array('value'=> $type, 'readonly' => 'readonly'));
echo $this->Form->input('label', array('readonly' => 'readonly', 'disabled'));
echo '<div class="form-group">';
echo '<label class="col-md-3 control-label">' . $this->Label->get('general.enabled').  '</label>';
echo '<div class="col-md-4">';
$itemsVal = explode(",", $this->data['ConfigItem']['value']);
echo $this->Form->input('ConfigItem.value.enable',
	array('label'=>false, 'div'=>false, 'after'=>false, 'before'=>false, 'between'=>false, 'class'=>'left form-control',
    'type'=>'checkbox', 'style'=>'width: 30px;', 'checked' => $itemsVal[1]));
echo $this->Form->input('ConfigItem.value.prefix', 
	array('default' => $itemsVal[0], 'label'=>false, 'div' => false,  'after'=>false, 'before'=>false, 'between'=>false, 'class'=>'left form-control',
	 'style'=>'width: 100px;'));
echo '</div>';
echo '</div>';

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));
echo $this->Form->end();
?>

<?php $this->end(); ?> 
<?php } ?>
