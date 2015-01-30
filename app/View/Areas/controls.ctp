<div class="row page-controls">
	<div class="col-md-4">
		<?php
		echo $this->Form->input('options', array(
			'class' => 'form-control',
			'label' => false,
			'div' => false,
			'options' => $areaOptions,
			'default' => $selectedAction,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'],
			'autocomplete' => 'off'
		));
		?>
	</div>
	<?php if($selectedAction == 'AreaAdministrativeLevel' && isset($countryOptions)) : ?>
		<div class="col-md-4">
			<?php
			echo $this->Form->input('country', array(
				'class' => 'form-control',
				'label' => false,
				'div' => false,
				'options' => $countryOptions,
				'default' => $selectedCountry,
				'onchange' => 'jsForm.change(this)',
				'url' => $this->params['controller'] . '/' . $selectedAction . '/index',
				'autocomplete' => 'off'
			));
			?>
		</div>
	<?php endif ?>
</div>