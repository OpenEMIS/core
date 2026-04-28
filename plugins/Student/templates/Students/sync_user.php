<?php
//POCOR-9590: Review page — shows diff between current and external identity data before applying
$fieldLabels = [
    'first_name'    => __('First Name'),
    'middle_name'   => __('Middle Name'),
    'third_name'    => __('Third Name'),
    'last_name'     => __('Last Name'),
    'gender'        => __('Gender'),
    'date_of_birth' => __('Date of Birth'),
];
?>
<div class="main-body-inner">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <?= __('Review External Identity Sync') ?>
                        &mdash; <?= h($user->name) ?>
                    </h3>
                </div>
                <div class="box-body">

                    <?php if (empty($diff)): ?>
                        <div class="alert alert-success">
                            <?= __('The external record matches the current data. No changes needed.') ?>
                        </div>
                        <a href="javascript:history.back()" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> <?= __('Back') ?>
                        </a>
                    <?php else: ?>
                        <p class="text-muted">
                            <?= __('The following fields differ between the current record and the external identity source. Review and click Apply to update.') ?>
                        </p>

                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?= __('Field') ?></th>
                                    <th><?= __('Current Value') ?></th>
                                    <th style="color:#c0392b;"><?= __('External Value') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($diff as $field => $values): ?>
                                <tr>
                                    <td><strong><?= h($fieldLabels[$field] ?? $field) ?></strong></td>
                                    <td><?= h($values['current']) ?></td>
                                    <td style="color:#c0392b; font-weight:bold;">
                                        <?= h($values['external']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?= $this->Form->create(null, [
                            'url' => ['action' => 'SyncUser', $encodedParams],
                            'method' => 'post',
                        ]) ?>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-refresh"></i> <?= __('Apply Sync') ?>
                                </button>
                                <a href="javascript:history.back()" class="btn btn-default" style="margin-left:8px;">
                                    <i class="fa fa-times"></i> <?= __('Cancel') ?>
                                </a>
                            </div>
                        <?= $this->Form->end() ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
