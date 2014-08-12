<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get('Config.name'));
$this->start('contentActions');
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