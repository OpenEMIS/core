<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('report', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

echo $this->Html->script('census_enrolment', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="enrolment" class="content_wrapper">
	<h1>
		<span><?php echo __('Students'); ?></span>
		<?php
		if($_edit && $isEditable) {
			echo $this->Html->link(__('Edit'), array('action' => 'enrolmentEdit', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'div' => false,
				'options' => $yearList,
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
		<legend><?php echo $obj['name']; ?></legend>
		
		<div class="row" style="margin-bottom: 15px;">
			<div class="label category"><?php echo __('Category'); ?></div>
			<div class="value category">
			<?php
				echo $this->Form->input('student_category_id', array(
					'id' => 'StudentCategoryId',
					'label' => false,
					'div' => false,
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
                <div class="list_wrapper ajaxContentHolder">
                    <table class="table">
                        <tbody>
                            <tr class="th_bg">
                                <td rowspan="2"><?php echo __('Age'); ?></td>
                                <td rowspan="2"><?php echo __('Gender'); ?></td>
                                <td colspan="<?php echo $gradesCount; ?>"><?php echo __('Grades'); ?></td>
                                <td colspan="2"><?php echo __('Totals'); ?></td>
                            </tr>
                            <tr class="th_bg">
                                <?php foreach($obj['grades'] AS $gradeName){?>
                                    <td><?php echo $gradeName; ?></td>
                                <?php } ?>
                                <td></td>
                                <td><?php echo __('Both'); ?></td>
                            </tr>
                            
                            <?php foreach($obj['dataRowsArr'] AS $row){?>
                                <?php if($row['type'] == 'input'){?>
                                    <tr age="<?php echo $row['age'] ?>" gender="<?php echo $row['gender'] == 'M' ? 'male' : 'female'; ?>">
                                <?php }else{?>
                                    <tr>
                                <?php }?>
                                    <?php foreach($row['data'] AS $dataKey => $dataValue){?>
                                        <?php if($dataKey == 'grades'){?>
                                            <?php foreach($dataValue AS $gradeId => $censusValue){?>
                                                <td><?php echo $censusValue['value']; ?></td>
                                            <?php }?>
                                        <?php }else if($dataKey == 'firstColumn' || $dataKey == 'lastColumn' || $dataKey == 'age'){?>
                                            <td rowspan="2"><?php echo $dataValue; ?></td>
                                        <?php }else if($dataKey == 'colspan2'){?>
                                            <td colspan="2"><?php echo $dataValue; ?></td>
                                        <?php }else{?>
                                            <td><?php echo $dataValue; ?></td>
                                        <?php }?>
                                    <?php }?>
                                </tr>
                            <?php }?>
                        </tbody>
                    </table>
                </div>
	</fieldset>
	<?php } // end foreach (data) ?>
</div>
