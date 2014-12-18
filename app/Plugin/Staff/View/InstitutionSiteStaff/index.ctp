<?php
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
echo $this->Html->script('institution_site_staff', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'staffs_search');
$this->assign('contentClass', 'search');
$this->assign('contentHeader', __('List of Staff'));

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => $model));
$formOptions['inputDefaults'] = array('label' => false, 'div' => false);
echo $this->Form->create($model, $formOptions);
?>
<div class="row">
	<div class="search_wrapper">
		<?php 
			echo $this->Form->input('SearchField', array(
				'id' => 'SearchField',
				'value' => $searchField,
				'class' => 'default',
				'placeholder' => __('Student OpenEMIS ID or Staff Name')
			));
		?>
		<span class="icon_clear">X</span>
	</div>
	<span class="left icon_search" onclick="$('form').submit()"></span>
	<span class="advanced"><?php echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'link_back')); ?></span>
</div>

<div class="row">
	<?php
	echo $this->Form->input('school_year', array(
		'id' => 'SchoolYearId',
		'options' => $yearOptions,
		'class' => 'search_select form-control',
		'empty' => __('All Years'),
		'default' => $selectedYear,
		'url' => $this->params['controller'] . '/' . $model . '/index',
		'onchange' => 'jsForm.change(this)'
	));
	?>
</div>

<?php
$orderSort = $order === 'asc' ? 'up' : 'down';
echo $this->Form->hidden('orderBy', array('class' => 'orderBy', 'value' => $orderBy));
echo $this->Form->hidden('order', array('class' => 'order', 'value' => $order));
echo $this->Form->hidden('page', array('class' => 'page', 'value' => $page));
echo $this->Form->end();
?>
<div id="mainlist">
	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th>
						<span class="left"><?php echo __('OpenEMIS ID'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'Staff.identification_no') ? $orderSort : 'up'; ?>" orderBy="Staff.identification_no"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Name'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'Staff.first_name') ? $orderSort : 'up'; ?>" orderBy="Staff.first_name"></span>
					</th>
					<th>
						<span class="left"><?php echo __('Position'); ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'InstitutionSitePosition.staff_position_title_id') ? $orderSort : 'up'; ?>" orderBy="InstitutionSitePosition.staff_position_title_id"></span>
					</th>
					<th>
						<span class="left"><?php echo $this->Label->get('general.status') ?></span>
						<span class="icon_sort_<?php echo ($orderBy == 'StaffStatus.name') ? $orderSort : 'up'; ?>" orderBy="StaffStatus.name"></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php 
					foreach ($data as $obj) {
						$fullName = trim($obj['Staff']['first_name'].' '.$obj['Staff']['middle_name']). ' '.$obj['Staff']['last_name'];
				?>
					<tr>
						<td><?php echo $obj['Staff']['identification_no']; ?></td>
						<td><?php echo $this->Html->link($fullName, array('plugin' => false, 'controller' => 'Staff', 'action' => 'view', $obj['Staff']['id']), array('escape' => false)) ?></td>
						<td><?php echo $positionList[$obj['InstitutionSitePosition']['staff_position_title_id']] ?></td>
						<td><?php echo $obj['StaffStatus']['name'] ?></td>
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
