<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Institutions'));
$this->start('contentActions');
	echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'divider'));
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.import'), array('action' => 'import'), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
$model = 'InstitutionSite';
?>

<?php echo $this->element('layout/search', array('model' => $model, 'placeholder' => 'Institution Name or Code')) ?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('code') ?></th>
				<th><?php echo $this->Paginator->sort('name') ?></th>
				<th><?php echo $this->Paginator->sort('Area.name', __('Area')) ?></th>
				<th><?php echo $this->Paginator->sort('InstitutionSiteType.name', __('Type')) ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj['InstitutionSite']['id'];
				$code = $this->Utility->highlight($search, $obj[$model]['code']);
				$name = $this->Utility->highlight($search, $obj[$model]['name'].((isset($obj['InstitutionSiteHistory']['name']))?'<br>'.$obj['InstitutionSiteHistory']['name']:''));
		?>
			<tr>
				<td><?php echo $code; ?></td>
				<td><?php echo $this->Html->link($name, array('action' => 'dashboard', $id), array('escape' => false)); ?></td>
                <td><?php echo $obj['Area']['name']; ?></td>
				<td><?php echo $obj['InstitutionSiteType']['name']; ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
