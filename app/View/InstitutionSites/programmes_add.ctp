<?php if(!empty($data)) { ?>

<div class="table_row not_highlight" row-id="0">
	<div class="table_cell" attr="name"></div>
	<div class="table_cell" attr="system">
		<select class="full_width" onchange="InstitutionSiteProgrammes.addProgramme()">
			<option value="">-- <?php echo __('Select Programme'); ?> --</option>
			<?php foreach($data as $obj) {
				$system = $obj['EducationSystem'];
				$cycle = $obj['EducationCycle'];
				$programme = $obj['EducationProgramme'];
				$option = '<option value="%d" system="%s" name="%s">%s - %s - %s</option>';
				echo sprintf($option, $programme['id'], $system['name'] . ' - ' . $cycle['name'], $programme['name'], $system['name'], $cycle['name'], $programme['name']);
			} ?>
		</select>
	</div>
	<?php if($_delete_programme) { ?>
	<div class="table_cell cell_delete"></div>
	<?php } ?>
</div>

<?php } else { ?>

<span class="alert" type="<?php echo $this->Utility->alertType['error']; ?>"><?php echo __('No programme available.'); ?></span>

<?php } ?>