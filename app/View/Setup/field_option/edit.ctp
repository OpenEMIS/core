<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper edit">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		$action = empty($id) ? 'fieldOption' : 'fieldOptionView';
		$params = array_merge(array('action' => $action), $parameters);
		$params[] = $id;
		echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
		?>
	</h1>
	<?php
	$params['controller'] = $this->params['controller'];
	$params['action'] = 'fieldOptionEdit';
	echo $this->Form->create($model, array(	
		'url' => $params,
		'inputDefaults' => array(
			'div' => 'row',
			'label' => false,
			'before' => '<div class="label">',
			'between' => '</div><div class="value">',
			'after' => '</div>',
			'class' => 'default'
		)
	));
	if(empty($id)) {
		echo $this->Form->hidden('order', array('value' => $order));
	}
	?>
	
	<?php echo $this->element('alert'); ?>
	<?php echo $this->Form->input('name', array('label' => array('text' => __('Option')))); ?>
	<?php
	if (isset($fields)) {
		foreach ($fields as $field => $value) {
			if(!isset($value['options'])) {
				echo $this->Form->input($field, array('label' => array('text' => __($value['label']))));
			} else {
				$options = array('options' => $value['options'], 'label' => array('text' => __($value['label'])));
				if(isset($conditions) && isset($conditions[$field])) {
					$options['default'] = $conditions[$field];
				}
				echo $this->Form->input($field, $options);
			}
		}
	}
	?>
	<?php echo $this->Form->input('visible', array('options' => $this->Utility->getVisibleOptions(), 'label' => array('text' => __('Visible')))); ?>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php 
		$params['action'] = $action;
		echo $this->Html->link(__('Cancel'), $params, array('class' => 'btn_cancel btn_left'));
		?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
