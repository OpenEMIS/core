<div class="table_row " row-id="<?php echo $index;?>">
    <div class="table_cell cell_description" style="width:90%">
        <?php echo $this->Form->input('RubricsTemplateGrade.'.$index.'.education_grade_id', array('options' => $gradeOptions, 'label' => false, 'style' => array('width:200px'))); ?> 
    </div>
    <div class="table_cell cell_delete">
        <span class="icon_delete" onclick="rubricsTemplate.removeRubricTemplateGrade(this)" title="Delete"></span>
    </div>
</div>