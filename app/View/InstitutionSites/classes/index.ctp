<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Classes'));

$this->start('contentActions');
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => $_action . 'Add', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => $_action));
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.class') ?></th>
				<th><?php echo $this->Label->get('general.section'); ?></th>
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
					<td><?php echo $this->Html->link($obj['name'], array('action' => $_action . 'View', $id), array('escape' => false)); ?></td>
					<td class="table_cell">
						<?php foreach ($obj['sections'] as $sectionId => $name) : ?>
							<div class="table_cell_row <?php echo ++$i == sizeof($obj['sections']) ? 'last' : ''; ?>"><?php echo $name; ?></div>
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
