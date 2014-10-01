<tr age="<?php echo $age; ?>" gender="male" type="input" gender_id="<?php echo $maleGenderId; ?>">
    <td rowspan="2">
        <div class="input_wrapper">
            <?php
            echo $this->Form->input('CensusStudentAge', array(
                'type' => 'text',
                'class' => '',
                'label' => false,
                'div' => false,
                'value' => $age,
                'defaultValue' => $age,
                'maxlength' => 10,
                'autocomplete' => 'off',
                'onkeypress' => 'return utility.integerCheck(event);'
            ));
            ?>
        </div>
    </td>
    <td>M</td>
    <?php 
        foreach($gradeList AS $gradeId => $gradeName){
    ?>
        <td>
            <div class="input_wrapper" census_id="0" grade_id ="<?php echo $gradeId; ?>">
                <?php 
                echo $this->Form->input('CensusStudentMale', array(
                        'type' => 'text',
                        'class' => '',
                        'label' => false,
                        'div' => false,
                        'value' => '',
                        'defaultValue' => '',
                        'maxlength' => 10,
                        'autocomplete' => 'off',
                        'onkeypress' => 'return utility.integerCheck(event);',
                        'onkeyup' => 'CensusEnrolment.computeByAgeGender(this);'
                ));
                ?>
            </div>
        </td>
    <?php 
        }
    ?>
    <td class="totalByAgeMale"></td>
    <td rowspan="2" class="totalByAgeAllGender"></td>
    <td rowspan="2" class="cell_delete">
        <span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="CensusEnrolment.removeRow(this);"></span>
    </td>
</tr>
<tr age="<?php echo $age; ?>" gender="female" type="input" gender_id="<?php echo $femaleGenderId; ?>">
    <td>F</td>
    <?php 
        foreach($gradeList AS $gradeId => $gradeName){
    ?>
        <td>
            <div class="input_wrapper" census_id="0" grade_id ="<?php echo $gradeId; ?>">
                <?php 
                echo $this->Form->input('CensusStudentFemale', array(
                        'type' => 'text',
                        'class' => '',
                        'label' => false,
                        'div' => false,
                        'value' => '',
                        'defaultValue' => '',
                        'maxlength' => 10,
                        'autocomplete' => 'off',
                        'onkeypress' => 'return utility.integerCheck(event);',
                        'onkeyup' => 'CensusEnrolment.computeByAgeGender(this);'
                ));
                ?>
            </div>
        </td>
    <?php 
        }
    ?>
    <td class="totalByAgeFemale"></td>
</tr>