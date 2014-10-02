<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->script('census', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Staff'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'CensusStaff', 'index', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->Form->create('CensusStaff', array(
	'inputDefaults' => array('label' => false, 'div' => false),
	'url' => array('controller' => 'Census', 'action' => 'CensusStaff')
));
echo $this->element('census/year_options');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<?php echo $this->Html->tableHeaders(array(__('Position'), __('Male'), __('Female'), __('Total'))); ?>
		</thead>
		<?php if (!empty($positionTitles)) { ?>
			<tbody>
			<?php } ?>

			<?php
			$total = 0;
			$index = 0;

			foreach ($positionTitles AS $titleId => $titleName):
				$subTotal = 0;
				?>
				<tr>
					<td><?php echo $titleName; ?></td>
					<?php
					foreach ($genderOptions AS $genderId => $genderName):
						?>
						<td class="cell-number">
							<div class="input_wrapper">
								<?php
								echo $this->Form->hidden($index . '.id', array('value' => !empty($data[$titleId][$genderId]['censusId']) ? $data[$titleId][$genderId]['censusId'] : 0));
								echo $this->Form->hidden($index . '.staff_category_id', array('value' => $titleId));
								echo $this->Form->hidden($index . '.gender_id', array('value' => $genderId));

								$record_tag = '';
								foreach ($source_type as $k => $v):
									if (isset($data[$titleId][$genderId]['source']) && $data[$titleId][$genderId]['source'] == $v) {
										$record_tag = "row_" . $k;
									}
								endforeach;

								if (!empty($data[$titleId][$genderId]['value'])) {
									$value = $data[$titleId][$genderId]['value'];
									$subTotal += $value;
								} else {
									$value = 0;
								}

								echo $this->Form->input($index . '.value', array(
									'type' => 'text',
									'class' => 'computeTotal ' . $record_tag,
									'div' => false,
									'value' => $value,
									'maxlength' => 10,
									'onkeypress' => 'return utility.integerCheck(event)',
									'onkeyup' => 'Census.computeTotal(this)'
								));
								?>
							</div>
						</td>
						<?php
						$index++;
					endforeach;
					?>
					<td class="cell-total cell-number"><?php echo $subTotal; ?></td>
				</tr>
				<?php
				$total += $subTotal;
			endforeach;
			?>
			<?php if (!empty($positionTitles)) { ?>
			</tbody>
		<?php } ?>

		<tfoot>
			<tr>
				<td colspan="3" class="cell-number"><?php echo __('Total'); ?></td>
				<td class="cell-value cell-number"><?php echo $total; ?></td>
			</tr>
		</tfoot>
	</table>
</div>
<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'CensusStaff', 'index', $selectedYear)));
echo $this->Form->end();
?>

<?php $this->end(); ?>
