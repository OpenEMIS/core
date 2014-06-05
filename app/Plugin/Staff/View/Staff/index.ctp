<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'staff-list');
$this->assign('contentClass', 'search');
$this->assign('contentHeader', __('List of Staff'));
$this->start('contentActions');
$total = 0;
if (strlen($this->Paginator->counter('{:count}')) > 0) {
	$total = $this->Paginator->counter('{:count}');
}
?>
<span class="divider"></span>
<span class="total"><span><?php echo $total; ?></span> <?php echo __('Staff'); ?></span>
<?php
$this->end();

$this->start('contentBody');
?>
<div class="row">
	<?php echo $this->Form->create('Staff', array('action' => 'search', 'id' => false)); ?>
	<div class="search_wrapper">
		<?php
		echo $this->Form->input('SearchField', array(
			'id' => 'SearchField',
			'value' => $searchField,
			'placeholder' => __("Staff OpenEMIS ID, First Name or Last Name"),
			'class' => 'default',
			'label' => false,
			'div' => false));
		?>
		<span class="icon_clear">X</span>
	</div>
	<?php
	echo $this->Js->submit('', array(
		'id' => 'searchbutton',
		'class' => 'icon_search',
		'url' => $this->Html->url(array('action' => 'index', 'full_base' => true)),
		'before' => "maskId = $.mask({parent: '.search', text:'" . __("Searching...") . "'});",
		'success' => '$.unmask({id: maskId, callback: function() { objSearch.callback(data); }});'));
	?>
	<span class="advanced"><?php echo $this->Html->link(__('Advanced Search'), array('action' => 'advanced'), array('class' => 'link_back')); ?></span>
<?php echo $this->Form->end(); ?>
</div>

<div id="mainlist">
	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
<?php if ($total > 0) { ?>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead url="Staff/index">
					<tr>
						<th>
							<span class="left"><?php echo __('OpenEMIS ID'); ?></span>
							<span class="icon_sort_<?php echo ($sortedcol == 'Staff.identification_no') ? $sorteddir : 'up'; ?>"  order="Staff.identification_no"></span>
						</th>
						<th>
							<span class="left"><?php echo __('Name'); ?></span>
							<span class="icon_sort_<?php echo ($sortedcol == 'Staff.first_name') ? $sorteddir : 'up'; ?>" order="Staff.first_name"></span>
						</th>
						<?php /*<th>
							<span class="left"><?php echo __('Middle Name'); ?></span>
							<span class="icon_sort_<?php echo ($sortedcol == 'Staff.middle_name') ? $sorteddir : 'up'; ?>" order="Staff.middle_name"></span>
						</th>
						<th>
							<span class="left"><?php echo __('Last Name'); ?></span>
							<span class="icon_sort_<?php echo ($sortedcol == 'Staff.last_name') ? $sorteddir : 'up'; ?>" order="Staff.last_name"></span>
						</th> */ ?>
						<th>
							<span class="left"><?php echo __('Gender'); ?></span>
							<span class="icon_sort_<?php echo ($sortedcol == 'Staff.gender') ? $sorteddir : 'up'; ?>" order="Staff.gender"></span>
						</th>
						<th>
							<span class="left"><?php echo __('Date of Birth'); ?></span>
							<span class="icon_sort_<?php echo ($sortedcol == 'Staff.date_of_birth') ? $sorteddir : 'up'; ?>" order="Staff.date_of_birth"></span>
						</th>
					</tr>
				</thead>
	            <tbody>
					<?php
					foreach ($staff as $arrItems):
						$id = $arrItems['Staff']['id'];
						$identificationNo = $this->Utility->highlight($searchField, $arrItems['Staff']['identification_no']);
						$firstName = $this->Utility->highlight($searchField,  $arrItems['Staff']['first_name']  . ((isset($arrItems['Staff']['history_first_name'])) ? '<br>' . $arrItems['Staff']['history_first_name'] : ''));
						$middleName = $this->Utility->highlight($searchField, $arrItems['Staff']['middle_name'] . ((isset($arrItems['Staff']['history_middle_name'])) ? '<br>' . $arrItems['Staff']['history_middle_name'] : ''));
						$lastName = $this->Utility->highlight($searchField,  $arrItems['Staff']['last_name'] . ((isset($arrItems['Staff']['history_last_name'])) ? '<br>' . $arrItems['Staff']['history_last_name'] : ''));
						$gender = $arrItems['Staff']['gender'];
						$birthday = $arrItems['Staff']['date_of_birth'];
						?>
						<tr row-id="<?php echo $id ?>">
							<td><?php echo $identificationNo; ?></td>
							<td><?php echo $this->Html->link($firstName.' '.$lastName, array('action' => 'viewStaff', $id), array('escape' => false)); ?></td>
							<?php /*<td><?php echo $middleName; ?></td>
							<td><?php echo $lastName; ?></td> */ ?>
							<td><?php echo $gender; ?></td>
							<td><?php echo $this->Utility->formatDate($birthday); ?></td>
						</tr>
					<?php endforeach; ?>
	            </tbody>
	        </table>
		</div>
	<?php } // end if total ?>

	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
</div> <!-- mainlist end-->

<?php echo $this->Js->writeBuffer(); ?>
<?php $this->end(); ?>