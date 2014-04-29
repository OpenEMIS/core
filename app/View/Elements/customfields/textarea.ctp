<div class="custom_field">
	<div class="field_label"><?php echo $obj[$model]['name']; ?></div>
	<div class="field_value">
	<?php
		if(isset($dataValues[$obj[$model]['id']][0]['value'])){
			echo $dataValues[$obj[$model]['id']][0]['value'];
		}
	?>
	</div>
</div>
