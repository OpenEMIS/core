<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<?php
$total = 0;

if(strlen($this->Paginator->counter('{:count}')) > 0) {
	$total = $this->Paginator->counter('{:count}');
}
?>

<div id="student-list" class="content_wrapper search">
    <h1>
        <span><?php echo __('List of Students'); ?></span>
        <span class="divider"></span>
        <span class="total"><span><?php echo $total; ?></span> <?php echo __('Students'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

    <div class="row">
        <?php  echo $this->Form->create('Student', array('action' => 'search','id'=>false));  ?>
        <div class="search_wrapper">
        	<?php echo $this->Form->input('SearchField', array(
				'id'=>'SearchField',
				'value'=>$searchField,
				'placeholder'=> __("Student Identification No, First Name or Last Name"),
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
        <div class="table allow_hover" action="Students/viewStudent/">
            <div class="table_head" url="Students/index">
                <div class="table_cell cell_id_no">
                    <span class="left"><?php echo __('Identification No.'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Student.identification_no')?$sorteddir:'up'; ?>"  order="Student.identification_no"></span>
                </div>
                <div class="table_cell cell_name">
                    <span class="left"><?php echo __('First Name'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Student.first_name')?$sorteddir:'up'; ?>" order="Student.first_name"></span>
                </div>
                <div class="table_cell cell_name">
                    <span class="left"><?php echo __('Last Name'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Student.last_name')?$sorteddir:'up'; ?>" order="Student.last_name"></span>
                </div>
                <div class="table_cell cell_gender">
                    <span class="left"><?php echo __('Gender'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Student.gender')?$sorteddir:'up'; ?>" order="Student.gender"></span>
                </div>
                <div class="table_cell cell_birthday">
                    <span class="left"><?php echo __('Date of Birth'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol =='Student.date_of_birth')?$sorteddir:'up'; ?>" order="Student.date_of_birth"></span>
                </div>
            </div>
            
            <div class="table_body">
			<?php
				foreach ($students as $arrItems):
					$id = $arrItems['Student']['id'];
					$identificationNo = $this->Utility->highlight($searchField, $arrItems['Student']['identification_no']);
					$firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['first_name'].'</b>'.((isset($arrItems['Student']['history_first_name']))?'<br>'.$arrItems['Student']['history_first_name']:''));
					$lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['last_name'].'</b>'.((isset($arrItems['Student']['history_last_name']))?'<br>'.$arrItems['Student']['history_last_name']:''));
					$gender = $arrItems['Student']['gender'];
					$birthday = $arrItems['Student']['date_of_birth'];
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
