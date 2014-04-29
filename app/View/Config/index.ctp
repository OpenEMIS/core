<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));
$this->start('contentActions');
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
					<td><?php echo __('No');?></td>
					<td><?php echo __('Name');?></td>
					<td><?php echo __('Value');?></td>
				</tr>
			</thead>

			<tbody class="table_body">
				<?php 
				$i = 0;
				foreach($element as $innerKey => $innerElement){
					$item = $innerElement;
				 ?>
				<tr>
					<td><?php echo ++$i;?></td>
					<td><?php echo $this->Html->link($item['label'], array('action' => 'view', $item['id']), array('escape' => false));?></td>
					<td><?php echo !empty($options[$item['id']])? $options[$item['id']][$item['value']] : $item['value'];?></td>
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