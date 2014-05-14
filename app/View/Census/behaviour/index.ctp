<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour'));

$this->start('contentActions');
if ($_edit && $isEditable) {
    echo $this->Html->link(__('Edit'), array('action' => 'behaviourEdit', $selectedYear), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<div id="behaviour" class="content_wrapper">

    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th class="table_cell cell_category"><?php echo __('Category'); ?></th>
                <th class="table_cell"><?php echo __('Male'); ?></th>
                <th class="table_cell"><?php echo __('Female'); ?></th>
                <th class="table_cell"><?php echo __('Total'); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php
            $total = 0;
            foreach ($data as $record) {
                $total += $record['male'] + $record['female'];
                $record_tag = "";
                switch ($record['source']) {
                    case 1:
                        $record_tag.="row_external";
                        break;
                    case 2:
                        $record_tag.="row_estimate";
                        break;
                }
                ?>
                <tr>
                    <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['name']; ?></td>
                    <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['male']) ? 0 : $record['male']; ?></td>
                    <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo is_null($record['female']) ? 0 : $record['female']; ?></td>
                    <td class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></td>
                </tr>
                <?php
            } // end for
            ?>
        </tbody>

        <tfoot>
            <tr>
                <td class="table_cell"></td>
                <td class="table_cell"></td>
                <td class="table_cell cell_label"><?php echo __('Total'); ?></td>
                <td class="table_cell cell_value cell_number"><?php echo $total; ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php $this->end(); ?>