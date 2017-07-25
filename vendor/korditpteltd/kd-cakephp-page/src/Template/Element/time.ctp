<div class="input time<?= $required ?>">
    <?php if (!is_null($label)):?>
        <label for="<?= $id ?>"><?= $label ?></label>
    <?php endif ?>

    <div class="input-group time" id="<?= $id ?>">
        <?php
            $errorMsg = '';
            $errorMsg = $this->Form->error($name);
            $fieldName = $name;

            echo $this->Form->input($fieldName, [
                    'type' => 'text',
                    'label' => false,
                    'class' => 'form-control',
                    'value' => isset($options['value']) ? $options['value'] : '',
                    'error' => false
                ]);
         ?>
        <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
    </div>
    <?php echo $errorMsg ?>
</div>
