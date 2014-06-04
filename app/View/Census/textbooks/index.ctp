<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Textbooks'));

$this->start('contentActions');
if ($_edit && $isEditable) {
    echo $this->Html->link(__('Edit'), array('action' => 'textbooksEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div class="table-responsive">

    <?php foreach ($data as $key => $val) { ?>
        <fieldset class="section_group">
            <legend><?php echo $key ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th class="table_cell cell_grade"><?php echo __('Grade'); ?></th>
                        <th class="table_cell"><?php echo __('Subject'); ?></th>
                        <th class="table_cell"><?php echo __('Total'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $total = 0;
                    foreach ($val as $record) {
                        $total += $record['total'];
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if ($record['source'] == $v) {
                                $record_tag = "row_" . $k;
                            }
                        }
                        ?>
                        <tr>
                            <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_grade_name']; ?></td>
                            <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_subject_name']; ?></td>
                            <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['total']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>

                <tfoot>
                    <tr>

                        <td class="table_cell"></td>
                        <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
                        <td class="table_cell cell_value cell_number"><?php echo $total; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>
    <?php } ?>
</div>
<?php $this->end(); ?>