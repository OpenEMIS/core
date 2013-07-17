<?php
$pageOptions = array('escape'=>false,'style' => 'display:none');
$pageNumberOptions = array('modulus'=>5,'first' => 2, 'last' => 2,'tag' => 'li', 'separator'=>'','ellipsis'=>'<li><span class="ellipsis">...</span></li>');
?>
<div class="row">
    <ul id="pagination">
        <?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
        <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
        <?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
    </ul>
    <div style="clear:both"></div>
</div>
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
    //pr($teachers);
    if(isset($teachers) && count($teachers) > 0){
        $ctr = 1;
        foreach ($teachers as $arrItems):
            $id = $arrItems['Teacher']['id'];
            $identificationNo = $this->Utility->highlight($searchField, $arrItems['Teacher']['identification_no']);
            $firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Teacher']['first_name'].'</b>'.((isset($arrItems['TeacherHistory']['first_name']))?'<br>'.$arrItems['TeacherHistory']['first_name']:''));
                        $lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Teacher']['last_name'].'</b>'.((isset($arrItems['TeacherHistory']['last_name']))?'<br>'.$arrItems['TeacherHistory']['last_name']:''));
            $gender = $arrItems['Teacher']['gender'];
            $birthday = $arrItems['Teacher']['date_of_birth'];
    ?>
            <div row-id="<?php echo $id ?>" class="table_row table_row_selection <?php echo ((($ctr++%2) != 0)?'odd':'even');?>">
                <div class="table_cell"><?php echo $identificationNo; ?></div>
                <div class="table_cell"><?php echo $firstName; ?></div>
                <div class="table_cell"><?php echo $lastName; ?></div>
                <div class="table_cell"><?php echo $gender; ?></div>
                <div class="table_cell"><?php echo $this->Utility->formatDate($birthday); ?></div>
            </div>
        <?php endforeach;
    }
    ?>
    </div>
</div>

<?php if(sizeof($teachers)==0) { ?>
<div class="row center" style="color: red"><?php echo __('No Teacher found.'); ?></div>
<?php } ?>
<div class="row">
    <ul id="pagination">
        <?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
        <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
        <?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
    </ul>
</div>