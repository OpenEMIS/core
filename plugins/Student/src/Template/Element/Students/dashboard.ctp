<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<i class="kd-students icon"></i>
		<div class="data-field">
			<h4>Total Students:</h4>
			<h1 class="data-header"><?= $this->Paginator->counter('{{count}}') ?></h1>
		</div>
	</div>

	<div class="data-section">
		<?php echo $this->element('doughnut') ?>
		<div class="data-field">
			<h4>Gender</h4>
			<p><div class="data-blue"></div><strong>Male:</strong> 708</p>
			<p><div class="data-pink"></div><strong>Female:</strong> 660</p>
		</div>
	</div>

	<div class="data-section data-field">
		<h4>Attendance Rate</h4>
		<h1 class="data-header">95.6%</h1>
	</div>

	<div class="data-section data-field">
		<h4>Assessment Results</h4>
		<h1 class="data-header">96.8%</h1>		
	</div>
</div>
