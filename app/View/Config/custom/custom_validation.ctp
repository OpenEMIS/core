<?php
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

<div class="row page-controls">
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
					<?php if($item['name']=='yearbook_logo'){ ?>
					<td>
						<?php 
						if($item['hasYearbookLogoContent']){
							echo $this->Html->image("/Config/fetchYearbookImage/{$item['value']}", array('class' => 'profile_image', 'alt' => '90x115')); 
						}
						?>
					</td>
					<?php }else{?>
					<td><?php echo !empty($options[$item['id']])? $options[$item['id']][$item['value']] : $item['value'];?></td>
					<?php } ?>
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

<?php 
if($action == 'view') {
	$this->start('contentActions');
	echo $this->Html->link($this->Label->get('general.back'), array('action' => 'index', $type), array('class' => 'divider'));
	if($_edit && $editable) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'edit', $id), array('class' => 'divider'));
	}
	$this->end();

	$this->start('contentBody');
	echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
	$this->end();
} 
?>

<?php 
if($action == 'edit') {
	$this->start('contentActions');
	echo $this->Html->link(__('View'),array('controller' => 'Config', 'action'=>'index') , array('class' => 'divider link_view'));
	$this->end();

	$this->start('contentBody'); 
?>

	<div class="row">
		<div class="left"><b>N</b> </div>
		<div class="left">(<?php echo __('Numbers') ?>)&nbsp;|&nbsp;</div>
		<div class="left"><b>A</b> </div><div class="left">(<?php echo __('Alphabets') ?>)&nbsp;|&nbsp;</div><div class="left">(Special Chars)</div>
	</div>

<?php
	echo '<div class="customvalidation">';
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, $id));
	echo $this->Form->create('ConfigItem', $formOptions);
	
	echo $this->Form->input('id', array('type' => 'hidden'));
	echo $this->Form->input('name', array('type' => 'hidden'));
	echo $this->Form->input('field_type', array('type' => 'hidden'));
	echo $this->Form->input('option_type', array('type' => 'hidden'));
	
	echo $this->Form->input('type', array('value'=> $type, 'readonly' => 'readonly'));
	echo $this->Form->input('label', array('readonly' => 'readonly', 'disabled'));
	echo $this->Form->input('value', array('class'=>'form-control custom_validation'));
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view', $id)));
	echo $this->Form->end();
	echo '</div>';
	
	$this->end();
};
?>
