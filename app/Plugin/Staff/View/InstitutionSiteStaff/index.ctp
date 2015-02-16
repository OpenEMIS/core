<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Staff'));
$this->start('contentActions');
	echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = array('url' => array('plugin' => 'Staff', 'controller' => 'Staff', 'action' => $model), 'inputDefaults' => array('label' => false, 'div' => false));
echo $this->Form->create($model, $formOptions);
echo $this->element('layout/search', array('model' => $model, 'placeholder' => 'OpenEMIS ID or Name', 'form' => false));
?>

<div class="row form-horizontal">
	<div class="col-md-4" style="padding-left: 0">
		<?php
		echo $this->Form->input('academic_period_id', array(
			'class' => 'form-control',
			'empty' => __('All Years'),
			'options' => $yearOptions,
			'onchange' => "$(this).closest('form').submit()",
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
				<th><?php echo $this->Paginator->sort('SecurityUser.openemis_no', __('OpenEMIS ID')) ?></th>
				<th><?php echo $this->Paginator->sort('SecurityUser.first_name', __('Name')) ?></th>
				<th><?php echo $this->Paginator->sort('StaffIdentity.number', __($defaultIdentity['name'])) ?></th>
				<th><?php echo __('Position') ?></th>
				<th><?php echo __('Status') ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj['Staff']['id'];
				$openemisNo = $this->Utility->highlight($search, $obj['SecurityUser']['openemis_no']);
				$name = $this->Utility->highlight($search, $this->Model->getName($obj['SecurityUser']));
				$name = $this->Html->link($name, array('action' => 'view', $id), array('escape' => false));
				$identity = (isset($obj['StaffIdentity'])) ? $obj['StaffIdentity']['number'] : '';
		?>
			<tr>
				<td><?php echo $openemisNo; ?></td>
				<td><?php echo $name; ?></td>
				<td><?php echo $identity; ?></td>
				<td><?php echo $positionList[$obj['InstitutionSitePosition']['staff_position_title_id']]; ?></td>
				<td><?php echo $obj['StaffStatus']['name']; ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
