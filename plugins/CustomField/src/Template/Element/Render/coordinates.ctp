<?php if ($action != 'edit'): ?>

	<?php
		$latitude = (is_object($values)) ? $values->latitude : '&nbsp;';
        $latitude = '<div style="width:12%;margin-right:1%;float:left;"><span class="status past">Latitude</span><span class="status" style="border-color:#ddd">'.$latitude.'</span></div>';
		$longitude = (is_object($values)) ? $values->longitude : '&nbsp;';
        $longitude = '<div style="width:12%;margin-right:1%;float:left;"><span class="status past">Longitude</span><span class="status" style="border-color:#ddd">'.$longitude.'</span></div>';
        echo $latitude . $longitude;
    ?>

<?php else: ?>

	<div class="input clearfix <?php if ($attr['customField']['is_mandatory']) : ?> required <?php endif; ?>">
		
		<label style="float:left;"><?= $attr['attr']['label']; ?></label>
		
		<?php
			$options = [
				'type' => 'string',
				'label' => false,
				'placeholder' => __('Latitude'),
	            'required' => true,
	            'templates' => [
	            	'inputContainer' => '{{content}}',
	            ],
	            'style' => 'width:100%;margin-bottom:0;',
	            'value' => (!is_null($values)) ? $values->latitude : '',
			];
			$latError = (array_key_exists('latitude', $errors)) ? $errors['latitude'] : false;
			$lngError = (array_key_exists('longitude', $errors)) ? $errors['longitude'] : false;
		?>
		<div style="float:left;width:12%;margin-right:1%" class="<?php if ($latError): ?> error <?php endif; ?>">
			<?= $attr['form']->input($attr['fieldPrefix'].".latitude", $options); ?>
			<div class="error-message"><?= $latError ?></div>
		</div>

		<?php
			$options['placeholder'] = __('Longitude');
			$options['value'] = (!is_null($values)) ? $values->longitude : '';
		?>
		<div style="float:left;width:12%;margin-right:1%" class="<?php if ($lngError): ?> error <?php endif; ?>">
			<?= $attr['form']->input($attr['fieldPrefix'].".longitude", $options); ?>
			<div class="error-message"><?= $lngError ?></div>
		</div>
			
		<?= $attr['form']->hidden($attr['fieldPrefix'].".".$attr['attr']['fieldKey'], ['value' => $attr['customField']->id]); ?>
		<?= $attr['form']->hidden($attr['fieldPrefix'].".id", ['value' => $id]); ?>

	</div>

<?php endif; ?>
