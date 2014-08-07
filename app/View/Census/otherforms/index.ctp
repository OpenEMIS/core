<?php
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Other Forms'));

$this->start('contentActions');
if($_edit && $isEditable) {
	echo $this->Html->link(__('Edit'), array('action' => 'otherformsEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<?php foreach($data as $arrval) { ?>
<div class="custom_grid">
	<fieldset class="section_break">
		<legend><?php echo $arrval['CensusGrid']['name']; ?></legend>
	</fieldset>
	
	<div class="desc"><?php echo $arrval['CensusGrid']['description']; ?></div>
	<div class="x_title"><?php echo $arrval['CensusGrid']['x_title']; ?></div>
	
	<div class="table_wrapper">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
				<th class="y_col">&nbsp;</th>
				<?php 
				foreach($arrval['CensusGridXCategory'] as $statVal) {
					if ($statVal['visible']==1) {
						echo '<th>' . $statVal['name'] . '</th>';
					}
				}
				?>
				</tr>
			</thead>
			
			<tbody id="<?php echo $statVal['name']; ?>_section">
			<?php
			$ctr = 1;
			foreach($arrval['CensusGridYCategory'] as $yCatId => $yCatName) {
			?>
				<tr>
					<td><?php echo $yCatName['name']; ?></td>
					<?php 
					foreach($arrval['CensusGridXCategory'] as $xCatId => $xCatName) {
						if ($xCatName['visible']==1) {
						echo '<td>';
						$val = isset($arrval['answer'][$xCatName['id']][$yCatName['id']]['value'])
							 ? $arrval['answer'][$xCatName['id']][$yCatName['id']]['value']
							 : '';
						echo $val;
						echo '</td>';
						}
					} 
					?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php }	?>

<?php		
	foreach($datafields as $arrVals){
		if($arrVals['CensusCustomField']['type'] == 1){//Label
			echo '<fieldset class="section_break">
						<legend>'.$arrVals['CensusCustomField']['name'].'</legend>
				  </fieldset>';
		}elseif($arrVals['CensusCustomField']['type'] == 2) {//Text
			echo '<div class="custom_field">
					<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
					<div class="field_value">';
					if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
						echo $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
					}
			echo '</div></div>';
		}elseif($arrVals['CensusCustomField']['type'] == 3) {//DropDown
			echo '<div class="custom_field">
							<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
							<div class="field_value">';
								/*
								 if(count($arrVals['CensusCustomFieldOption'])> 0){
									echo '<select>';
									foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
										
										if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
											$defaults =  $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
										}
										echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
										
									}
									echo '</select>';
									
								}
								 */
								if(count($arrVals['CensusCustomFieldOption'])> 0){
									$defaults = '';
									foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
										//pr($datavalues);
										//die;
										if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
											$defaults =  $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
										}
										echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
									}
								}
					  echo '</div>
					</div>';
			
			
			
		}elseif($arrVals['CensusCustomField']['type'] == 4) {
			echo '<div class="custom_field">
							<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
							<div class="field_value">';
								$defaults = array();
								 if(count($arrVals['CensusCustomFieldOption'])> 0){
									
									foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
										
										if(isset($datavalues[$arrVals['CensusCustomField']['id']])){
											if(count($datavalues[$arrVals['CensusCustomField']['id']] > 0)){
												foreach($datavalues[$arrVals['CensusCustomField']['id']] as $arrCheckboxVal){
													$defaults[] = $arrCheckboxVal['value'];
												}
											}
											
										}
										echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';
										
									}
									
								}
								 
					  echo '</div>
					</div>';
		}elseif($arrVals['CensusCustomField']['type'] == 5) {
			echo '<div class="custom_field">
							<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
							<div class="field_value">';
							if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
								echo $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
							}
					  echo '</div>
					</div>';
		}
		
	}
?>
<?php $this->end(); ?>
