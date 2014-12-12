<div class="row page-controls">
	<div class="col-md-4">
		<?php
		echo $this->Form->input('school_year_id', array(
			'label' => false,
			'div' => false,
			'options' => $yearList,
			'value' => $yearId,
			'class' => 'form-control',
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . "/$model/$action"
		));
		?>
	</div>
	<div class="col-md-4">
		<?php
		echo $this->Form->input('week_id', array(
			'label' => false,
			'div' => false,
			'options' => $weekList,
			'value' => $weekId,
			'class' => 'form-control',
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . "/$model/$action/$yearId"
		));
		?>
	</div>
	<div class="col-md-4">
		<?php
		if (empty($weekDayList)) {
			array_push($weekDayList, $this->Label->get('general.noData'));
		}
		if (!isset($dayId)) {
			$dayId=0;
		}
		echo $this->Form->input('day_id', array(
			'label' => false,
			'div' => false,
			'options' => $weekDayList,
			'value' => $dayId, 
			'class' => 'form-control',
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . "/$model/$action/$yearId/$weekId"
		));
		?>
	</div>
</div>
