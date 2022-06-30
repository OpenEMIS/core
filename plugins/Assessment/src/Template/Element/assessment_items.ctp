<?php
    $alias = $ControllerAction['table']->alias();
    $this->Form->unlockField('Assessments.assessment_items');
    // pr($data);
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                <th><?= $this->Label->get('Assessments.classification'); ?></th>
            </thead>
            <?php if (isset($data['assessment_items'])) : ?>
                <tbody>
                    <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                        <tr>
                            <td><?= $item->education_subject->code . ' - ' . $item->education_subject->name; ?></td>
                            <td><?= $item->weight; ?></td>
                            <td><?= $item->classification; ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <div class="input required">
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                        <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                        <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                        <th><?= $this->Label->get('Assessments.classification'); ?></th>
                    </thead>
                    <?php if (isset($data['assessment_items'])) : ?>
                        
                         <?php //echo "<pre>"; print_r($data['assessment_subject']);
                        // echo "<pre>"; print_r($data['assessment_items']);die('iyjhju');?>
                        <tbody>
                            <?php foreach ($data['assessment_subject'] as $j => $itemName) : ?>
                                <?php foreach ($data['assessment_items'] as $i => $item) : ?>
                                  <?php $a = 1; ?>
                                <?php
                                    $fieldPrefix = "$alias.assessment_items.$i";
                                ?>
                                <?php if($j == $item->education_subject_id ){ 
                                    $a= $a*0;
                                    ?>
                                <tr>

                                    <td>

                                        <?php
                                        echo '<input type="checkbox" checkbox-toggle-target="' . $item->education_subject_id . '" class="no-selection-label" kd-checkbox-radio/>' . $section;
                                            //echo $item->education_subject->code . ' - ' . $item->education_subject->name;
                                        echo $itemName;
                                            echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $item->education_subject_id]);
                                            if (isset($item->id)) {
                                                echo $this->Form->hidden("$fieldPrefix.id", ['value' => $item->id]);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$fieldPrefix.weight", [
                                                'type' => 'float',
                                                'label' => false,
                                                'onblur' => "return utility.checkDecimal(this, 2);",
                                                'onkeypress' => "return utility.floatCheck(event)",
                                            ]);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$fieldPrefix.classification", [
                                                'type' => 'string',
                                                'label' => false
                                            ]);
                                        ?>
                                    </td>

                                </tr>
                            <?php } ?>

                        <?php endforeach ?>
                        <?php if($a==1){ ?>
                            <tr>

                                    <td>

                                        <?php
                                       /* echo '<input type="checkbox" checkbox-toggle-target="' . $item->education_subject_id . '" class="no-selection-label" kd-checkbox-radio/>' . $section;*/
                                            //echo $item->education_subject->code . ' - ' . $item->education_subject->name;
                                        echo $itemName;
                                            echo $this->Form->hidden("$fieldPrefix.$j", ['value' => $j]);
                                            if (isset($item->id)) {
                                                echo $this->Form->hidden("$fieldPrefix.id", ['value' => $j]);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$fieldPrefix.weight", [
                                                'type' => 'float',
                                                'label' => false,
                                                'onblur' => "return utility.checkDecimal(this, 2);",
                                                'onkeypress' => "return utility.floatCheck(event)",
                                            ]);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo $this->Form->input("$fieldPrefix.classification", [
                                                'type' => 'string',
                                                'label' => false
                                            ]);
                                        ?>
                                    </td>

                                </tr>
                            <?php } ?>

                        <? php }?>
                            <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>