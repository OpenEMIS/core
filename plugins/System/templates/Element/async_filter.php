<?php
//POCOR-9719: Async Services filter dropdown.
//Renders a <select> whose options' values are full controller-action URLs.
//onchange navigates to the chosen page — no form submit, no JS framework dep.
$options  = $data['options'];
$selected = $data['selected'];
?>
<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <div class="form-group form-control-wrapper">
            <label class="control-label"><span><?= __('Filter') ?></span></label>
            <div class="input-control-wrapper">
                <div class="input-select-wrapper">
                    <select class="form-control" onchange="window.location=this.value">
                        <?php foreach ($options as $key => $opt): ?>
                            <option value="<?= h($opt['url']) ?>"<?= $key === $selected ? ' selected' : '' ?>>
                                <?= h($opt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
