<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('List of Classes'));

$this->start('contentActions');
if ($_add_class) {
	echo $this->Html->link(__('Add'), array('action' => 'classesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => 'classes'));
echo $this->Form->create('InstitutionSite', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'classes'),
	'inputDefaults' => array('label' => false, 'div' => false)
));
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_class"><?php echo __('Class'); ?></th>
				<th><?php echo __('Grade'); ?></th>
				<th class="cell_gender"><?php echo __('Male'); ?></th>
				<th class="cell_gender"><?php echo __('Female'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			foreach ($data as $id => $obj) {
				$i = 0;
				?>
				<tr row-id="<?php echo $id; ?>">
					<td class="table_cell"><?php echo $this->Html->link($obj['name'], array('action' => 'classesView', $id), array('escape' => false)); ?></td>

					<td class="table_cell">
						<?php
						foreach ($obj['grades'] as $gradeId => $name) {
							$i++;
							?>
							<div class="table_cell_row <?php echo $i == sizeof($obj['grades']) ? 'last' : ''; ?>"><?php echo $name; ?></div>
						<?php } ?>
					</td>

					<td class="table_cell cell_number"><?php echo $obj['gender']['M']; ?></td>
					<td class="table_cell cell_number"><?php echo $obj['gender']['F']; ?></td>
				</tr>
			<?php } // end for (multigrade)    ?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
