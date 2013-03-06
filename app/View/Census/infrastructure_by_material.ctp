<?php
$ctr = 1;


//echo $is_edit;
foreach($data as $infraname => $arrval){
	$ctr = 1;
	$ctrModel = 1;
	$modelName = Inflector::singularize($infraname);
	foreach($arrval['types']  as $typeid => $typeVal){
		echo '<div class="table_row '.($ctr%2==0?'even':'').'">
				<div class="table_cell">'.$typeVal.'</div>';
		 
		$statusTotal = 0;
		foreach($arrval['status'] as $statids => $statVal){
			$inputName = 'data[Census'.$modelName.']['.$ctrModel.']';
			echo '<div class="table_cell cell_number">';
			if($infraname == 'Buildings'){ //building = got 3 dimension
				
				$infraVal = isset($data[$infraname]['data'][$typeid][$statids][$material_id]['value'])
						  ? $data[$infraname]['data'][$typeid][$statids][$material_id]['value']
						  : '';
				
				
				
				if($is_edit == "true"){
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_material_id]" value="'.$material_id.'">';
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_status_id]" value="'.$statids.'">';
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_building_id]" value="'.$typeid.'">';
					echo $this->Form->input('value', array(
							'type' => 'text',
							'label' => false,
							'div' => false,
							'maxlength' => 8,
							'onkeyup' => 'Infrastructure.computeTotal(this)',
							'name' => $inputName . '[value]',
							'before' => '<div class="input_wrapper">',
							'after' => '</div>',
							'value' => $infraVal
						)
					);
					echo '<input type="hidden" name="' . $inputName . '[id]" value="'.(isset($data[$infraname]['data'][$typeid][$statids][$material_id]['id'])?$data[$infraname]['data'][$typeid][$statids][$material_id]['id']:'').'">';
				} else {
					echo $infraVal ;
				}
			 }elseif($infraname == 'Sanitation'){ //building = got 3 dimension
				
				$infraVal = isset($data[$infraname]['data'][$typeid][$statids][$material_id][$gender])
						  ? $data[$infraname]['data'][$typeid][$statids][$material_id][$gender]
						  : '';
				
				
				
				if($is_edit == "true"){
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_material_id]" value="'.key($data[$infraname]['materials']).'">';
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_status_id]" value="'.$statids.'">';
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_sanitation_id]" value="'.$typeid.'">';
					echo $this->Form->input('value', array(
							'type' => 'text',
							'label' => false,
							'div' => false,
							'maxlength' => 8,
							'onkeyup' => 'Infrastructure.computeTotal(this)',
							'name' => $inputName . '[value]',
							'before' => '<div class="input_wrapper">',
							'after' => '</div>',
							'value' => $infraVal
						)
					);
					echo '<input type="hidden" name="' . $inputName . '[id]" value="'.(isset($data[$infraname]['data'][$typeid][$statids][$material_id]['id'])?$data[$infraname]['data'][$typeid][$statids][$material_id]['id']:'').'">';
				} else {
					echo $infraVal;
				}
			 }else{
				$infraVal = isset($data[$infraname]['data'][$typeid][$statids]['value'])
						  ? $data[$infraname]['data'][$typeid][$statids]['value']
						  : '';
				if($is_edit == "true"){  
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_status_id]" value="'.$statids.'">';
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_'.  rtrim(strtolower($infraname),"s").'_id]" value="'.$typeid.'">';
					echo '<input type="hidden" name="' . $inputName . '[infrastructure_building_id]" value="'.$typeid.'">';
					echo $this->Form->input('value', array(
							'type' => 'text',
							'label' => false,
							'div' => false,
							'maxlength' => 8,
							'onkeyup' => 'Infrastructure.computeTotal(this)',
							'name' => $inputName . '[value]',
							'before' => '<div class="input_wrapper">',
							'after' => '</div>',
							'value' => $infraVal
						)
					);
					echo '<input type="hidden" name="' . $inputName . '[id]" value="'. (isset($data[$infraname]['data'][$typeid][$statids]['id'])?$data[$infraname]['data'][$typeid][$statids]['id']:'').'">';
				} else {
					echo $infraVal;
				}
			 }
			 echo '</div>';
			 $statusTotal += $infraVal;
			 $ctrModel++;
		}
		echo '<div class="table_cell cell_total cell_number">' . ($statusTotal>0 ? $statusTotal : '') . '</div>';
		echo '</div>';
		$ctr++;
	}
}
?>