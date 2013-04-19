<?php 
if($data !== false) {
	if(empty($data)) { ?>
	
		<div class="alert none" type="2"><?php echo __("Your search returns no result."); ?></div>
		
<?php
	} else {
		foreach($data as $obj) {
			$id_no = $this->Utility->highlight($searchStr, $obj['identification_no']);
			$firstName = $this->Utility->highlight($searchStr, $obj['first_name']);
			$lastName = $this->Utility->highlight($searchStr, $obj['last_name']);
			$name = $firstName . ' ' . $lastName;
?>
		
		<div class="table_row">
			<div class="table_cell cell_id_no"><?php echo $id_no; ?></div>
			<div class="table_cell" name="<?php echo $obj['first_name'] . ' ' . $obj['last_name']; ?>"><?php echo $name; ?></div>
			<div class="table_cell cell_icon_action"><span class="icon_plus" student-id="<?php echo $obj['id']; ?>"></span></div>
		</div>
	
<?php 
		}
	} 
} else {
?>
	<div class="alert none" type="0"><?php echo __("Your search returned too many results. Please refine your search criteria."); ?></div>

<?php } ?>