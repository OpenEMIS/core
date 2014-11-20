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
</div>

<table class="table table-striped table-hover table-bordered" total="<?php echo $this->Paginator->counter('{:count}'); ?>">
    <thead url="Staff/index">
		<tr>
        <th class="cell_code">
                <span class="left"><?php echo __('OpenEMIS ID'); ?></span>
                <span class="icon_sort_<?php echo ($sortedcol =='Staff.identification_no')?$sorteddir:'up'; ?>"  order="Staff.identification_no"></span>
        </th>
        <th class="cell_code">
            <span class="left"><?php echo __('Name'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='Staff.first_name')?$sorteddir:'up'; ?>" order="Staff.first_name"></span>
        </th>
        <?php /*<th class="table_cell cell_code">
            <span class="left"><?php echo __('Middle Name'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='Staff.middle_name')?$sorteddir:'up'; ?>" order="Staff.middle_name"></span>
        </th>
        <th class="table_cell cell_code">
            <span class="left"><?php echo __('Last Name'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='Staff.last_name')?$sorteddir:'up'; ?>" order="Staff.last_name"></span>
        </th>*/ ?>
        <th class="cell_code">
            <span class="left"><?php echo __('Gender'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='Staff.gender')?$sorteddir:'up'; ?>" order="Staff.gender"></span>
        </th>
        <th class="cell_code">
            <span class="left"><?php echo __('Date of Birth'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='Staff.date_of_birth')?$sorteddir:'up'; ?>" order="Staff.date_of_birth"></span>
        </th>
</tr> 
    </thead>

    <tbody>
    <?php
    //pr($staff);
    if(isset($staff) && count($staff) > 0){
        $ctr = 1;
        foreach ($staff as $arrItems):
            $id = $arrItems['Staff']['id'];
            $identificationNo = $this->Utility->highlight($searchField, $arrItems['Staff']['identification_no']);
            $firstName = $this->Utility->highlight($searchField, $arrItems['Staff']['first_name'].((isset($arrItems['Staff']['history_first_name']))?'<br>'.$arrItems['Staff']['history_first_name']:''));
            $middleName = $this->Utility->highlight($searchField, $arrItems['Staff']['middle_name'].((isset($arrItems['Staff']['history_middle_name']))?'<br>'.$arrItems['Staff']['history_middle_name']:''));
            $lastName = $this->Utility->highlight($searchField, $arrItems['Staff']['last_name'].((isset($arrItems['Staff']['history_last_name']))?'<br>'.$arrItems['Staff']['history_last_name']:''));
            $gender = $arrItems['Staff']['gender'];
            $birthday = $arrItems['Staff']['date_of_birth'];
    ?>
            <tr class="table_row_selection <?php echo ((($ctr++%2) != 0)?'odd':'even');?>">
                <td><?php echo $identificationNo; ?></td>
                <td><?php echo $this->Html->link($firstName. ' '.$lastName, array('action' => 'view', $id), array('escape' => false)); ?></td>
               <?php /* <td class="table_cell"><?php echo $middleName; ?></td>
                <td class="table_cell"><?php echo $lastName; ?></td> */?>
                <td><?php echo $gender; ?></td>
                <td><?php echo $this->Utility->formatDate($birthday); ?></td>
            </tr>
        <?php endforeach;
    }
    ?>
    </tbody>
</table>

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
