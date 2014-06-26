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
			foreach($data as $record) {
				if($record['staff_category_visible'] == 1) {
					$total += $record['male'] + $record['female'];
					$record_tag="";
					switch ($record['source']) {
						case 1:
							$record_tag.="row_external";break;
						case 2:
							$record_tag.="row_estimate";break;
					}
			?>
			<tr>
				<?php
				echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
				echo $this->Form->hidden($index . '.staff_category_id', array('value' => $record['staff_category_id']));
				?>
				<td class="<?php echo $record_tag; ?>"><?php echo $record['staff_category_name']; ?></div>
				<td class="cell-number">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input($index . '.male', array(
						'type' => 'text',
						'class' => 'computeTotal ' . $record_tag,
						'value' => empty($record['male']) ? 0 : $record['male'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)',
						'onkeyup' => 'Census.computeTotal(this)'
					));
					?>
					</div>
				</td>
				<td class="cell-number">
					<div class="input_wrapper">
					<?php 
					echo $this->Form->input($index . '.female', array(
						'type' => 'text',
						'class' => 'computeTotal ' . $record_tag,
						'value' => empty($record['female']) ? 0 : $record['female'],
						'maxlength' => 10,
						'onkeypress' => 'return utility.integerCheck(event)',
						'onkeyup' => 'Census.computeTotal(this)'
					));
					?>
					</div>
				</td>
				<td class="cell-total cell-number"><?php echo $record['male'] + $record['female']; ?></div>
			</div>
			<?php 
					$index++; 
				} 
			}
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
