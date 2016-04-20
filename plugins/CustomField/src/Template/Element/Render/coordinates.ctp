<?php if ($action != 'edit'): ?>

	<?php
        $latitude = '<div style="width:12%;margin-right:1%;float:left;"><span class="status past">Latitude</span><span class="status" style="border-color:#ddd">'.$values->latitude.'</span></div>';
        $longitude = '<div style="width:12%;margin-right:1%;float:left;"><span class="status past">Longitude</span><span class="status" style="border-color:#ddd">'.$values->longitude.'</span></div>';
        echo $latitude . $longitude;
    ?>

<?php else: ?>

	<div class="input">
		<label><?= $attr['attr']['label']; ?></label>
		<?php
			$options = [
				'type' => 'string',
				'label' => false,
				'placeholder' => __('Latitude'),
	            'required' => true,
	            'templates' => [
	            	'inputContainer' => '{{content}}',
	            ],
	            'style' => 'width:12%;margin-right:1%;',
	            'value' => (!is_null($values)) ? $values->latitude : '',
			];
			echo $attr['form']->input($attr['fieldPrefix'].".latitude", $options);

			$options['placeholder'] = __('Longitude');
			$options['value'] = (!is_null($values)) ? $values->longitude : '';
			echo $attr['form']->input($attr['fieldPrefix'].".longitude", $options);
			echo $attr['form']->hidden($attr['fieldPrefix'].".".$attr['attr']['fieldKey'], ['value' => $attr['customField']->id]);
			echo $attr['form']->hidden($attr['fieldPrefix'].".id", ['value' => $id]);
		?>
	</div>

<?php endif; ?>
