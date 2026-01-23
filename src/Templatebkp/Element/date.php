<div class="input date<?= $required ?>">
    <label for="<?= $id ?>"><?= $label ?></label>

    <div class="input-group date" id="<?= $id ?>">
        <div class="input text<?= $required ?>">
            <?= $this->Form->input($name, $options) ?>
        </div>
        <?php if (!isset($options['disabled'])) : ?>
        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
        <?php endif ?>
    </div>

    <?php echo $this->Form->error($name) ?>
</div>
