<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('report', 'stylesheet', array('inline' => false));
echo $this->Html->script('census_enrolment', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'enrolment');
$this->assign('contentHeader', __('Students'));
$this->start('contentActions');
if($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'enrolmentEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<?php foreach($data as $key => $obj) : ?>
<fieldset class="section_group report" url="Census/enrolmentAjax/<?php echo $selectedYear; ?>" programme_id="<?php echo $obj['education_programme_id'];?>" admission_age="<?php echo $obj['admission_age'];?>">
	<legend><?php echo $obj['name']; ?></legend>
	<div class="row page-controls">
		<div class="col-md-4">
		<?php
			echo $this->Form->input('student_category_id', array(
				'id' => 'StudentCategoryId',
				'label' => false,
				'div' => false,
				'class' => 'form-control',
				'options' => $categoryList,
				'onchange' => 'CensusEnrolment.get(this)',
				'autocomplete' => 'off'
			));
		?>
		</div>
	</div>
	<?php 
	$gradesCount = count($obj['grades']);
	?>
	<div class="table-responsive ajaxContentHolder">
		<table class="table table-bordered">
			<tbody>
				<tr class="th_bg">
					<td rowspan="2"><?php echo __('Age'); ?></td>
					<td rowspan="2"><?php echo __('Gender'); ?></td>
					<td colspan="<?php echo $gradesCount; ?>"><?php echo __('Grades'); ?></td>
					<td colspan="2"><?php echo __('Totals'); ?></td>
				</tr>
				<tr class="th_bg">
					<?php 
					foreach($obj['grades'] AS $gradeName) {
						echo '<td>' . $gradeName . '</td>';
					}
					?>
					<td></td>
					<td><?php echo __('Both'); ?></td>
				</tr>
				<?php
				foreach($obj['dataRowsArr'] AS $row) {
					if($row['type'] == 'input') {
						echo sprintf('<tr age="%s" gender="%s">', $row['age'], ($row['gender'] == 'M' ? 'male' : 'female'));
					} else {
						echo '<tr>';
					}
					foreach($row['data'] as $dataKey => $dataValue) {
						if($dataKey == 'grades') {
							foreach($dataValue AS $gradeId => $censusValue) {
								$record_tag="";
								foreach ($source_type as $k => $v) {
									if (isset($censusValue['source']) && $censusValue['source'] == $v) {
										$record_tag = "row_" . $k;
									}
								}
								echo '<td class="' . $record_tag . '">' . $censusValue['value'] . '</td>';
							}
						} else if($dataKey == 'firstColumn' || $dataKey == 'lastColumn' || $dataKey == 'age') {
							echo '<td rowspan="2">' . $dataValue . '</td>';
						} else if($dataKey == 'colspan2') {
							echo '<td colspan="2">' . $dataValue . '</td>';
						} else {
							echo '<td>' . $dataValue . '</td>';
						}
					}
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
</fieldset>
<?php 
endforeach;
$this->end();
?>
