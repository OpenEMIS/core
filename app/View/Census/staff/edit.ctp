<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->script('census', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Staff'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'staff', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->Form->create('CensusStaff', array(
	'inputDefaults' => array('label' => false, 'div' => false),
	'url' => array('controller' => 'Census', 'action' => 'staffEdit')
));
echo $this->element('census/year_options');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<?php echo $this->Html->tableHeaders(array(__('Position'), __('Male'), __('Female'), __('Total'))); ?>
		</thead>
		<tbody>
			<?php
			$total = 0;
			$index = 0;

			foreach ($staffCategories AS $staffCatId => $staffCatName):
				$subTotal = 0;
				?>
				<tr>
					<td><?php echo $staffCatName; ?></td>
					<?php
					foreach ($genderOptions AS $genderId => $genderName):
						?>
						<td class="cell-number">
							<div class="input_wrapper">
								<?php
								echo $this->Form->hidden($index . '.id', array('value' => !empty($data[$staffCatId][$genderId]['censusId']) ? $data[$staffCatId][$genderId]['censusId'] : 0));
								echo $this->Form->hidden($index . '.staff_category_id', array('value' => $staffCatId));
								echo $this->Form->hidden($index . '.gender_id', array('value' => $genderId));

								$record_tag = '';
								foreach ($source_type as $k => $v):
									if (isset($data[$staffCatId][$genderId]['source']) && $data[$staffCatId][$genderId]['source'] == $v) {
										$record_tag = "row_" . $k;
									}
								endforeach;

								if (!empty($data[$staffCatId][$genderId]['value'])) {
									$value = $data[$staffCatId][$genderId]['value'];
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
			endforeach;
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3" class="cell-number"><?php echo __('Total'); ?></td>
				<td class="cell-value cell-number"><?php echo $total; ?></td>
			</tr>
		</tfoot>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'staff', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
</div>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>
