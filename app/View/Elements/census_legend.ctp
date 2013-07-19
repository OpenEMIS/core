<?php echo $this->Html->script('jquery.tools', false); ?>


<div class="row_item_legend">
<ul class="legend">
	<li title="Census entries that are manually entered/verified by data entry"><span class="dataentry"  ></span><?php echo __('Data Entry'); ?></li>
	<li title="Census entries that are entered from external sources ie: online/offline questionnaires"><span class="external"></span><?php echo __('External'); ?></li>
	<li title="Census entries that are generated from current records in the system"><span class="internal"></span><?php echo __('Internal'); ?></li>
	<li title="Census entries that are generated from estimates using past census data"><span class="estimate"></span><?php echo __('Estimate'); ?></li>
</ul>
</div>

<script>
$(document).ready(function() {
 $('li[title]').tooltip({position: 'top center', effect: 'slide'});
});
</script>