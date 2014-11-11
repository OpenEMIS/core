<?php
$ctr = 1;


//echo $is_edit;
foreach($data as $infraname => $arrval){
	$ctr = 1;
	$ctrModel = 1;
	$modelName = Inflector::singularize($infraname);
	foreach($arrval['types']  as $typeid => $typeVal){
		echo '<tr class="table_row '.($ctr%2==0?'even':'').'">
				<td class="table_cell">'.$typeVal.'</td>';
		 
		$statusTotal = 0;
		foreach($arrval['status'] as $statids => $statVal){
			$inputName = 'data[Census'.$modelName.']['.$ctrModel.']';
			
			$cell_html="";
			$infraSource = "";
			if($infraname == 'Buildings'){ //building = got 3 dimension
				
				$infraVal = isset($data[$infraname]['data'][$typeid][$statids][$material_id]['value'])
						  ? $data[$infraname]['data'][$typeid][$statids][$material_id]['value']
						  : '';
				$infraSource = isset($data[$infraname]['data'][$typeid][$statids][$material_id]['source'])
						  ? $data[$infraname]['data'][$typeid][$statids][$material_id]['source']
						  : '';
				
				if($is_edit == "true"){
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_material_id]" value="'.$material_id.'">';
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_status_id]" value="'.$statids.'">';
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_building_id]" value="'.$typeid.'">';
					$cell_html.= $this->Form->input('value', array(
							'class'=>@$record_tag,
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
					$cell_html.= '<input type="hidden" name="' . $inputName . '[id]" value="'.(isset($data[$infraname]['data'][$typeid][$statids][$material_id]['id'])?$data[$infraname]['data'][$typeid][$statids][$material_id]['id']:'').'">';
				} else {
					$cell_html.= $infraVal ;
				}
			 }elseif($infraname == 'Sanitation'){ //building = got 3 dimension
				
				$infraVal = isset($data[$infraname]['data'][$typeid][$statids][$material_id][$genderId]['value'])
						  ? $data[$infraname]['data'][$typeid][$statids][$material_id][$genderId]['value']
						  : '';
				$infraSource = @isset($data[$infraname]['data'][$typeid][$statids][$material_id][$genderId][$source])
						  ? $data[$infraname]['data'][$typeid][$statids][$material_id][$genderId][$source]
						  : '';

				if($is_edit == "true"){
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_material_id]" value="'.$material_id.'">';
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_status_id]" value="'.$statids.'">';
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_sanitation_id]" value="'.$typeid.'">';
					$cell_html.= $this->Form->input('value', array(
							'class'=>@$record_tag,
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
					$cell_html.= '<input type="hidden" name="' . $inputName . '[id]" value="'.(isset($data[$infraname]['data'][$typeid][$statids][$material_id][$genderId]['id']) ? $data[$infraname]['data'][$typeid][$statids][$material_id][$genderId]['id'] : '').'">';
				} else {
					$cell_html.= $infraVal;
				}
			 }else{
				$infraVal = isset($data[$infraname]['data'][$typeid][$statids]['value'])
						  ? $data[$infraname]['data'][$typeid][$statids]['value']
						  : '';
				$infraSource = isset($data[$infraname]['data'][$typeid][$statids]['source'])
						  ? $data[$infraname]['data'][$typeid][$statids]['source']
						  : '';

				if($is_edit == "true"){  
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_status_id]" value="'.$statids.'">';
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_'.  rtrim(strtolower($infraname),"s").'_id]" value="'.$typeid.'">';
					$cell_html.= '<input type="hidden" name="' . $inputName . '[infrastructure_building_id]" value="'.$typeid.'">';
					$cell_html.= $this->Form->input('value', array(
							'class'=>$record_tag,
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
					$cell_html.= '<input type="hidden" name="' . $inputName . '[id]" value="'. (isset($data[$infraname]['data'][$typeid][$statids]['id'])?$data[$infraname]['data'][$typeid][$statids]['id']:'').'">';
				} else {
					$cell_html.= $infraVal;
				}
			 }
			 
			$record_tag="";
			foreach ($source_type as $k => $v) {
				if ($infraSource==$v) {
					$record_tag = "row_" . $k;
				}
			}
		
			 echo '<td class="table_cell cell_number ' . $record_tag.'">'. $cell_html . '</td>';
			 $statusTotal += $infraVal;
			 $ctrModel++;
		}
		echo '<td class="table_cell cell_total cell_number">' . ($statusTotal>0 ? $statusTotal : '') . '</td>';
		echo '</tr>';
		$ctr++;
	}
}
?>