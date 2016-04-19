
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
	            'style' => 'width:12%;margin-right:1%;'
			];
			echo $attr['form']->input($attr['fieldPrefix'].".latitude", $options);

			$options['placeholder'] = __('Longitude');
			echo $attr['form']->input($attr['fieldPrefix'].".longitude", $options);
		?>
	</div>
