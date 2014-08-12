<?php echo $this->Html->script('jquery.tools', false); ?>


<div class="row_item_legend">
<ul class="legend">
	<li title="Population entries that are manually entered/verified by data entry"><span class="dataentry"  ></span><?php echo __('Data Entry'); ?></li>
	<li title="Population entries that are generated from estimates "><span class="estimate"></span><?php echo __('Estimate'); ?></li>
</ul>
</div>

<script>
$(document).ready(function() {
 $('li[title]').tooltip({position: 'top center', effect: 'slide'});
});
</script>