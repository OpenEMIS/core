<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('report', 'stylesheet', array('inline' => false));
echo $this->Html->script('census_enrolment', false);
$this->extend('/Elements/layout/container');
//$this->assign('contentId', 'enrolment');
$this->assign('contentHeader', __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'enrolment', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->Form->create('CensusStudent', array(
	'inputDefaults' => array('label' => false, 'div' => false),	
	'url' => array('controller' => 'Census', 'action' => 'enrolmentEdit')
));
echo $this->element('census/year_options');
?>
<div id="enrolment" class=" edit">
<?php foreach($data as $key => $obj) : ?>

<fieldset class="section_group report" url="Census/enrolmentAjax/<?php echo $selectedYear; ?>" programme_id="<?php echo $obj['education_programme_id'];?>" admission_age="<?php echo $obj['admission_age'];?>">
	<legend><?php echo $obj['name']; ?></legend>
	
	<div class="row page-controls">
		<div class="col-md-4">
		<?php
			echo $this->Form->input('student_category_id', array(
				'id' => 'StudentCategoryId',
				'class' => 'form-control',
				'options' => $categoryList,
				'onchange' => 'CensusEnrolment.get(this)',
				'autocomplete' => 'off'
			));
		?>
		</div>
	</div>
	
	<?php 
	$gradesCount = count($obj['grades']);
	?>
	
	<div class="table-responsive ajaxContentHolder">
		<table class="table table-bordered">
			 <tbody>
				<tr class="th_bg">
					<td rowspan="2"><?php echo __('Age'); ?></td>
					<td rowspan="2"><?php echo __('Gender'); ?></td>
					<td colspan="<?php echo $gradesCount; ?>"><?php echo __('Grades'); ?></td>
					<td colspan="2"><?php echo __('Totals'); ?></td>
					<td rowspan="2" class="cell_delete"></td>
				 </tr>
				 <tr class="th_bg">
					<?php 
					foreach($obj['grades'] AS $gradeName) {
						echo '<td>' . $gradeName . '</td>';
					}
					?>
					<td></td>
					<td><?php echo __('Both'); ?></td>
				</tr>
				
				<?php foreach($obj['dataRowsArr'] AS $row){?>
					<?php if($row['type'] == 'input'){?>
						<tr age="<?php echo $row['age'] ?>" gender="<?php echo $row['gender'] == 'M' ? 'male' : 'female'; ?>" type="input">
					<?php }else{?>
						<tr>
					<?php }?>
						<?php foreach($row['data'] AS $dataKey => $dataValue){?>
							<?php if($dataKey == 'grades'){?>
								<?php foreach($dataValue AS $gradeId => $censusValue){?>
									<td class="inputField">
										<?php if($row['type'] == 'input'){?>
											<div class="input_wrapper" census_id="<?php echo $censusValue['censusId']; ?>" grade_id ="<?php echo $gradeId; ?>">
												<?php 
														$record_tag="";
														foreach ($source_type as $k => $v) {
																if (isset($censusValue['source']) && $censusValue['source'] == $v) {
																		$record_tag = "row_" . $k;
																}
														}
												
														echo $this->Form->input($row['gender'] == 'M' ? 'male' : 'female', array(
																'type' => 'text',
																'class' => $record_tag,
																'label' => false,
																'div' => false,
																'value' => $censusValue['value'],
																'defaultValue' => $censusValue['value'],
																'maxlength' => 10,
																'autocomplete' => 'off',
																'onkeypress' => 'return utility.integerCheck(event);',
																'onkeyup' => 'CensusEnrolment.computeByAgeGender(this);'
														));
												?>
											</div>
										<?php }else{?>
											<?php echo $censusValue['value']; ?>
										<?php }?>
									</td>
								<?php }?>
							<?php }else if($dataKey == 'firstColumn' || $dataKey == 'lastColumn'){?>
								<td rowspan="2"><?php echo $dataValue; ?></td>
							<?php } else if ($dataKey == 'age') { ?>
								<?php if(isset($row['ageEditable']) && $row['ageEditable'] == 'yes'){?>
									<td rowspan="2" class="inputField">
										<div class="input_wrapper">
														<?php
														$record_tag = "";
														foreach ($source_type as $k => $v) {
															if ($v == 0) {
																$record_tag = "row_" . $k;
																break;
															}
														}

														echo $this->Form->input('age', array(
															'type' => 'text',
															'class' => $record_tag,
															'label' => false,
															'div' => false,
															'value' => $dataValue,
															'defaultValue' => $dataValue,
															'maxlength' => 10,
															'autocomplete' => 'off',
															'onkeypress' => 'return utility.integerCheck(event);'
														));
														?>
										</div>
									</td>
								 <?php }else{?>
									<td rowspan="2"><?php echo $dataValue; ?></td>
								 <?php }?>
							<?php }else if($dataKey == 'colspan2'){?>
								<td colspan="2"><?php echo $dataValue; ?></td>
							<?php }else if($dataKey == 'firstHalf'){?>
								<td colspan="<?php echo $row['colspan']; ?>" class="rowTotalLeftCol"><?php echo $dataValue; ?></td>
							<?php }else if($dataKey == 'totalAllGrades'){?>
								<td colspan="2" class="rowTotalRightCol"><?php echo $dataValue; ?></td>
							<?php }else if($dataKey == 'totalByAgeMale' || $dataKey == 'totalByAgeFemale'){?>
								<td class="<?php echo $dataKey; ?>"><?php echo $dataValue; ?></td>
							<?php }else if($dataKey == 'totalByAgeAllGender'){?>
								<td rowspan="2" class="<?php echo $dataKey; ?>"><?php echo $dataValue; ?></td>
							<?php }else{?>
								<td><?php echo $dataValue; ?></td>
							<?php }?>
						<?php }?>
						<?php if($row['type'] == 'input' && $row['gender'] == 'M'){?>
							<?php if(isset($row['ageEditable']) && $row['ageEditable'] == 'yes'){?>
								<td rowspan="2" class="cell_delete">
									<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this)"></span>
								</td>
							<?php }else{?>
								<td rowspan="2" class="cell_delete"></td>
							<?php }?>
						<?php }else if($row['type'] == 'read-only' && $row['gender'] == 'M'){?>
							<td rowspan="2" class="cell_delete"></td>
						<?php }else if($row['type'] == 'read-only' && $row['gender'] == 'all'){?>
							<td class="cell_delete"></td>
						<?php }?>
					</tr>
				<?php }?>
			</tbody>
		</table>
	</div>
</fieldset>

<?php endforeach; ?>

<?php if(!empty($data)) : ?>
	<div class="controls">
		<input type="button" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="CensusEnrolment.save()" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'enrolment', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
<?php endif; ?>
</div>
<?php echo $this->Form->end(); ?>
	
<?php $this->end(); ?>
