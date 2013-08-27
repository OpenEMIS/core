<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<?php
$total = 0;

if(strlen($this->Paginator->counter('{:count}')) > 0) {
	$total = $this->Paginator->counter('{:count}');
}
?>

<div id="teacher-list" class="content_wrapper search">
	<h1>
        <span><?php echo __('List of Teachers'); ?></span>
        <span class="divider"></span>
        <span class="total"><span><?php echo $total; ?></span> <?php echo __('Teachers'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

    <div class="row">
        <?php  echo $this->Form->create('Teacher', array('action' => 'search','id'=>false));  ?>
        <div class="search_wrapper">
			<?php echo $this->Form->input('SearchField', array(
				'id'=>'SearchField',
				'value'=>$searchField,
				'placeholder'=> __("Teacher Identification No, First Name or Last Name"),
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
        <div class="table allow_hover" action="Teachers/viewTeacher/">
            <div class="table_head" url="Teachers/index">
                <div class="table_cell cell_id_no">
                    <span class="left"><?php echo __('Identification No.'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Teacher.identification_no')?$sorteddir:'up'; ?>"  order="Teacher.identification_no"></span>
                </div>
                <div class="table_cell cell_name">
                    <span class="left"><?php echo __('First Name'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Teacher.first_name')?$sorteddir:'up'; ?>" order="Teacher.first_name"></span>
                </div>
                <div class="table_cell cell_name">
                    <span class="left"><?php echo __('Last Name'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Teacher.last_name')?$sorteddir:'up'; ?>" order="Teacher.last_name"></span>
                </div>
                <div class="table_cell cell_gender">
                    <span class="left"><?php echo __('Gender'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Teacher.gender')?$sorteddir:'up'; ?>" order="Teacher.gender"></span>
                </div>
                <div class="table_cell cell_birthday">
                    <span class="left"><?php echo __('Date of Birth'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Teacher.date_of_birth')?$sorteddir:'up'; ?>" order="Teacher.date_of_birth"></span>
                </div>
            </div>
            
            <div class="table_body">
			<?php
				foreach ($teachers as $arrItems):
					$id = $arrItems['Teacher']['id'];
					$identificationNo = $this->Utility->highlight($searchField, $arrItems['Teacher']['identification_no']);
					$firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Teacher']['first_name'].'</b>'.((isset($arrItems['Teacher']['history_first_name']))?'<br>'.$arrItems['Teacher']['history_first_name']:''));
					$lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Teacher']['last_name'].'</b>'.((isset($arrItems['Teacher']['history_last_name']))?'<br>'.$arrItems['Teacher']['history_last_name']:''));
					$gender = $arrItems['Teacher']['gender'];
					$birthday = $arrItems['Teacher']['date_of_birth'];
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