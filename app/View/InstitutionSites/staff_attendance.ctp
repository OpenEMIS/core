<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Attendance'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('controller' => 'InstitutionSites', 'action' => 'staffView', $id), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'staffAttendanceEdit', $selectedYear), array('class' => 'divider'));
		}
$this->end();

$this->start('contentBody');
?>

<div id="staffAttendance" class="content_wrapper dataDisplay">
	<div class="row myyear">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'div' => false,
				'class' => 'form-control',
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'jsForm.change(this)',
				'url' => $this->params['controller'] . '/' . $this->action
			));
			?>
		</div>
	</div>
	
    <div class="row school_days">
		<div class="label"><?php echo __('School Days'); ?></div>
		<div class="value"><input type="text" class="default form-control" value="<?php echo $schoolDays; ?>" disabled="disabled" /></div>
	</div>
    <div class="legendWrapper"><?php echo $legend; ?></div>
	<table class="table table-striped table-hover table-bordered" style="margin-top: 10px;">
		<thead class="table_head">
			<tr>
                    <?php foreach($attendanceTypes AS $attendanceType): ?>
                        <th class="table_cell"><?php echo __($attendanceType['StaffAttendanceType']['national_code']); ?></th>
                    <?php endforeach; ?>
            <th class="table_cell"><?php echo __('Total'); ?></th>
            <?php
				$total = 0;
			?>
			</tr>
		</thead>
		
		<tbody class="table_body">
			<tr class="table_row">
                        <?php foreach($attendanceTypes AS $attendanceType): ?>
                            <?php $attendanceTypeId = $attendanceType['StaffAttendanceType']['id']; ?>
                            <?php $attendanceValue = $data[$attendanceTypeId]['value']; ?>
                            <?php $total += $attendanceValue; ?>
                            <td class="table_cell cell_totals"><?php echo empty($attendanceValue) ? 0 : $attendanceValue ?></td>
                        <?php endforeach; ?>
                <td class="table_cell cell_total cell_number"><?php echo $total; ?></td>
			</tr>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
