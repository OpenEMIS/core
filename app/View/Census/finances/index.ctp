<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_finance', false);
echo $this->Html->script('jquery.scrollTo', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Finances'));

$this->start('contentActions');
if ($isEditable) {
    if ($_add) {
        echo $this->Html->link(__('Add'), array(), array('class' => 'divider void', 'onclick' => "CensusFinance.show('CensusFinanceAdd')"));
    }
    if ($_edit) {
        echo $this->Html->link(__('Edit'), array('action' => 'financesEdit', $selectedYear), array('class' => 'divider'));
    }
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div id="finances" class="content_wrapper">
    <?php
    echo $this->Form->create('CensusFinance', array(
        'id' => 'submitForm',
        'inputDefaults' => array('label' => false, 'div' => false),
        'url' => array('controller' => 'Census', 'action' => 'finances')
    ));
    ?>
    <?php
    //pr($data);
    foreach ($data as $nature => $arrFinanceType) {
        ?>
        <fieldset class="section_group">
            <legend><?php echo $nature; ?></legend>
            <?php foreach ($arrFinanceType as $finance => $arrCategories) { ?>
                <fieldset class="section_break">
                    <legend><?php echo $finance; ?></legend>
                    <table class="table table-striped table-hover table-bordered">
                        <thead>
                            <tr>
                                <th class="table_cell"><?php echo __('Source'); ?></th>
                                <th class="table_cell"><?php echo __('Category'); ?></th>
                                <th class="table_cell"><?php echo __('Description'); ?></th>
                                <th class="table_cell"><?php echo __('Amount'); ?> (<?php echo $this->Session->read('configItem.currency'); ?>)</th>
                            </tr></thead>
                        <tbody>
                            <?php
                            foreach ($arrCategories as $arrValues) {
                                //pr($arrCategories);
                                //echo "d2";
                                $record_tag = "";
                                switch ($arrValues['CensusFinance']['source']) {
                                    case 1:
                                        $record_tag.="row_external";
                                        break;
                                    case 2:
                                        $record_tag.="row_estimate";
                                        break;
                                }
                                ?>
                                <tr>
                                    <td class="table_cell <?php echo $record_tag; ?>"><?php echo $arrValues['FinanceSource']['name']; ?></td>
                                    <td class="table_cell <?php echo $record_tag; ?>"><?php echo $arrValues['FinanceCategory']['name']; ?></td>
                                    <td class="table_cell <?php echo $record_tag; ?>"><?php echo $arrValues['CensusFinance']['description']; ?></td>
                                    <td class="table_cell <?php echo $record_tag; ?>"><?php echo $arrValues['CensusFinance']['amount']; ?></td>
                                </tr>
                            <?php } ?>   
                        </tbody>
                    </table>
                </fieldset>
            <?php } ?>
        </fieldset>
        <?php
    }
    ?>

    <?php if ($isEditable) { ?>
        <fieldset id="CensusFinanceAdd" class="section_group" style="<?php ((count($data) > 0) ? 'visibility: hidden' : ''); ?>">
            <legend><?php echo __('Add New'); ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <td class="table_cell"><?php echo __('Nature'); ?></td>
                        <td class="table_cell"><?php echo __('Type'); ?></td>
                        <td class="table_cell"><?php echo __('Category'); ?></td>
                        <td class="table_cell"><?php echo __('Source'); ?></td>
                        <td class="table_cell"><?php echo __('Description'); ?></td>
                        <td class="table_cell"><?php echo __('Amount'); ?></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="table_cell">
                            <select id="FinanceNature" onChange="CensusFinance.changeType(this)" name="data[FinanceNature][id]" class="full_width form-control">
                                <option value="0"><?php echo __('--Select--'); ?></option>
                                <?php
                                foreach ($natures as $id => $name) {
                                    echo '<option value="' . $id . '">' . $name . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td class="table_cell">
                            <select id="FinanceType" onChange="CensusFinance.changeCategory(this)" name="data[FinanceType][id]" class="full_width form-control">
                                <option value="0"><?php echo __('--Select--'); ?></option>
                            </select>
                        </td>
                        <td class="table_cell">
                            <select id="FinanceCategory" name="data[CensusFinance][finance_category_id]" class="full_width form-control">
                                <option value="0"><?php echo __('--Select--'); ?></option>
                            </select>
                        </td>
                        <td class="table_cell">
                            <select id="FinanceSource" name="data[CensusFinance][finance_source_id]" class="full_width form-control">
                                <option value="0"><?php echo __('--Select--'); ?></option>
                                <?php
                                foreach ($sources as $id => $name) {
                                    echo '<option value="' . $id . '">' . $name . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td class="table_cell"><div class="input_wrapper"><input type="text" name="data[CensusFinance][description]"></div></td>
                        <td class="table_cell"><div class="input_wrapper"><input type="text" name="data[CensusFinance][amount]"></div></td>
                    </tr>
                </tbody>			
                <input type="hidden" name="data[CensusFinance][school_year_id]" value="<?php echo $selectedYear; ?>">
            </table>
            <div class="controls">
                <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return CensusFinance.validateAdd();" />
                <input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" onClick="CensusFinance.hide('CensusFinanceAdd')" />
            </div>
        </fieldset>
    <?php } ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>