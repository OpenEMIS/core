<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'behaviour', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusBehaviour', array(
	'inputDefaults' => array('label' => false, 'div' => false),
	'url' => array('controller' => 'Census', 'action' => 'behaviourEdit')
));
echo $this->element('census/year_options');
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
			$index = 0;
			$total = 0;
			foreach ($behaviourCategories AS $catId => $catName):
				$subTotal = 0;
				?>
				<tr>
					<td><?php echo $catName; ?></td>
					<?php
					foreach ($genderOptions AS $genderId => $genderName):
						?>
						<td>
							<div class="input_wrapper">
								<?php
								echo $this->Form->hidden($index . '.id', array('value' => !empty($data[$catId][$genderId]['census_id']) ? $data[$catId][$genderId]['census_id'] : 0));
								echo $this->Form->hidden($index . '.student_behaviour_category_id', array('value' => $catId));
								echo $this->Form->hidden($index . '.gender_id', array('value' => $genderId));

								$record_tag = "";
								foreach ($source_type as $k => $v):
									if (isset($data[$catId][$genderId]['source']) && $data[$catId][$genderId]['source'] == $v):
										$record_tag = "row_" . $k;
									endif;
								endforeach;

								if (!empty($data[$catId][$genderId]['value'])):
									$value = $data[$catId][$genderId]['value'];
								else:
									$value = 0;
								endif;

								echo $this->Form->input($index . '.value', array(
									'class' => 'computeTotal ' . $record_tag,
									'type' => 'text',
									'maxlength' => 10,
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
                <td></td>
                <td></td>
                <td class="cell-label"><?php echo __('Total'); ?></td>
                <td class="cell-value cell-number"><?php echo $total; ?></td>
            </tr>
        </tfoot>
    </table>
	<?php
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'behaviour', $selectedYear)));
	echo $this->Form->end();
	?>
</div>
<?php $this->end(); ?>
