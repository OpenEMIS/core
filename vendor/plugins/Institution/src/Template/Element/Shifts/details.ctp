<style>
.vertical-align-top {
	vertical-align: top !important;
}
</style>

<?php if ($action == 'replicate') : ?>
	<label><?= __('Shift Details'); ?></label>
<?php endif ?>

<?php if (!empty($attr['data'])) { ?>
	<div class="form-input table-full-width">
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table">
					<thead>
						<tr>
							<th><?= $this->Label->get('InstitutionShifts.shift_option_id'); ?></th>
							<th><?= $this->Label->get('InstitutionShifts.start_time'); ?></th>
							<th><?= $this->Label->get('InstitutionShifts.end_time'); ?></th>
							<th><?= $this->Label->get('InstitutionShifts.institution_id'); ?></th>
							<th><?= $this->Label->get('InstitutionShifts.location_institution_id'); ?></th>
						</tr>
					</thead>
					
					<tbody>
						<?php foreach($attr['data'] as $index) { ?>
						<tr>
							<td class="vertical-align-top"><?php echo $index['Shift']; ?></td>
							<td class="vertical-align-top"><?php echo $index['StartTime']; ?></td>
							<td class="vertical-align-top"><?php echo $index['EndTime']; ?></td>
							<td class="vertical-align-top"><?php echo $index['Owner']; ?></td>
							<td class="vertical-align-top"><?php echo $index['Occupier']; ?></td>
						</tr>
						<?php } ?>
					</tbody>				
				</table>
			</div>
		</div>
	</div>
<?php } ?>