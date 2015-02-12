<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Sections'));

$this->start('contentActions');
	if ($_add) {
		echo $this->Html->link($this->Label->get('general.add'), array('action' => 'InstitutionSiteSection', 'singleGradeAdd', $selectedPeriod, $selectedGradeId), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteSection/controls_index', array('url' => 'InstitutionSiteSection/index'));

?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.section') ?></th>
				<th><?php echo $this->Label->get('InstitutionSiteSection.staff_id'); ?></th>
				<th><?php echo $this->Label->get('general.male_students'); ?></th>
				<th><?php echo $this->Label->get('general.female_students'); ?></th>
				<th><?php echo $this->Label->get('general.classes') ?></th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($data as $i => $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj[$model]['name'], array('action' => 'InstitutionSiteSection', 'view', $obj[$model]['id']), array('escape' => false)); ?></td>
					<td><?php echo ModelHelper::getName($obj['Staff']); ?></td>
					<td class="cell-number"><?php echo !empty($obj[$model]['gender']['M']) ? $obj[$model]['gender']['M'] : 0; ?></td>
					<td class="cell-number"><?php echo !empty($obj[$model]['gender']['F']) ? $obj[$model]['gender']['F'] : 0; ?></td>
					<td class="cell-number">
						<?php echo $this->Html->link($obj[$model]['classes'], array('action' => 'InstitutionSiteClass', 'index', $selectedPeriod, $obj[$model]['id']), array('escape' => false)); ?>
					</td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
