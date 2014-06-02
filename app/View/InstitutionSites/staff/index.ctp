<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
echo $this->Html->script('institution_site_staff', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'staff');
$this->assign('contentHeader', __('List of Staff'));
$this->start('contentActions');
if ($_add_staff) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'staffAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->Form->create('Staff', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'staff'),
	'inputDefaults' => array('div' => false)
));
?>
<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('school_year', array(
			'id' => 'SchoolYearId',
			'label' => false,
			'options' => $yearOptions,
			'class' => 'form-control',
			'empty' => __('All Years'),
			'default' => $selectedYear,
			'onchange' => 'InstitutionSiteStaff.navigate()'
		));
		?>
	</div>
</div>

<?php
$orderSort = $order === 'asc' ? 'up' : 'down';
echo $this->Form->hidden('orderBy', array('class' => 'orderBy', 'value' => $orderBy));
echo $this->Form->hidden('order', array('class' => 'order', 'value' => $order));
echo $this->Form->hidden('page', array('class' => 'page', 'value' => $page));
echo $this->Form->end();
?>
<div id="mainlist">
	<ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
		<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
		<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
	</ul>

	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th style="width:120px;">
						<span class="left"><?php echo __('OpenEMIS ID'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'Staff.identification_no') ? $orderSort : 'up'; ?>" orderBy="Staff.identification_no"></span>
					</th>
					<th style="width:120px;">
						<span class="left"><?php echo __('Name'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'Staff.first_name') ? $orderSort : 'up'; ?>" orderBy="Staff.first_name"></span>
					</th>
					<th style="width:120px;">
						<span class="left"><?php echo __('Position'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'StaffCategory.name') ? $orderSort : 'up'; ?>" orderBy="StaffCategory.name"></span>
					</th>
				</tr>
			</thead>

		<tbody>
			<?php 
				foreach ($data as $obj) { 
			?>
				<tr>
					<td><?php echo $this->Html->link($obj['Staff']['identification_no'], array('action' => 'staffView', $obj['Staff']['id']), array('escape' => false)); ?></td>
					<td><?php echo $obj['Staff']['first_name'].' '.$obj['Staff']['last_name']; ?></td>
					<td class="table_cell"><?php echo $obj['StaffPositionTitle']['name']; ?></td>
				</tr>
			<?php } ?>
		</tbody>
		</table>
	</div>

	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
</div>
<?php $this->end(); ?>
