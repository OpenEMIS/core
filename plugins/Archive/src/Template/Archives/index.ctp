<?php 
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
//echo '<pre>'; print_r($toolbarButtons);
    foreach ($toolbarButtons as $key => $btn) {
        if (!array_key_exists('type', $btn) || $btn['type'] == 'button') {
            echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
        } else if ($btn['type'] == 'element') {
            echo $this->element($btn['element'], $btn['data'], $btn['options']);
        }
    }
$this->end(); ?>

<?php $this->start('panelBody'); ?>

<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table table-curved" id="ArchiveList" url="<?= $url ?>" data-downloadtext="<?= $downloadText ?>">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('generated_on') ?></th>
                <th scope="col"><?= $this->Paginator->sort('generated_by') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($archives as $archive): ?>
            <tr>
                <td><?= h(date('Y-m-d H:i:s', strtotime($archive->generated_on))) ?></td>
                <td><?= h($archive->generated_by) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Download'), ['action' => 'exportDB', $archive->id]) ?>
                    
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>
