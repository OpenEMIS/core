<?php $label = isset($attr['label']) ? $attr['label'] : $attr['field']; ?>
<?php if ($label) : ?>
	<!-- POCOR-7659 start -->
	<?php if (($attr['type'] == "custom_repeater") && (strcasecmp($attr['customField']['name'],"repeater")==0) && !empty($attr['date_options'])) :;
    	else : ?>
			<div class="input date<?= $attr['null'] == false ? ' required' : '' ?>">
				<label for="<?= $attr['id'] ?>"><?= $label ?></label>
	<?php endif;?>
	<!-- POCOR-7659 end -->
<?php endif; ?>

	<?php
	$errorMsg = '';
	if (array_key_exists('fieldName', $attr)) {
		$errorMsg = $this->Form->error($attr['fieldName']);
	} else {
		$errorMsg = $this->Form->error($attr['field']);
	}
	$divErrorCSS = (!empty($errorMsg)) ? 'error' : '';
	$inputErrorCSS = (!empty($errorMsg)) ? 'form-error' : '';
	$inputWrapperStyle = (array_key_exists('inputWrapperStyle', $attr)) ? $attr['inputWrapperStyle'] : '';
	?>
	<div class="input-group date <?= isset($attr['class']) ? $attr['class'] : '' ?> <?php echo $divErrorCSS; ?>" id="<?= $attr['id'] ?>" style="<?= $inputWrapperStyle; ?>">
		<?php
		$fieldName = (array_key_exists('fieldName', $attr)) ? $attr['fieldName'] : $attr['model'] . '.' . $attr['field'];

		if (($fieldName == 'Identities.issue_date') || ($fieldName == 'Identities.expiry_date')) {
			$attr['value'] = '';
		}

		$inputAttr = [
			'class' => 'form-control datepkr' . $inputErrorCSS,
			'value' => isset($attr['value']) ? $attr['value'] : '',
			'type' => 'text',
			'label' => false,
			'error' => false
		];

		if (array_key_exists('attr', $attr)) {
			if (array_key_exists('onchange', $attr['attr'])) {
				$inputAttr = array_merge($inputAttr, ['onchange' => $attr['attr']['onchange']]);
			}
		}
		echo $this->Form->input($fieldName, $inputAttr);
		?>
		<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
	</div>
	<?php
	echo $errorMsg;
	?>

	<?php if ($label) : ?>
		</div>
	<?php endif; ?>
	<!-- POCOR-7793 -->
	<?php if($attr['className'] == "Institution.InstitutionSurveys"){ ?>
		<style>
			.datepkr{
				margin-bottom: 16px !important;
			}
		</style>

	<?php } ?>
	<!-- POCOR-7793 -->