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
                <span class="icon_sort_<?php echo ($sortedcol =='SecurityUser.openemis_no')?$sorteddir:'up'; ?>"  order="SecurityUser.openemis_no"></span>
        </th>
        <th class="cell_code">
            <span class="left"><?php echo __('Name'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='SecurityUser.first_name')?$sorteddir:'up'; ?>" order="SecurityUser.first_name"></span>
        </th>
        <th class="cell_code">
            <span class="left"><?php echo __('Gender'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='SecurityUser.gender')?$sorteddir:'up'; ?>" order="SecurityUser.gender"></span>
        </th>
        <th class="cell_code">
            <span class="left"><?php echo __('Date of Birth'); ?></span>
            <span class="icon_sort_<?php echo ($sortedcol =='SecurityUser.date_of_birth')?$sorteddir:'up'; ?>" order="SecurityUser.date_of_birth"></span>
        </th>
</tr> 
    </thead>

    <tbody>
    <?php
    if(isset($staff) && count($staff) > 0){
        $ctr = 1;
        foreach ($staff as $arrItems):
            $id = $arrItems['Staff']['id'];
            $identificationNo = $this->Utility->highlight($searchField, $arrItems['SecurityUser']['openemis_no']);
            $name = $this->Utility->highlight($search, $this->Model->getNameWithHistory($arrItems['Staff']));
            $name = $this->Html->link($name, array('action' => 'view', $id), array('escape' => false));
            $gender = $arrItems['Staff']['gender'];
            $birthday = $arrItems['Staff']['date_of_birth'];

    ?>
            <tr class="table_row_selection <?php echo ((($ctr++%2) != 0)?'odd':'even');?>">
                <td><?php echo $identificationNo; ?></td>
                <td><?php echo $this->Html->link($name, array('action' => 'view', $id), array('escape' => false)); ?></td>
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
