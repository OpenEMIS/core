<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper">
	<h1>
		<span><?php echo __($header); ?></span>
		<?php
		$params = array_merge(array('action' => 'fieldOption'), $parameters);
		echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
		if($_edit) {
			$params = array_merge(array('action' => 'fieldOptionEdit'), $parameters);
			$params[] = $id;
			echo $this->Html->link(__('Edit'), $params, array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row">
		<div class="label"><?php echo __('Option'); ?></div>
		<div class="value"><?php echo $data[$model]['name']; ?></div>
	</div>
	
	<?php
		if (isset($fields)):
			foreach ($fields as $field => $value):
		?>
			<div class="row">
				<div class="label"><?php echo __($value['label']); ?></div>
				<div class="value">
					<?php
					if(!isset($value['options'])) { 
						echo $data[$model][$field];
					} else {
						echo $value['options'][$data[$model][$field]];
					}
					?>
				</div>
			</div>
		<?php
			endforeach;
		endif;
		?>
	
	<div class="row">
		<div class="label"><?php echo __('Visible'); ?></div>
		<div class="value"><?php echo $this->Utility->getVisible($data[$model]['visible']); ?></div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Modified by'); ?></div>
		<div class="value"><?php echo $data[$model]['modified_user']; ?></div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Modified on'); ?></div>
		<div class="value"><?php echo $data[$model]['modified']; ?></div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Created by'); ?></div>
		<div class="value"><?php echo $data[$model]['created_user']; ?></div>
	</div>
	
	<div class="row">
		<div class="label"><?php echo __('Created on'); ?></div>
		<div class="value"><?php echo $data[$model]['created']; ?></div>
	</div>
</div>