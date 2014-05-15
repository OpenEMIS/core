<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Staff'));
$this->start('contentActions');
if($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'staffEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<?php echo $this->Html->tableHeaders(array(__('Position'), __('Male'), __('Female'), __('Total'))); ?>
		</thead>
		<tbody>
			<?php
			$tableData = array();
			$total = 0;
			foreach($data as $obj) {
				if($obj['staff_category_visible'] == 1) {
					$total += $obj['male'] + $obj['female'];
					$recordTag="";
					foreach ($source_type as $k => $v) {
						if ($obj['source']==$v) {
							$recordTag = "row_" . $k;
						}
					}
					$male = is_null($obj['male']) ? 0 : $obj['male'];
					$female = is_null($obj['female']) ? 0 : $obj['female'];
					$subtotal = $obj['male'] + $obj['female'];
					$tableData[] = array(
						$obj['staff_category_name'],
						array($male, array('class' => 'cell-number')),
						array($female, array('class' => 'cell-number')),
						array($subtotal, array('class' => 'cell-number'))
					);
				}
			}
			echo $this->Html->tableCells($tableData, array('class' => $recordTag), array('class' => $recordTag));
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3" class="cell-number"><?php echo __('Total'); ?></td>
				<td class="cell-number"><?php echo $total; ?></td>
			</tr>
		</tfoot>
	</table>
</div>

<?php $this->end(); ?>
