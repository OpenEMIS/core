<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="field_option" class="content_wrapper">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		if($_add) {
			$params = array_merge(array('action' => 'add'));//, $parameters);
			echo $this->Html->link(__('Add'), $params, array('class' => 'divider'));
		}
		if($_edit) {
			$params = array_merge(array('action' => 'indexEdit'));//, $parameters);
			echo $this->Html->link(__('Reorder'), $params, array('class' => 'divider'));
		}
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
			'url' => $this->params['controller'] . '/index',
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
			'url' => $this->params['controller'] . '/fieldOption/' . $selectedOption,
			'onchange' => 'jsForm.change(this)',
			'autocomplete' => 'off'
		));
		?>
	</div>
	<?php endif; ?>
	
	<div class="table_content" style="margin-top: 10px;">
		<table class="table table-striped">
			<thead>
				<tr>
					<td class="col-visible" style="width: 60px;"><?php echo __('Visible'); ?></td>
					<td><?php echo __('Option'); ?></td>
					<?php
					if(isset($fields)) {
						foreach($fields as $field => $value) {
							if($value['display']) {
								echo '<td>' . __($value['label']) . '</td>';
							}
						}
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php 
				if(!empty($data)) :
					foreach($data as $obj) :
				?>
				<tr row-id="<?php echo $obj['id']; ?>">
					<td class="center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']==1); ?></td>
					<td><?php echo $this->Html->link($obj['name'], array('action' => 'view', $obj['id'])); ?></td>
					<?php
					if(isset($fields)) {
						foreach($fields as $field => $value) {
							if($value['display']) {
								echo '<td>' .$obj[$field] . '</td>';
							}
						}
					}
					?>
				</tr>
				<?php 
					endforeach;
				endif;
				?>
			</tbody>
		</table>
	</div>
</div>