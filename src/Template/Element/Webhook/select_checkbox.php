<?php
//POCOR-9257: Checkbox element for webhook bulk-select rows
if (!empty($entity)) : ?>
    <input type="checkbox" class="webhook-row-checkbox" value="<?= h($entity->id) ?>">
<?php endif ?>
