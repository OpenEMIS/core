<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Graduates'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'graduatesEdit', $selectedAcademicPeriod), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/academic_period_options');
?>

<div class="table-responsive">
	<?php
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
						<th><?php echo __('Male'); ?></th>
						<th><?php echo __('Female'); ?></th>
						<th><?php echo __('Total'); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php
					foreach ($programmes as $programmeId => $programme):
						$maleValue = 0;
						$femaleValue = 0;

						$recordTagMale = "";
						$recordTagFemale = "";

						foreach ($genderOptions AS $genderId => $genderName):
							if (!empty($censusData[$programmeId][$genderId])):
								foreach ($source_type as $k => $v):
									if ($censusData[$programmeId][$genderId]['source'] == $v):
										if ($genderName == 'Male'):
											$recordTagMale = "row_" . $k;
										else:
											$recordTagFemale = "row_" . $k;
										endif;
									endif;
								endforeach;

								if ($genderName == 'Male'):
									$maleValue = $censusData[$programmeId][$genderId]['value'];
								else:
									$femaleValue = $censusData[$programmeId][$genderId]['value'];
								endif;
							endif;
						endforeach;

						$rowTotal = $maleValue + $femaleValue;
						$total += $rowTotal;
						?>
						<tr>
							<td><?php echo $programme['programmeName']; ?></td>
							<td><?php echo $programme['certificationName']; ?></td>
							<td class="cell-number <?php echo $recordTagMale; ?>"><?php echo $maleValue; ?></td>
							<td class="cell-number <?php echo $recordTagFemale; ?>"><?php echo $femaleValue; ?></td>
							<td class="cell-number"><?php echo $rowTotal; ?></td>
						</tr>
					<?php endforeach; ?>
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
</div>
<?php $this->end(); ?>
