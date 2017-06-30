<div class="input date<?= $required ?>">
    <label for="<?= $id ?>"><?= $label ?></label>

    <div class="input-group date" id="<?= $id ?>">
        <div class="input text<?= $required ?>">
            <?= $this->Form->input($name, $options) ?>
        </div>
        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
    </div>
</div>
