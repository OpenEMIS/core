<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Classes'));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => $_action , 'add', $selectedAcademicPeriod, $selectedSection), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteClass/control', array());
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.class') ?></th>
				<th><?php echo $this->Label->get('general.subject'); ?></th>
				<th><?php echo $this->Label->get('general.teacher'); ?></th>
				<th><?php echo $this->Label->get('general.male_students'); ?></th>
				<th><?php echo $this->Label->get('general.female_students'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			foreach ($data as $id => $obj) {
				$i = 0;
				?>
				<tr>
					<td><?php echo $this->Html->link($obj['name'], array('action' => $_action , 'view', $id), array('escape' => false)); ?></td>
					<td><?php echo $obj['educationSubjectName']; ?></td>
					<td><?php echo $obj['staffName'];; ?></td>
					<td class="cell-number"><?php echo $obj['gender']['M']; ?></td>
					<td class="cell-number"><?php echo $obj['gender']['F']; ?></td>
				</tr>
			<?php } // end for (multigrade)    ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
