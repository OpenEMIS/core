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
                        <th></th>
                        <th><?= $this->Label->get('Assessments.educationSubject'); ?></th>
                        <th><?= $this->Label->get('Assessments.subjectWeight'); ?></th>
                        <th><?= $this->Label->get('Assessments.classification'); ?></th>
                    </thead>
                    <?php if (isset($data['assessment_items'])) : ?>
                        
                         <?php //echo "<pre>"; print_r($data['assessment_subject']);
                        // echo "<pre>"; print_r($data['assessment_items']);die('iyjhju');?>
                        <tbody>
                            <?php foreach ($data['assessment_subject'] as $j => $itemName) : ?>
                                <?php $key = array_search($j, array_column($data['assessment_items'], 'education_subject_id'));
                              //  print_r($fieldPrefix);  die;
                             // echo "<pre>"; print_r($data['assessment_items']); die;
                                ?>
                              
                                <?php 
                                    if(isset($key) && $key !==false){
                                        $fieldPrefix = "$alias.assessment_items.$key";

                                     ?>
                                <tr>
                                    <td>
                                        <?php
                                        echo $this->Form->checkbox("$fieldPrefix.$key.education_subject_check", ['checked' => $data['assessment_items'][$key]['education_subject_id'], 'class' => 'no-selection-label', 'kd-checkbox-radio' => '']);?>
                                        </td>
                                        <td> <?php echo $itemName; ?></td>
                                        <?php
                                            echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $data['assessment_items'][$key]['education_subject_id']]);
                                            echo $this->Form->hidden("$fieldPrefix.assessment_items_id", ['value' => $data['assessment_items'][0]['assessment_id']]);
                                            if (isset($data['assessment_items'][$key]['id'])) {
                                                echo $this->Form->hidden("$fieldPrefix.id", ['value' => $data['assessment_items'][$key]['id']]);
                                            }
                                        ?>
                                    
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
                            <?php } else { ?>
                                <tr>

                                    <td>

                                        <?php
                                        echo $this->Form->checkbox("$fieldPrefix.$key.education_subject_check", ['class' => 'no-selection-label', 'kd-uncheckbox-radio' => '']);?>
                                        </td>
                                        <td><?php echo $itemName; ?></td>
                                        <?php
                                            echo $this->Form->hidden("$fieldPrefix.education_subject_id", ['value' => $j]);
                                            echo $this->Form->hidden("$fieldPrefix.assessment_items_id", ['value' => $data['assessment_items'][0]['assessment_id']]);
                                            if (isset($j)) {
                                                echo $this->Form->hidden("$fieldPrefix.id", ['value' => $j]);
                                            }
                                        ?>
                                    
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

                        <?php //endforeach ?>
                            <?php endforeach ?>
                        </tbody>
                    <?php endif ?>
                </table>
            </div>
        </div>
    </div>
<?php endif ?>