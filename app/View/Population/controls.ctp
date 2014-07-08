<?php //echo $this->Html->script('jquery.tools', false); ?>
<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('year', array(
			'label' => false,
			'div' => false,
			'between' => false,
			'after' => false,
			'id' => 'populationYear',
			'options' => $yearList,
			'class' => 'form-control',
			'default' => $selectedYear,
			'onchange' => 'jsForm.change(this)',
			'url' => 'Population/' . $this->action
		));
		?>
	</div>
	<div class="col-md-7 right">
		<ul class="legend">
			<li title="Population entries that are manually entered/verified by data entry"><span class="dataentry"  ></span><?php echo __('Data Entry'); ?></li>
			<li title="Population entries that are generated from estimates "><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
	</div>
</div>
<script>
$(document).ready(function() {
 //$('li[title]').tooltip({position: 'top center', effect: 'slide'});
});
</script>