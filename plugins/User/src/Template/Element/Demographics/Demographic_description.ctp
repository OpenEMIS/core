<?php use Cake\Utility\Inflector;?>

<div class="input required">
	<label><?php echo __('Wealth Quintile') ?></label>
	<div class="input-selection">
		<?php

			$this->Form->unlockField('demographic_types_id');
			$selectedValue = $attr['fields']['entity']['demographic_types_id'];
			foreach ($attr['fields']['demographicsTypes'] as $key => $record) :
		?>
			<div class="input">
				<input <?php echo $selectedValue == $attr['fields']['demographicsTypes'][$key]->id ? "checked=\"checked\"" : ""; ?> value="<?php echo $attr['fields']['demographicsTypes'][$key]->id ?>" kd-checkbox-radio="<strong><?php echo $attr['fields']['demographicsTypes'][$key]->name ?></strong> - <?php echo $attr['fields']['demographicsTypes'][$key]->description ?>" type="radio" id="demographics-demographic-types-id" name="Demographic[demographic_types_id]">
			</div>
		<?php
			endforeach;
		?>
	</div>
</div>	
