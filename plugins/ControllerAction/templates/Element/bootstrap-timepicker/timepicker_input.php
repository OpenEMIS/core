<div class="input time<?= $attr['null'] == false ? ' required' : '' ?>">
    <?php if (strlen($attr['label']) > 0 ):?>
        <label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
    <?php endif ?>

    <div class="input-group time <?= isset($attr['class']) ? $attr['class'] : '' ?>" id="<?= $attr['id'] ?>">
        <?php
            $errorMsg = '';
            if (isset($attr['fieldName'])) {
                $errorMsg = $this->Form->error($attr['fieldName']);
            } else {
                $errorMsg = $this->Form->error($attr['field']);
            }
            $fieldName = (isset($attr['fieldName']))? $attr['fieldName']: $attr['model'].'.'.$attr['field'];

            echo $this->Form->input($fieldName, [
                    'type' => 'text',
                    'label' => false,
                    'class' => 'form-control',
                    'value' => isset($attr['value']) ? $attr['value'] : '',
                    'error' => false
                ]);
         ?>
        <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
    </div>
    <?php echo $errorMsg ?>
</div>
