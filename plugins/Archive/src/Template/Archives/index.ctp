<?php 
echo $this->Html->css('OpenEmis.../plugins/progressbar/css/bootstrap-progressbar-3.3.0.min', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/progressbar/bootstrap-progressbar.min', ['block' => true]);
echo $this->Html->script('Report.report.list', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel'); ?>

<?php $this->start('toolbar');

    $addUrl = [
            'plugin' => 'Archive',
            'controller' => 'Archives',
            'action' => 'add'
        ];
    
    echo $this->Html->link('<i class="fa kd-add"></i>', $addUrl, ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('Add'), 'escapeTitle' => false, 'id' => 'add_url']);

    /*$url = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action'],
	    'ajaxUserAutocomplete'
    ]);
    
	echo $this->Form->input('user_search', [
		//'label' => __('Add User'),
		'type' => 'text',
		'class' => 'autocomplete',
		'autocomplete-url' => $url,
		'autocomplete-no-results' => __('No User found.'),
		'autocomplete-class' => 'error-message',
		'autocomplete-target' => 'user_id',
		'autocomplete-submit' => "$('#reload').val('addUser').click();"
	]);
	echo $this->Form->hidden('user_id', ['autocomplete-value' => 'user_id']); */?>

    <div class="search" style="margin-right: -235px;">
        <div class="input-group">
            <div class="input text"><input type="text" name="Search[searchField]" class="form-control search-input focus" data-input-name="Search[searchField]" placeholder="Search" onkeypress="if (event.keyCode == 13) jsForm.submit()" id="search-searchfield" value=""></div>		
            <span class="input-group-btn" style="margin-left: 210px;">
                <button class="btn btn-xs btn-reset" type="button" onclick="$('.search-input').val('');jsForm.submit()"><i class="fa fa-close"></i></button>
                <button class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" title="" type="button" onclick="jsForm.submit()" data-original-title="Search">
                    <i class="fa fa-search"></i>
                </button>
            </span>
        </div>
    </div>
    <?php //echo $this->Html->link('<i class="fa kd-lists"></i>', '', ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'data-container' => 'body', 'title' => __('List'), 'escapeTitle' => false]);
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
                    <?= $this->Html->link(__('Download'), ['action' => 'downloadExportDB', $archive->id]) ?>
                    
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>
