<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('field.option', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="field_option" class="content_wrapper">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		$params = array_merge(array('action' => 'fieldOption'), $parameters);
		echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row category">
		<?php
		echo $this->Form->input('options', array(
			'class' => 'default',
			'options' => $options,
			'label' => false,
			'default' => $selectedOption,
			'url' => $this->params['controller'] . '/fieldOptionIndexEdit',
			'onchange' => 'jsForm.change(this)',
			'autocomplete' => 'off'
		));
		?>
	</div>
	<?php if(isset($subOptions)) : ?>
	<div class="row category">
		<?php
		echo $this->Form->input('suboptions', array(
			'class' => 'default',
			'options' => $subOptions,
			'label' => false,
			'default' => $selectedSubOption,
			'url' => $this->params['controller'] . '/fieldOptionIndexEdit/' . $selectedOption,
			'onchange' => 'jsForm.change(this)',
			'autocomplete' => 'off'
		));
		?>
	</div>
	<?php endif; ?>
	
	<div class="table_content" style="margin-top: 10px;">
		<?php 
		$params['controller'] = $this->params['controller'];
		$params['action'] = 'fieldOptionReorder';
		echo $this->Form->create($model, array('url' => $params, 'id' => 'OptionMoveForm'));
		echo $this->Form->hidden('id', array('class' => 'option-id'));
		echo $this->Form->hidden('move', array('class' => 'option-move'));
		echo $this->Form->end();
		$index = 1;
		?>
		<table class="table table-striped">
			<thead>
				<tr>
					<td class="col_visible" style="width: 60px;"><?php echo __('Visible'); ?></td>
					<td><?php echo __('Option'); ?></td>
					<td class="col_action" style="width: 72px;"><?php echo __('Order'); ?></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach($data as $obj) : ?>
					<tr row-id="<?php echo $obj['id']; ?>">
						<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
						<td><?php echo $obj['name']; ?></td>
						<td>
							<?php 
							$options = array('escape' => false, 'class' => 'void action', 'onclick' => 'FieldOptions.move(this)');
							if($index!=count($data)) {
								$class = $index>1 ? 'void action' : 'void action action-last';
								$options['move'] = 'last';
								$options['class'] = $class;
								echo '<span class="icon_last" move="last" onclick="FieldOptions.move(this)"></span>';
							}
							if($index!=count($data)) {
								$options['move'] = 'down';
								echo '<span class="icon_down" move="down" onclick="FieldOptions.move(this)"></span>';
							}
							if($index>1) {
								$options['move'] = 'up';
								echo '<span class="icon_up" move="up" onclick="FieldOptions.move(this)"></span>';
							}
							if($index>1) {
								$class = $index!=count($data) ? 'void action' : 'void action action-last';
								$options['move'] = 'first';
								$options['class'] = $class;
								echo '<span class="icon_first" move="first" onclick="FieldOptions.move(this)"></span>';
							}
							$index++;
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
