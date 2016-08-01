<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php $label = isset($attr['label']) ? $attr['label'] : $attr['field']; ?>
<div class="input clearfix">
    <label for="<?= $attr['id'] ?>"><?= $label ?></label>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table table-checkable table-input">
                <thead>
                    <tr>
                        <th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
                        <th><?= $this->Label->get('InstitutionSubjects.subjects') ?></th>
                        <th><?= $this->Label->get('InstitutionSubjects.teachers') ?></th>
                    </tr>
                </thead>
                <?php if (isset($attr['data'])) : ?>
                <?php
                    $elementData = $attr['data'];
                    $subjectsData = $elementData['subjects'];
                    $staffId = $elementData['staffId'];
                 ?>
                <tbody>
                    <?php
                    $staffId = $data->staff_id;
                    foreach ($subjectsData as $key => $value) {
                    ?>
                        <tr>
                            <td class="checkbox-column">
                                <?php
                                $n = intval($value->id);

                                $selected = false;
                                if(!empty($this->request->data)) {
                                    if ($this->request->data['submit'] == 'save') {
                                        $selected = ((isset($this->request->data['Subjects'][$key]['subject_id']) && !empty($this->request->data['Subjects'][$key]['subject_id']))) ? 'checked': '';
                                    } else {
                                        $selected = ($value->has('teachers') && !empty($value->teachers) && in_array($staffId, array_keys($value->teachers)))? 'checked': '';
                                    }
                                }
                                echo $this->Form->input('Subjects.' . $key . '.subject_id', [
                                    'type'      => 'checkbox',
                                    'checked'   => $selected,
                                    'class'     => 'icheck-input',
                                    'value'     => $n,
                                    'label'     => false
                                ]);
                                ?>
                            </td>

                            <td><?=$value->name?></td>
                            <td><?php echo ($value->has('teachers') && !empty($value->teachers))? implode(', ', array_values($value->teachers)): '-'; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <?php endif ?>
            </table>
        </div>
    </div>
</div>
