<?php
$index = $order;
// $fieldName = sprintf('data[StudentsInstitution][%s][%%s]', $index);
?>
<div class="table_row">
	<div class="table_cell">
		<input type="hidden" value="0" name="data[InstitutionSiteStudent][<?php echo $index; ?>][id]" />
		<select class="full_width" name="data[InstitutionSiteStudent][<?php echo $index; ?>][institution_site_id]">
		<?php 
			/*foreach ($institutions as $institutionKey => $institutionValue):
				foreach($institutionValue as $key => $value):
		?>
			<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
		<?php 
				endforeach; 
			endforeach; */
		?>
		<?php
			foreach ($institutions as $arrInstitutionValue){
				echo "<option value=".$arrInstitutionValue['InstitutionSite']['id'].">".$arrInstitutionValue['Institution']['name']." - ".$arrInstitutionValue['InstitutionSite']['name']."</option>";
			}
		?>
		</select>
		
	</div>
	<div class="table_cell cell_start_date">
		<?php 
			echo $this->Utility->getDatePicker($this->Form, 'start_date', array('name' => 'data[InstitutionSiteStudent]['.$index.'][start_date]', 'order' => 'my')); 
			// echo $this->Utility->getDatePicker($this->Form, 'start_date', array('name' => 'data[InstitutionSiteStudent]['.$index.'][start_date]', 'order' => 'my', 'value' => '2004-01-01')); 
		?>
	</div>
	<div class="table_cell cell_end_date">
		<?php 
			echo $this->Utility->getDatePicker($this->Form, 'end_date', array('name' => 'data[InstitutionSiteStudent]['.$index.'][end_date]', 'order' => 'my')); 
			// echo $this->Utility->getDatePicker($this->Form, 'end_date', array('name' => 'data[InstitutionSiteStudent]['.$index.'][end_date]', 'order' => 'my', 'value' => '2006-06-01')); 
		?>
	</div>
	<div class="table_cell"><span class="icon_delete" title="<?php echo __('Delete'); ?>" onClick="objStudent.removeRow(this)"></span></div>
</div>