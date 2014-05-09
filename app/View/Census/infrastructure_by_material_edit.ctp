<?php
$ctr = 1;
//pr($data);
foreach($data as $infraname => $arrval){
$ctr = 1;
$ctrModel = 1;
foreach($arrval['types']  as $typeid => $typeVal){
     echo '<tr class="table_row '.($ctr%2==0?'even':'').'">
             <td class="col_age">'.$typeVal.'</td>';

     foreach($arrval['status'] as $statids => $statVal){

         echo '<td class="col_total">';
         if($infraname == 'Buildings'){ //got 3 dimension
            if($is_edit){
                echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][infrastructure_material_id]" value="'.key($data[$infraname]['materials']).'">';
                echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][infrastructure_status_id]" value="'.$statids.'">';
                echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][infrastructure_building_id]" value="'.$typeid.'">';

                $infraVal = isset($data[$infraname]['data'][$typeid][$statids][1]['value'])
						  ? $data[$infraname]['data'][$typeid][$statids][1]['value']
						  : '';
				echo $this->Form->input('value', array(
						'type' => 'text',
						'label' => false,
						'div' => false,
						'name' => 'data[Census'.$infraname.']['.$ctrModel.'][value]',
						'before' => '<div class="input_wrapper">',
						'after' => '</div>',
						'value' => $infraVal
					)
				);
                echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][id]" value="'.(isset($data[$infraname]['data'][$typeid][$statids][1]['id'])?$data[$infraname]['data'][$typeid][$statids][1]['id']:'').'">';
            } else {
                echo (isset($data[$infraname]['data'][$typeid][$statids][1]['value'])?$data[$infraname]['data'][$typeid][$statids][1]['value']:'');
            }
         }else{
           if($is_edit){
             echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][infrastructure_status_id]" value="'.$statids.'">';
             echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][infrastructure_'.  rtrim(strtolower($infraname),"s").'_id]" value="'.$typeid.'">';
             echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][infrastructure_building_id]" value="'.$typeid.'">';

             $infraVal = isset($data[$infraname]['data'][$typeid][$statids]['value'])
					  ? $data[$infraname]['data'][$typeid][$statids]['value']
					  : '';
			echo $this->Form->input('value', array(
					'type' => 'text',
					'label' => false,
					'div' => false,
					'name' => 'data[Census'.$infraname.']['.$ctrModel.'][value]',
					'before' => '<div class="input_wrapper">',
					'after' => '</div>',
					'value' => $infraVal
				)
			);
             echo '<input type="hidden" name="data[Census'.$infraname.']['.$ctrModel.'][id]" value="'. (isset($data[$infraname]['data'][$typeid][$statids]['id'])?$data[$infraname]['data'][$typeid][$statids]['id']:'').'">';
           } else {
               echo (isset($data[$infraname]['data'][$typeid][$statids]['value'])?$data[$infraname]['data'][$typeid][$statids]['value']:'');
           }
         }   

         echo '</td>';
         //echo '<td class="col_total">'. $statids.'</td>';
         $ctrModel++;
     }       
     echo '</tr>';
     $ctr++;
 }
}
?>