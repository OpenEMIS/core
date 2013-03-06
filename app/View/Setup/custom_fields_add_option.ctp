<?php
list($model, $order, $field, $fieldId) = $params;
$option = sprintf('data[%s][%s][%%s]', $model.'Option', $order+1);
?>

<li data-id="<?php echo $order;?>">
	<input type="hidden" id="order" name="<?php echo sprintf($option, 'order'); ?>" value="<?php echo $order; ?>" />
	<input type="hidden" id="visible" name="<?php echo sprintf($option, 'visible'); ?>" value="1" />
	<input type="hidden" id="id" name="<?php echo sprintf($option, 'id'); ?>" value="0" />
	<input type="hidden" id="ref_id" name="<?php echo sprintf($option, $field); ?>" value="<?php echo $fieldId; ?>" />
	<input type="text" class="default" name="<?php echo sprintf($option, 'value'); ?>" value="" />
	<span class="icon_visible"></span>
	<span class="icon_up"></span>
	<span class="icon_down"></span>
</li>