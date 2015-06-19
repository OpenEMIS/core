<?php
$primaryKey = $table->primaryKey();
$id = $obj->$primaryKey;
?>

<div class="dropdown">
	<button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
	<?= __('Select') ?><span class="caret-down"></span>
	</button>

	<ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
		<div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

		<?php
		foreach ($_indexActions as $action => $attr) : 
			$icon = sprintf('<i class="%s"></i>%s', $attr['class'], $this->ControllerAction->getLabel('general', $action));
			$options = array(
				'role' => 'menuitem',
				'tabindex' => '-1',
				'escape' => false
			);

			if (array_key_exists('removeStraightAway', $attr) && $attr['removeStraightAway']) {
				$options['data-toggle'] = 'modal';
				$options['data-target'] = '#delete-modal';
				$options['field-target'] = '#recordId';
				$options['field-value'] = $obj->$primaryKey;
				$options['onclick'] = 'ControllerAction.fieldMapping(this)';
			}

			$url = $attr['url'];
			$url[] = $id;
		?>
		<li role="presentation">
			<?= $this->Html->link($icon, $url, $options) ?>
		</li>
		<?php endforeach ?>
	</ul>
</div>
