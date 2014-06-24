<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Staff'));

$this->start('contentBody');
echo $this->Form->create('Staff', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'students'),
	'inputDefaults' => array('label' => false, 'div' => false)
));
?>
<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('school_year', array(
			'id' => 'SchoolYearId',
			'class' => ' form-control',
			'url' => 'InstitutionSites/behaviourStaffList',
			'onchange' => 'jsForm.change(this)',
			//	'empty' => __('All Years'),
			'options' => $yearOptions,
			'default' => $selectedYear
		));
		?>
	</div>

</div>
<?php
echo $this->Form->end();
?>
<div id="mainlist">
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th>
						<span class="left"><?php echo $this->Label->get('general.openemisId'); ?></span>

					</th>
					<th>
						<span class="left"><?php echo $this->Label->get('general.name'); ?></span>

					</th>
				</tr>
			</thead>

			<tbody>
				<?php foreach ($data as $obj) { ?>
					<?php
					$idNo = $obj['Staff']['identification_no'];
					$firstName = $obj['Staff']['first_name'];
					$middleName = $obj['Staff']['middle_name'];
					$lastName = $obj['Staff']['last_name'];
					$fullName = trim($firstName . ' ' . $middleName) . ' ' . $lastName;
					?>
					<tr>
						<td><?php echo $this->Html->link($idNo, array('action' => 'behaviourStaff', $obj['Staff']['id']), array('escape' => false)); ?></td>
						<td><?php echo trim($fullName); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
<?php $this->end(); ?>
