<?php 
if($data !== false) {
	if(empty($data)) { ?>
		<div class="alert none" type="2"><?php echo __("Your search returns no result."); ?></div>
<?php
	} else {
		foreach($data as $obj) {
			$id = $obj['Teacher']['id'];
			$gender = $obj['Teacher']['gender'];
			$id_no = $obj['Teacher']['identification_no'];
			$firstName = $obj['Teacher']['first_name'];
			$lastName = $obj['Teacher']['last_name'];
?>
		<div class="table_row" row-id="<?php echo $id; ?>" id-no="<?php echo $id_no; ?>" first-name="<?php echo $firstName; ?>" last-name="<?php echo $lastName; ?>" gender="<?php echo $gender; ?>">
			<div class="table_cell cell_id_no"><?php echo $this->Utility->highlight($search, $id_no); ?></div>
			<div class="table_cell"><?php echo $this->Utility->highlight($search, $firstName); ?></div>
			<div class="table_cell"><?php echo $this->Utility->highlight($search, $lastName); ?></div>
			<div class="table_cell cell_icon_action"><span class="icon_plus" onclick="InstitutionSiteTeachers.addTeacher(this)"></span></div>
		</div>
<?php 
		}
	} 
} else {
?>
	<div class="alert none" type="0"><?php echo __("Your search returned too many results. Please refine your search criteria."); ?></div>
<?php } ?>