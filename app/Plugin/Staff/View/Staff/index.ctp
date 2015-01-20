<?php
echo $this->Html->css('search', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Staff'));
$this->start('contentActions');
	echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$model = 'Staff';
?>

<?php echo $this->element('layout/search', array('model' => $model, 'placeholder' => 'OpenEMIS ID or Name')) ?>

<?php if (!empty($data)) : ?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-sortable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('identification_no', __('OpenEMIS ID')) ?></th>
				<th><?php echo $this->Paginator->sort('first_name', __('Name')) ?></th>
				<th><?php echo $this->Paginator->sort('StaffIdentity.number', __($defaultIdentity['name'])) ?></th>
				<th>School Name</th>
				<th>Status</th>
			</tr>
		</thead>
		
		<tbody>
		<?php 
			foreach ($data as $obj):
				$id = $obj[$model]['id'];
				$identificationNo = $this->Utility->highlight($search, $obj[$model]['identification_no']);
				$firstName = $this->Utility->highlight($search, $obj[$model]['first_name'].((isset($obj[$model]['history_first_name']))?'<br>'.$obj[$model]['history_first_name']:''));
				$middleName = $this->Utility->highlight($search, $obj[$model]['middle_name'].((isset($obj[$model]['history_middle_name']))?'<br>'.$obj[$model]['history_middle_name']:''));
				$thirdName = $this->Utility->highlight($search, $obj[$model]['third_name'].((isset($obj[$model]['history_third_name']))?'<br>'.$obj[$model]['history_third_name']:''));
				$lastName = $this->Utility->highlight($search, $obj[$model]['last_name'].((isset($obj[$model]['history_last_name']))?'<br>'.$obj[$model]['history_last_name']:''));
				$name = $this->Html->link($firstName.(($middleName!='')?' '.$middleName:'').(($thirdName!='')?' '.$thirdName:'').' '.$lastName, array('action' => 'view', $id), array('escape' => false));
				$identity = (isset($obj['StaffIdentity'])) ? $obj['StaffIdentity']['number'] : '';
				$schoolName = (isset($obj['InstitutionSite'])) ? $obj['InstitutionSite']['name'] : '';
				$status = (isset($obj['StaffStatus'])) ? $obj['StaffStatus']['name'] : '';
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
