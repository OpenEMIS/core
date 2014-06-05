<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attendance" class="content_wrapper">
    <h1>
        <span><?php echo __('Results'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if( count($years) != 0 && count($programmeGrades) != 0){
        echo $this->Form->create(null, array('style'=>'margin-bottom:20px;'));
    ?>
        <div class="row myyear">
    		<div class="label"><?php echo __('Year'); ?></div>
    		<div class="value">
    			<?php
    			echo $this->Form->input('school_year_id', array(
    				'label' => false,
    				'div' => false,
    				'options' => $years,
    				'default' => $selectedYear,
    				'onchange' => '',
    				//'onchange' => 'jsForm.change(this)',
    				'name' => "data[year]"
    				// 'url' => $this->params['controller'] . '/' . $this->action
    			));
    			?>
    		</div>
    	</div>

        <div class="row school_days">
    		<div class="label"><?php echo __('Programme - Grade'); ?></div>
    		<div class="value">
    		    <?php
    			echo $this->Form->input('education_grade_id', array(
    				'label' => false,
    				'div' => false,
    				'options' => $programmeGrades,
    				'default' => $selectedProgrammeGrade,
    				'name' => "data[programmeGrade]"
    				//'onchange' => 'jsForm.change(this)',
    				//'url' => $this->params['controller'] . '/' . $this->action
    			));
    			echo $this->Form->hidden('programme_grade_count', array(
    			    'value' => count($programmeGrades),
    			    'name' => "data[programmeGradeCount]"
    			));
    			?>
    		</div>
    	</div>
    <?php echo $this->Form->end();
        }
    ?>

        <?php if(isset($data) && !empty($data)){
        foreach($data as $institutionKey => $institutionRow){ ?>
    	<fieldset class="section_group">
            <legend><?php echo $institutionKey; ?></legend>
            <?php foreach($institutionRow as $subjectKKey => $subjectRow){?>
            <fieldset class="custom_section_break">
                <legend><?php echo $subjectKKey; ?></legend>
            </fieldset>
    	<div class="table full_width" style="margin-top: 10px;">
    		<div class="table_head">
    			<div class="table_cell"><?php echo __('code'); ?></div>
    			<div class="table_cell"><?php echo __('Assessment'); ?></div>
                <div class="table_cell"><?php echo __('Marks'); ?></div>
                <div class="table_cell"><?php echo __('Grading'); ?></div>
    		</div>

    		<div class="table_body">
    		    <?php foreach($subjectRow as $assessmentRow){ ?>
    			<div class="table_row">
    				<div class="table_cell "><?php echo empty($assessmentRow['assessment']['code']) ? 0 : $assessmentRow['assessment']['code'] ?>
                    </div>
    				<div class="table_cell "><?php echo empty($assessmentRow['assessment']['name']) ? 0 : $assessmentRow['assessment']['name'] ?>
                    </div>
                    <div class="table_cell cell_number <?php echo (intval($assessmentRow['marks']['value']) >= intval($assessmentRow['marks']['min']))?:"red"; ?>">
                        <?php echo empty($assessmentRow['marks']['value']) ? 0 : $assessmentRow['marks']['value'] ?>
                    </div>
                    <div class="table_cell">
                        <?php echo empty($assessmentRow['grading']) ? 0 : $assessmentRow['grading']['name'] ?>
                    </div>
    			</div>
    			<?php } ?>
    		</div>
    	</div>
    	<?php }?>
		</fieldset>
    	<?php }
    	} ?>
    </div>
    <script type="text/javascript">
        $(document).ready(function(){
            $('#InstitutionSchoolYearId,#InstitutionEducationGradeId').change(function(e){
                $(this).closest('form').submit();
            })
        });
    </script>
 * 
 */ ?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
if (count($years) != 0 && count($programmeGrades) != 0) {
	echo $this->Form->create('Institution', array('style' => 'margin-bottom:20px;'));
	?>
	<div class="row myyear">
		<div class="col-md-2"><?php echo __('Year'); ?></div>
		<div class="col-md-3">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'class' => 'form-control',
				'div' => false,
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => '',
				'name' => "data[year]"
			));
			?>
		</div>
	</div>

	<div class="row school_days">
		<div class="col-md-2"><?php echo __('Programme - Grade'); ?></div>
		<div class="col-md-6">
			<?php
			echo $this->Form->input('education_grade_id', array(
				'label' => false,
				'class' => 'form-control',
				'div' => false,
				'options' => $programmeGrades,
				'default' => $selectedProgrammeGrade,
				'name' => "data[programmeGrade]"
			));
			echo $this->Form->hidden('programme_grade_count', array(
				'value' => count($programmeGrades),
				'name' => "data[programmeGradeCount]"
			));
			?>
		</div>
	</div>
	<?php
	echo $this->Form->end();
}


if (isset($data) && !empty($data)) {
	foreach ($data as $institutionKey => $institutionRow) {
		echo '<fieldset class="section_group">';
		echo '<legend>' . $institutionKey . '</legend>';

		foreach ($institutionRow as $subjectKKey => $subjectRow) {
			echo '<fieldset class="custom_section_break"><legend>' . $subjectKKey . '</legend></fieldset>';
			$tableHeaders = array(__('Code'), __('Assessment'), __('Marks'), __('Grading'));
			$tableData = array();
			foreach ($subjectRow as $assessmentRow) {
				$row = array();
				$row[] = empty($assessmentRow['assessment']['code']) ? 0 : $assessmentRow['assessment']['code'];
				$row[] = empty($assessmentRow['assessment']['name']) ? 0 : $assessmentRow['assessment']['name'];
				$row[] = array(empty($assessmentRow['marks']['value']) ? 0 : $assessmentRow['marks']['value'], array('class' => (intval($assessmentRow['marks']['value']) >= intval($assessmentRow['marks']['min']))? : "red"));
				$row[] = empty($assessmentRow['grading']) ? 0 : $assessmentRow['grading']['name'];
				$tableData[] = $row;
			}
			echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
		}

		echo '</fieldset>';
	}
}
?>
<script type="text/javascript">
	$(document).ready(function(){
		$('#InstitutionSchoolYearId,#InstitutionEducationGradeId').change(function(e){
			$(this).closest('form').submit();
		})
	});
</script>
<?php
$this->end();
?>
