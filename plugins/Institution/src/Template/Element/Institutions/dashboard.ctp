<div class="overview-wrapper alert overview-box">
	<a data-dismiss="alert" href="#" aria-hidden="true" class="close">×</a>
	<div class="data-section">
		<i class="kd-institutions icon"></i>
		<div class="data-field">
			<h4>Total Institutions:</h4>
			<h1 class="data-header"><?= $this->Paginator->counter('{{count}}') ?></h1>
		</div>
	</div>

	<div class="data-section">
		<?php echo $this->element('doughnut') ?>
		<div class="data-field">
			<h4>Locality</h4>
			<p><div class="data-blue"></div><strong>Urban:</strong> 20</p>
			<p><div class="data-pink"></div><strong>Rural:</strong> 25</p>
		</div>
	</div>

	<div class="data-section data-field">
		<h4>Total Female Students</h4>
		<h1 class="data-header">123, 600</h1>
	</div>

	<div class="data-section data-field">
		<h4>Total Male Students</h4>
		<h1 class="data-header">200, 000</h1>
	</div>
</div>
