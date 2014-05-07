<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Shifts'));

$this->start('contentActions');
if ($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'shiftsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>

<div id="shifts" class="content_wrapper">

    <table class="table table-striped table-hover table-bordered" action="InstitutionSites/shiftsView/">
        <thead>
			<tr>
				<td class="table_cell"><?php echo __('Year'); ?></td>
				<td class="table_cell"><?php echo __('Shift'); ?></td>
				<td class="table_cell"><?php echo __('Period'); ?></td>
				<td class="table_cell"><?php echo __('Location'); ?></td>
			</tr>
        </thead>

        <tbody>
			<?php foreach ($data as $obj): ?>
				<tr class="table_row" row-id="<?php echo $obj['InstitutionSiteShift']['id']; ?>">
					<td class="table_cell"><?php echo $obj['SchoolYear']['name']; ?></td>
					<td class="table_cell"><?php echo $this->Html->link($obj['InstitutionSiteShift']['name'], array('action' => 'shiftsView', $obj['InstitutionSiteShift']['id']), array('escape' => false)); ?></td>
					<td class="table_cell"><?php echo $obj['InstitutionSiteShift']['start_time']; ?> - <?php echo $obj['InstitutionSiteShift']['end_time']; ?></td>
					<td class="table_cell"><?php echo $obj['InstitutionSite']['name']; ?></td>
				</tr>
			<?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->end(); ?>