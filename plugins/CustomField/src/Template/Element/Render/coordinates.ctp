<?php if ($action != 'edit'): ?>

	<?php
		$latitude = (is_object($values) && strlen($values->latitude) > 0) ? $values->latitude : '&nbsp;';
        $latitude = '<div class="input-value-wrapper"><span class="status past">'.__('Latitude').'</span><span class="status status-grey-border">'.$latitude.'</span></div>';
		$longitude = (is_object($values) && strlen($values->longitude) > 0) ? $values->longitude : '&nbsp;';
        $longitude = '<div class="input-value-wrapper"><span class="status past">'.__('Longitude').'</span><span class="status status-grey-border">'.$longitude.'</span></div>';
        echo $latitude . $longitude;
    ?>

<?php else: ?>

	<style>
		.error-message.error-message-double-line {margin:0;padding-top:0}
	</style>

	<div class="input <?php if ($attr['customField']['is_mandatory']) : ?> required <?php endif; ?>">

		<label class="tooltip-desc">
			<?= $attr['attr']['label']; ?>
			<i tooltip-class="tooltip-orange" tooltip-append-to-body="true" uib-tooltip="<?= __('Latitude and Longitude')?>" tooltip-placement="bottom" class="fa fa-question-circle fa-lg fa-right icon-orange"></i>
		</label>

		<?php
			$options = [
				'type' => 'string',
				'label' => false,
				'placeholder' => __('Latitude'),
	            'required' => true,
	            'class' => 'input-inline input-prefix',
	            'templates' => [
	            	'inputContainer' => '{{content}}',
	            ],
	            'value' => (!is_null($values)) ? $values->latitude : '',
			];
			$latError = (array_key_exists('latitude', $errors)) ? $errors['latitude'] : false;
			$lngError = (array_key_exists('longitude', $errors)) ? $errors['longitude'] : false;
		?>

		<div class="input-form-wrapper <?php if ($latError || $lngError): ?> error <?php endif; ?>">
			<?= $attr['form']->input($attr['fieldPrefix'].".latitude", $options); ?>

			<?php
				$options['placeholder'] = __('Longitude');
				$options['value'] = (!is_null($values)) ? $values->longitude : '';
			?>
			<?= $attr['form']->input($attr['fieldPrefix'].".longitude", $options); ?>

			<div class="error-message error-message-double-line"><?= implode('<br/>', [$latError, $lngError]) ?></div>
		</div>

		<?= $attr['form']->hidden($attr['fieldPrefix'].".".$attr['attr']['fieldKey'], ['value' => $attr['customField']->id]); ?>
		<?= $attr['form']->hidden($attr['fieldPrefix'].".id", ['value' => $id]); ?>

	</div>

<?php endif; ?>
