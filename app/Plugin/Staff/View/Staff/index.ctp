<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<?php
$total = 0;

if(strlen($this->Paginator->counter('{:count}')) > 0) {
	$total = $this->Paginator->counter('{:count}');
}
?>

<div id="staff-list" class="content_wrapper search">
	<h1>
		<span><?php echo __('List of Staff'); ?></span>
		<span class="divider"></span>
		<span class="total"><span><?php echo $total;?></span> <?php echo __('Staff'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>

    <div class="row">
        <?php  echo $this->Form->create('Staff', array('action' => 'search','id'=>false));  ?>
        <div class="search_wrapper">
            <?php echo $this->Form->input('SearchField', array(
                'id'=>'SearchField',
                'value'=>$searchField,
                'placeholder'=> __("Staff OpenEMIS ID, First Name or Last Name"),
                'class'=>'default',
                'label'=>false,
                'div'=>false)); 
            ?>
            <span class="icon_clear">X</span>
        </div>
        <?php echo $this->Js->submit('',array(
            'id'=>'searchbutton',
            'class'=>'icon_search',
            'url'=> $this->Html->url(array('action'=>'index','full_base'=>true)),
            'before'=> "maskId = $.mask({parent: '.search', text:'".__("Searching...")."'});",
            'success'=>'$.unmask({id: maskId, callback: function() { objSearch.callback(data); }});'));
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
		<?php if($total > 0) { ?>
        <div class="table allow_hover" action="Staff/viewStaff/">
            <div class="table_head" url="Staff/index">
                <div class="table_cell cell_id_no">
                    <span class="left"><?php echo __('OpenEMIS ID'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Staff.identification_no')?$sorteddir:'up'; ?>"  order="Staff.identification_no"></span>
                </div>
                <div class="table_cell cell_name">
                    <span class="left"><?php echo __('First Name'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Staff.first_name')?$sorteddir:'up'; ?>" order="Staff.first_name"></span>
                </div>
                <div class="table_cell cell_name">
                    <span class="left"><?php echo __('Last Name'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Staff.last_name')?$sorteddir:'up'; ?>" order="Staff.last_name"></span>
                </div>
                <div class="table_cell cell_gender">
                    <span class="left"><?php echo __('Gender'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Staff.gender')?$sorteddir:'up'; ?>" order="Staff.gender"></span>
                </div>
                <div class="table_cell cell_birthday">
                    <span class="left"><?php echo __('Date of Birth'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Staff.date_of_birth')?$sorteddir:'up'; ?>" order="Staff.date_of_birth"></span>
                </div>
            </div>
            
            <div class="table_body">
			<?php
				foreach ($staff as $arrItems):
					$id = $arrItems['Staff']['id'];
					$identificationNo = $this->Utility->highlight($searchField, $arrItems['Staff']['identification_no']);
					$firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Staff']['first_name'].'</b>'.((isset($arrItems['Staff']['history_first_name']))?'<br>'.$arrItems['Staff']['history_first_name']:''));
					$lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Staff']['last_name'].'</b>'.((isset($arrItems['Staff']['history_last_name']))?'<br>'.$arrItems['Staff']['history_last_name']:''));
					$gender = $arrItems['Staff']['gender'];
					$birthday = $arrItems['Staff']['date_of_birth'];

			?>
				<div class="table_row" row-id="<?php echo $id ?>">
					<div class="table_cell"><?php echo $identificationNo; ?></div>
					<div class="table_cell"><?php echo $firstName; ?></div>
					<div class="table_cell"><?php echo $lastName; ?></div>
					<div class="table_cell"><?php echo $gender; ?></div>
					<div class="table_cell"><?php echo $this->Utility->formatDate($birthday); ?></div>
				</div>
			<?php endforeach; ?>
            </div>
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
</div>
<?php echo $this->Js->writeBuffer(); ?>
