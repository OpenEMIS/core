<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Staff'));
$this->start('contentActions');
if($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'CensusStaff', 'edit', $selectedAcademicPeriod), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/academic_period_options');
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

			foreach($positionTitles AS $titleId => $titleName){
				$maleValue = 0;
				$femaleValue = 0;
				
				$recordTagMale = "";
				$recordTagFemale = "";
				
				foreach($genderOptions AS $genderId => $genderName){	
					if(!empty($data[$titleId][$genderId])){
						foreach ($source_type as $k => $v) {
							if ($data[$titleId][$genderId]['source'] == $v) {
								if($genderOptions[$genderId] == 'Male'){
									$recordTagMale = "row_" . $k;
								}else{
									$recordTagFemale = "row_" . $k;
								}
							}
						}
						
						if($genderName == 'Male'){
							$maleValue = $data[$titleId][$genderId]['value'];
						}else{
							$femaleValue = $data[$titleId][$genderId]['value'];
						}
					}
				}
				
				$subTotal = $maleValue + $femaleValue;
				$total += $subTotal;
	
				$tableData[] = array(
					$titleName,
					array($maleValue, array('class' => 'cell-number ' . $recordTagMale)),
					array($femaleValue, array('class' => 'cell-number ' . $recordTagFemale)),
					array($subTotal, array('class' => 'cell-number'))
				);
			}
			
			echo $this->Html->tableCells($tableData);
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
