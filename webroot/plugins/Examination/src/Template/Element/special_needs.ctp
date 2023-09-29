<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'view') : ?>
<div class="input clearfix required">
    <?php if ($ControllerAction['action'] == 'add') : ?>
        <label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
    <?php endif ?>
    <div class="input-form-wrapper">
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                        <th><?= __('Special Needs') ?></th>
                        <th><?= __('Special Needs Difficulty') ?></th>
                    </thead>
                    <tbody>
                        <?php foreach ($attr['data'] as $i => $item) : ?>
                            <tr>
                                <td><?= $item['special_need'] ?></td>
                                <td><?= $item['special_need_difficulty'] ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif ?>