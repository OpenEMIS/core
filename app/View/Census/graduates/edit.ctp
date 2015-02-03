<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Graduates'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'graduates', $selectedAcademicPeriod), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusGraduate', array(
	'inputDefaults' => array('label' => false, 'div' => false),
	'url' => array('controller' => 'Census', 'action' => 'graduatesEdit')
));
echo $this->element('census/academic_period_options');
?>
<div class="table-responsive">
	<?php
	$index = 0;
	foreach ($programmeData as $cycleName => $programmes):
		$total = 0;
		?>
		<fieldset class="section_group">
			<legend><?php echo $cycleName ?></legend>
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="cell_programme"><?php echo __('Programme'); ?></th>
						<th class="cell_certificate"><?php echo __('Certification'); ?></th>
						<th class=""><?php echo __('Male'); ?></th>
						<th class=""><?php echo __('Female'); ?></th>
						<th class=""><?php echo __('Total'); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php
					foreach ($programmes as $programmeId => $programme):
						$subTotal = 0;
						?>
						<tr>
							<td class=""><?php echo $programme['programmeName']; ?></td>
							<td class=""><?php echo $programme['certificationName']; ?></td>
							<?php
							foreach ($genderOptions AS $genderId => $genderName):
								?>
								<td class="">
									<div class="input_wrapper">
										<?php
										echo $this->Form->hidden($index . '.id', array('value' => !empty($censusData[$programmeId][$genderId]['census_id']) ? $censusData[$programmeId][$genderId]['census_id'] : 0));
										echo $this->Form->hidden($index . '.education_programme_id', array('value' => $programmeId));
										echo $this->Form->hidden($index . '.gender_id', array('value' => $genderId));

										$record_tag = "";
										foreach ($source_type as $k => $v):
											if (isset($censusData[$programmeId][$genderId]['source']) && $censusData[$programmeId][$genderId]['source'] == $v):
												$record_tag = "row_" . $k;
											endif;
										endforeach;

										if (!empty($censusData[$programmeId][$genderId]['value'])):
											$value = $censusData[$programmeId][$genderId]['value'];
										else:
											$value = 0;
										endif;

										echo $this->Form->input($index . '.value', array(
											'class' => 'computeTotal ' . $record_tag,
											'type' => 'text',
											'maxlength' => 9,
											'value' => $value,
											'onkeypress' => 'return utility.integerCheck(event)',
											'onkeyup' => 'Census.computeTotal(this)'
										));

										$subTotal += $value;
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
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3"></td>
						<td class="cell-label">Total</td>
						<td class="cell-value cell-number"><?php echo $total; ?></td>
					</tr>
				</tfoot>
			</table>
		</fieldset>
	<?php endforeach; ?>
	<?php 
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'graduates', $selectedAcademicPeriod)));
	echo $this->Form->end();
	?>
</div>
<?php $this->end(); ?>
