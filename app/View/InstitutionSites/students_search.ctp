<?php 
if($data !== false) {
	if(empty($data)) { ?>
		<div class="alert none" type="2"><?php echo __("Your search returns no result."); ?></div>
<?php
	} else {
		foreach($data as $obj) {
			$id = $obj['Student']['id'];
			$gender = $this->Utility->formatGender($obj['Student']['gender']);
			$id_no = $obj['Student']['identification_no'];
			$firstName = $obj['Student']['first_name'];
			$lastName = $obj['Student']['last_name'];
?>
		<div class="table_row" row-id="<?php echo $id; ?>" id-no="<?php echo $id_no; ?>" first-name="<?php echo $firstName; ?>" last-name="<?php echo $lastName; ?>" gender="<?php echo $gender; ?>" onclick="InstitutionSiteStudents.addStudent(this)">
			<div class="table_cell cell_id_no"><?php echo $this->Utility->highlight($search, $id_no); ?></div>
			<div class="table_cell"><?php echo $this->Utility->highlight($search, $firstName); ?></div>
			<div class="table_cell"><?php echo $this->Utility->highlight($search, $lastName); ?></div>
		</div>
<?php 
		}
	} 
} else {
?>
	<div class="alert none" type="0"><?php echo __("Your search returned too many results. Please refine your search criteria."); ?></div>
<?php } ?>