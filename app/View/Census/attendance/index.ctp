<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance'));

$this->start('contentActions');
if ($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'attendanceEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div id="attendance" class="dataDisplay">


	<div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value"><input type="text" class="default" value="<?php echo $schoolDays; ?>" disabled="disabled" /></div>
	</div>

	<?php foreach ($data as $programmeData) { ?>
		<fieldset class="section_group">
			<legend><?php echo $programmeData['programmeName']; ?></legend>

			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th class="cell_grade"><?php echo __('Grade'); ?></th>
						<th><?php echo __('Days Absent') . '<br>' . __('(Male)'); ?></th>
						<th><?php echo __('Days Absent') . '<br>' . __('(Female)'); ?></th>
						<th><?php echo __('Days Attended') . '<br>' . __('(Male)'); ?></th>
						<th><?php echo __('Days Attended') . '<br>' . __('(Female)'); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php
					$total = 0;
					foreach ($programmeData['genders'] as $record) {
						$record_tag = "";
						switch ($record['source']) {
							case 1:
								$record_tag.="row_external";
								break;
							case 2:
								$record_tag.="row_estimate";
								break;
						}
						?>
						<tr>
							<td><?php echo $record['education_grade_name']; ?></td>
							<?php 
							
							 foreach($genderOptions AS $genderId => $genderName):
								 if(isset($record[$genderId])):
									 $value = $record['value'];
								 endif;
							?>
								<td class="cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['absent_male']) ? 0 : $record['absent_male']; ?></td>
							<?php 
							 endforeach;
							?>
							<td class="cell_number"><?php echo is_null($record['attended_male']) ? 0 : $record['attended_male']; ?></td>
							<td class="cell_number"><?php echo is_null($record['attended_female']) ? 0 : $record['attended_female']; ?></td>
						</tr>
						<?php
					} // end for
					?>
				</tbody>
			</table>
		</fieldset>
	<?php } ?>
</div>
<?php $this->end(); ?>