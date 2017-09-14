<div class="input time<?= $required ?>">
    <?php if (!is_null($label)):?>
        <label for="<?= $id ?>"><?= $label ?></label>
    <?php endif ?>

    <div class="input-group time" id="<?= $id ?>">
        <?php
            $errorMsg = '';
            $errorMsg = $this->Form->error($name);
            $fieldName = $name;
            echo $this->Form->input($fieldName, $options);
        ?>
        <?php if (!array_key_exists('disabled', $options)) : ?>
        <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
        <?php endif; ?>
    </div>
    <?php echo $errorMsg ?>
</div>
