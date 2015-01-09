<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('Translations.app.translation',false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => 'add'), array('class' => 'divider'));
	}
	if ($_execute) {
		echo $this->Html->link($this->Label->get('general.compile'), array(), array('url' => $this->params['controller'] . '/compile' ,'class' => 'divider void', 'onclick' => 'Translation.compileFile(this)'));
	}
$this->end();

$this->start('contentBody');
$model = 'Translation';

$formOptions = array('url' => array('plugin' => 'Translations', 'controller' => 'Translations'), 'inputDefaults' => array('label' => false, 'div' => false));
echo $this->Form->create($model, $formOptions);
echo $this->element('layout/search', array('form' => false));
?>

<div class="row form-horizontal">
	<div class="col-md-4" style="padding-left: 0">
		<?php
		echo $this->Form->input('language', array(
			'id' => 'language',
			'class' => 'form-control',
			'options' => $languageOptions,
			'onchange' => "$('form').submit()",
			'required' => false
		));
		?>
	</div>
</div>

<?php echo $this->Form->end() ?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('eng', __('English')) ?></th>
				<th><?php echo $languageOptions[$selectedLang]; ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj[$model]['id'];
				$name = $this->Utility->highlight($search, $obj['Translation']['eng']);
		?>
			<tr>
				<td><?php echo $this->Html->link($name, array('action' => 'view', $id), array('escape' => false)); ?></td>
				<td><?php echo $obj['Translation'][$selectedLang]; ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
