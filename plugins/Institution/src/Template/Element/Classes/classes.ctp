<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php $label = isset($attr['label']) ? $attr['label'] : $attr['field']; ?>
<div class="input clearfix">
    <label for="<?= $attr['id'] ?>"><?= $label ?></label>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table table-checkable table-input">
                <thead>
                    <tr>
                        <th class="checkbox-column"><input type="checkbox" class="no-selection-label" kd-checkbox-radio=""/></th>
                        <th><?= $this->Label->get('InstitutionClasses.class') ?></th>
                        <th><?= $this->Label->get('InstitutionClasses.staff_id') ?></th>
                    </tr>
                </thead>
                <?php if (isset($attr['data'])) : ?>
                    <?php
                        $elementData = $attr['data'];
                        $classesData = $elementData['classes'];
                        // pr($classesData);
                     ?>
                    <tbody>
                    <?php
                        $staffId = $data->staff_id;
                        foreach ($classesData as $key => $value) {

                            $n = intval($value->id);

                            $selected = false;
                            $disabled = (!empty($value->staff_id) && $value->staff_id != $staffId)? 'disabled': '';
                            if ($disabled) {
                                // class's homeroom is another teacher
                                $selected = 'checked';
                            } else {
                                if(!empty($this->request->data)) {
                                    if ($this->request->data['submit'] == 'save') {
                                        $selected = (isset($this->request->data['Classes'][$key]['class_id']) && !empty($this->request->data['Classes'][$key]['class_id']))? 'checked': '';
                                    } else {
                                        $selected = ($value->staff_id == $staffId)? 'checked': '';
                                    }
                                }
                            }
                    ?>
                            <tr>
                                <td <?php if(!$disabled){ ?>class="checkbox-column"<?php } ?>>
                                    <?php
                                    echo $this->Form->checkbox('Classes.' . $key . '.class_id', ['checked' => $selected, 'disabled' => $disabled, 'value' => $n, 'class' => 'no-selection-label', 'kd-checkbox-radio' => '']);
                                    ?>
                                </td>

                                <td><?=$value->name?></td>
                                <td><?php echo ($value->has('user') && !empty($value->user))? $value->user->name: '-'; ?></td>
                            </tr>

                    <?php } ?>
                </tbody>
                <?php endif ?>
            </table>
        </div>
    </div>
</div>
