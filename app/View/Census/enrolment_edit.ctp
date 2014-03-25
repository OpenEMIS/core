<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('report', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_enrolment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="enrolment" class="content_wrapper edit">
	<?php
	echo $this->Form->create('CensusStudent', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'enrolmentEdit')
	));
	?>
	<h1>
		<span><?php echo __('Students'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'enrolment', $selectedYear), array('class' => 'divider')); ?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
		
	<?php echo $this->element('census_legend'); ?>
	</div>
	
	<?php foreach($data as $key => $obj) { ?>
	<fieldset class="section_group table_scrollable scroll_active report" url="Census/enrolmentAjax/<?php echo $selectedYear; ?>" programme_id="<?php echo $obj['education_programme_id'];?>" admission_age="<?php echo $obj['admission_age'];?>">
		<legend>
			<span><?php echo $obj['name']; ?></span>
		</legend>
		
		<div class="row" style="margin-bottom: 15px;">
			<div class="label category"><?php echo __('Category'); ?></div>
			<div class="value category">
				<?php
				echo $this->Form->input('student_category_id', array(
					'id' => 'StudentCategoryId',
					'options' => $category,
					'onchange' => 'CensusEnrolment.get(this)',
					'autocomplete' => 'off'
				));
				?>
			</div>
		</div>
                <?php 
                    $gradesCount = count($obj['grades']);
                    
                ?>
                <div class="list_wrapper ajaxContentHolder">
                    <table class="table">
                        <tbody>
                            <tr class="th_bg">
                                <td rowspan="2"><?php echo __('Age'); ?></td>
                                <td rowspan="2"><?php echo __('Gender'); ?></td>
                                <td colspan="<?php echo $gradesCount; ?>"><?php echo __('Grades'); ?></td>
                                <td colspan="2"><?php echo __('Totals'); ?></td>
                                <td rowspan="2" class="cell_delete"></td>
                            </tr>
                            <tr class="th_bg">
                                <?php foreach($obj['grades'] AS $gradeName){?>
                                    <td><?php echo $gradeName; ?></td>
                                <? } ?>
                                <td></td>
                                <td><?php echo __('Both'); ?></td>
                            </tr>
                            
                            <?php foreach($obj['dataRowsArr'] AS $row){?>
                                <?php if($row['type'] == 'input'){?>
                                    <tr age="<?php echo $row['age'] ?>" gender="<?php echo $row['gender'] == 'M' ? 'male' : 'female'; ?>" type="input">
                                <?php }else{?>
                                    <tr>
                                <?}?>
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
                                                    <?}?>
                                                </td>
                                            <?php }?>
                                        <?}else if($dataKey == 'firstColumn' || $dataKey == 'lastColumn'){?>
                                            <td rowspan="2"><?php echo $dataValue; ?></td>
                                        <? } else if ($dataKey == 'age') { ?>
                                            <?php if(isset($row['ageEditable']) && $row['ageEditable'] == 'yes'){?>
                                                <td rowspan="2">
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
                                        <?}else if($dataKey == 'colspan2'){?>
                                            <td colspan="2"><?php echo $dataValue; ?></td>
                                        <?}else if($dataKey == 'firstHalf'){?>
                                            <td colspan="<?php echo $row['colspan']; ?>" class="rowTotalLeftCol"><?php echo $dataValue; ?></td>
                                        <?}else if($dataKey == 'totalAllGrades'){?>
                                            <td colspan="2" class="rowTotalRightCol"><?php echo $dataValue; ?></td>
                                        <?}else if($dataKey == 'totalByAgeMale' || $dataKey == 'totalByAgeFemale'){?>
                                            <td class="<?php echo $dataKey; ?>"><?php echo $dataValue; ?></td>
                                        <?}else if($dataKey == 'totalByAgeAllGender'){?>
                                            <td rowspan="2" class="<?php echo $dataKey; ?>"><?php echo $dataValue; ?></td>
                                        <?php }else{?>
                                            <td><?php echo $dataValue; ?></td>
                                        <?}?>
                                    <?}?>
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
                                    <?}?>
                                </tr>
                            <?}?>
                        </tbody>
                    </table>
                </div>
                <?php if($_add) { ?>
                    <div class="row"><a class="void icon_plus" url="Census/enrolmentAddRow"><?php echo __('Add').' '.__('Age'); ?></a></div>
		<?php } ?>
	</fieldset>
	<?php } // end foreach (enrolment) ?>
	
	<?php if(!empty($data)) { ?>
	<div class="controls">
		<input type="button" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="CensusEnrolment.save()" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'enrolment', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php } ?>
	
	<?php echo $this->Form->end(); ?>
</div>