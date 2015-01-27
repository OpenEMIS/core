<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Sections'));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => 'InstitutionSiteSection', 'add', $selectedAcademicPeriod), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
echo $this->element('templates/academic_period_options', array('url' => 'InstitutionSiteSection/index'));
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.section') ?></th>
				<th><?php echo $this->Label->get('general.grade'); ?></th>
				<th><?php echo $this->Label->get('gender.m'); ?></th>
				<th><?php echo $this->Label->get('gender.f'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			foreach ($data as $id => $obj) {
				$i = 0;
				?>
				<tr>
					<td><?php echo $this->Html->link($obj['name'], array('action' => 'InstitutionSiteSection', 'view', $id), array('escape' => false)); ?></td>
					<td>
						<?php foreach ($obj['grades'] as $gradeId => $name) : ?>
							<div class="table_cell_row <?php echo ++$i == sizeof($obj['grades']) ? 'last' : ''; ?>"><?php echo $name; ?></div>
						<?php endforeach ?>
					</td>
					<td class="cell-number"><?php echo $obj['gender']['M']; ?></td>
					<td class="cell-number"><?php echo $obj['gender']['F']; ?></td>
				</tr>
			<?php } // end for (multigrade)    ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
