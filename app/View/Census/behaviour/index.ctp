<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'behaviourEdit', $selectedAcademicPeriod), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/academic_period_options');
?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th class="cell_category"><?php echo __('Category'); ?></th>
                <th><?php echo __('Male'); ?></th>
                <th><?php echo __('Female'); ?></th>
                <th><?php echo __('Total'); ?></th>
            </tr>
        </thead>
        <tbody>
			<?php
			$total = 0;
			foreach ($behaviourCategories AS $catId => $catName):
				$maleValue = 0;
				$femaleValue = 0;

				$recordTagMale = "";
				$recordTagFemale = "";
				foreach ($genderOptions AS $genderId => $genderName):
					if (!empty($data[$catId][$genderId])):
						foreach ($source_type AS $k => $v):
							if ($data[$catId][$genderId]['source'] == $v):
								if ($genderName == 'Male'):
									$recordTagMale = "row_" . $k;
								else:
									$recordTagFemale = "row_" . $k;
								endif;
							endif;
						endforeach;

						if ($genderName == 'Male'):
							$maleValue = $data[$catId][$genderId]['value'];
						else:
							$femaleValue = $data[$catId][$genderId]['value'];
						endif;
					endif;
				endforeach;

				$rowTotal = $maleValue + $femaleValue;
				$total += $rowTotal;
				?>
				<tr>
					<td><?php echo $catName['name']; ?></td>
					<td class="cell-number <?php echo $recordTagMale; ?>"><?php echo $maleValue; ?></td>
					<td class="cell-number <?php echo $recordTagFemale; ?>"><?php echo $femaleValue; ?></td>
					<td class="cell-numbe"><?php echo $rowTotal; ?></td>
				</tr>
				<?php
			endforeach;
			?>
        </tbody>

        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td class="cell-label"><?php echo __('Total'); ?></td>
                <td class="cell-value cell-number"><?php echo $total; ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php $this->end(); ?>
