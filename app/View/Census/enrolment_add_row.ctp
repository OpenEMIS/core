<tr age="<?php echo $age; ?>" gender="male" type="input">
    <td rowspan="2">
        <div class="input_wrapper">
            <?php
            echo $this->Form->input('CensusStudentAge', array(
                'type' => 'text',
                'class' => '',
                'label' => false,
                'div' => false,
                'value' => $age,
                'defaultValue' => 0,
                'maxlength' => 10,
                'autocomplete' => 'off',
                'onkeypress' => 'return utility.integerCheck(event);'
            ));
            ?>
        </div>
    </td>
    <td>M</td>
    <?php 
        foreach($grades AS $gradeId => $gradeName){
    ?>
        <td>
            <div class="input_wrapper" census_id="0" grade_id ="<?php echo $gradeId; ?>">
                <?php 
                echo $this->Form->input('CensusStudentMale', array(
                        'type' => 'text',
                        'class' => '',
                        'label' => false,
                        'div' => false,
                        'value' => 0,
                        'defaultValue' => 0,
                        'maxlength' => 10,
                        'autocomplete' => 'off',
                        'onkeypress' => 'return utility.integerCheck(event);'
                ));
                ?>
            </div>
        </td>
    <?php 
        }
    ?>
    <td>-</td>
    <td rowspan="2">-</td>
    <td rowspan="2" class="cell_delete">
        <span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this);"></span>
    </td>
</tr>
<tr age="<?php echo $age; ?>" gender="female" type="input">
    <td>F</td>
    <?php 
        foreach($grades AS $gradeId => $gradeName){
    ?>
        <td>
            <div class="input_wrapper" census_id="0" grade_id ="<?php echo $gradeId; ?>">
                <?php 
                echo $this->Form->input('CensusStudentFemale', array(
                        'type' => 'text',
                        'class' => '',
                        'label' => false,
                        'div' => false,
                        'value' => 0,
                        'defaultValue' => 0,
                        'maxlength' => 10,
                        'autocomplete' => 'off',
                        'onkeypress' => 'return utility.integerCheck(event);'
                ));
                ?>
            </div>
        </td>
    <?php 
        }
    ?>
    <td>-</td>
</tr>