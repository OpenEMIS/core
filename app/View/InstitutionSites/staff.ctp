<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper">
    <h1>
		<span><?php echo __('List of Staff'); ?></span>
		<?php
		if($_add_staff) {
			echo $this->Html->link(__('Add'), array('action' => 'staffAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('Staff', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'staff'),
		'inputDefaults' => array('label' => false, 'div' => false)
	)); 
	?>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year', array(
				'id' => 'SchoolYearId',
				'options' => $yearOptions,
				'empty' => __('All Years'),
				'default' => $selectedYear,
				'onchange' => 'InstitutionSiteStaff.navigate()'
			));
			?>
		</div>
	</div>
	
	<?php
	$orderSort = $order==='asc' ? 'up' : 'down';
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

        <div class="table full_width allow_hover" action="InstitutionSites/staffView/">
            <div class="table_head">
				<div class="table_cell cell_id_no">
					<span class="left"><?php echo __('Identification No.'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='Staff.identification_no')?$orderSort:'up'; ?>" orderBy="Staff.identification_no"></span>
                </div>
				<div class="table_cell">
					<span class="left"><?php echo __('First Name'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='Staff.first_name')?$orderSort:'up'; ?>" orderBy="Staff.first_name"></span>
                </div>
				<div class="table_cell">
					<span class="left"><?php echo __('Last Name'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='Staff.last_name')?$orderSort:'up'; ?>" orderBy="Staff.last_name"></span>
                </div>
				<div class="table_cell">
					<span class="left"><?php echo __('Position'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='StaffCategory.name')?$orderSort:'up'; ?>" orderBy="StaffCategory.name"></span>
                </div>
			</div>
            
            <div class="table_body">
				<?php foreach($data as $obj) { ?>
                <div class="table_row" row-id="<?php echo $obj['Staff']['id']; ?>">
					<div class="table_cell"><?php echo $obj['Staff']['identification_no']; ?></div>
					<div class="table_cell"><?php echo $obj['Staff']['first_name']; ?></div>
					<div class="table_cell"><?php echo $obj['Staff']['last_name']; ?></div>
					<div class="table_cell"><?php echo $obj['StaffCategory']['name']; ?></div>
				</div>
				<?php } ?>
            </div>
        </div>
		
        <div class="row">
            <ul id="pagination">
                <?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
            </ul>
        </div>
    </div> <!-- mainlist end-->
</div>
