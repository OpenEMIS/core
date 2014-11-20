<div class="row page-controls">
	<div class="col-md-3">
		<?php
		$url = 'Census/' . $this->action;
		
		if (isset($model) && isset($action)) {
			$url = "Census/$model/" . $action;
		}
		echo $this->Form->input('school_year_id', array(
			'label' => false,
			'div' => false,
			'options' => $yearList,
			'class' => 'form-control',
			'default' => $selectedYear,
			'onchange' => 'jsForm.change(this)',
			'url' => $url
		));
		?>
	</div>
	<?php echo $this->element('census/legend'); ?>
</div>
