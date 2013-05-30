<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="student-list" class="content_wrapper search">
    <h1>
        <span><?php echo __('List of Students'); ?></span>
        <span class="divider"></span>
        <span class="total"><?php echo $totalcount;?> <?php echo __('Students'); ?></span>
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

        <div class="table allow_hover" action="Students/viewStudent/">
            <div class="table_head">
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
                    // pr($students);
                if(isset($students) && count($students) > 0){
                    $ctr = 1;
                    foreach ($students as $arrItems):
                        $id = $arrItems['Student']['id'];
                        $identificationNo = $this->Utility->highlight($searchField, $arrItems['Student']['identification_no']);
                        $firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['first_name'].'</b>'.((isset($arrItems['StudentHistory']['first_name']))?'<br>'.$arrItems['StudentHistory']['first_name']:''));
                        $lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['last_name'].'</b>'.((isset($arrItems['StudentHistory']['last_name']))?'<br>'.$arrItems['StudentHistory']['last_name']:''));
                        $gender = $arrItems['Student']['gender'];
                        $birthday = $arrItems['Student']['date_of_birth'];

                ?>
                    <div class="table_row" row-id="<?php echo $id ?>">
                        <div class="table_cell"><?php echo $identificationNo; ?></div>
                        <div class="table_cell"><?php echo $firstName; ?></div>
                        <div class="table_cell"><?php echo $lastName; ?></div>
                        <div class="table_cell"><?php echo $gender; ?></div>
                        <div class="table_cell"><?php echo $birthday; ?></div>
                    </div>
                    <?php endforeach;
                }
                ?>
            </div>
        </div>
        <?php if(sizeof($students)==0) { ?>
        <div class="row center" style="color: red"><?php echo __('No Student found.'); ?></div>
        <?php } ?>

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
