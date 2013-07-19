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
<div class="table allow_hover" action="Staff/viewStaff/" total="<?php echo $this->Paginator->counter('{:count}'); ?>">
    <div class="table_head" url="Staff/index">

        <div class="table_cell cell_id_no">
                <span class="left"><?php echo __('Identification No.'); ?></span>
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
    //pr($staff);
    if(isset($staff) && count($staff) > 0){
        $ctr = 1;
        foreach ($staff as $arrItems):
            $id = $arrItems['Staff']['id'];
            $identificationNo = $this->Utility->highlight($searchField, $arrItems['Staff']['identification_no']);
            $firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Staff']['first_name'].'</b>'.((isset($arrItems['StaffHistory']['first_name']))?'<br>'.$arrItems['StaffHistory']['first_name']:''));
                        $lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Staff']['last_name'].'</b>'.((isset($arrItems['StaffHistory']['last_name']))?'<br>'.$arrItems['StaffHistory']['last_name']:''));
            $gender = $arrItems['Staff']['gender'];
            $birthday = $arrItems['Staff']['date_of_birth'];
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

<?php if(sizeof($staff)==0) { ?>
<div class="row center" style="color: red; margin-top: 15px;"><?php echo __('No Staff found.'); ?></div>
<?php } ?>
<div class="row">
    <ul id="pagination">
        <?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
        <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
        <?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
    </ul>
</div>
