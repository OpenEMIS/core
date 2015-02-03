<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Students'));
$this->start('contentActions');
	echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$model = 'Student';
?>

<?php echo $this->element('layout/search', array('model' => $model, 'placeholder' => 'OpenEMIS ID or Name')) ?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('identification_no', __('OpenEMIS ID')) ?></th>
				<th><?php echo $this->Paginator->sort('first_name', __('Name')) ?></th>
				<th><?php echo $this->Paginator->sort('StudentIdentity.number', __($defaultIdentity['name'])) ?></th>
				<th><?php echo __('School Name') ?></th>
				<th><?php echo __('Status') ?></th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj[$model]['id'];
				$identificationNo = $this->Utility->highlight($search, $obj[$model]['identification_no']);
	            $name = $this->Utility->highlight($search, $this->Model->getNameWithHistory($obj['Student']));
	            $name = $this->Html->link($name, array('action' => 'view', $id), array('escape' => false));
				$identity = (isset($obj['StudentIdentity'])) ? $obj['StudentIdentity']['number'] : '';
				$schoolName = (isset($obj['InstitutionSite'])) ? $obj['InstitutionSite']['name'] : '';
				$status = (isset($obj['StudentStatus'])) ? $obj['StudentStatus']['name'] : '';
		?>
			<tr>
				<td><?php echo $identificationNo; ?></td>
				<td><?php echo $name; ?></td>
				<td><?php echo $identity; ?></td>
				<td><?php echo $schoolName; ?></td>
				<td><?php echo $status; ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php endif ?>
<?php echo $this->element('layout/pagination', array('displayCount' => false)) ?>
<?php $this->end(); ?>
