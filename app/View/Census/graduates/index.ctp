<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Graduates'));

$this->start('contentActions');
if ($_edit && $isEditable) {
    echo $this->Html->link(__('Edit'), array('action' => 'graduatesEdit', $selectedYear), array('class' => 'divider'));
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
                        <th class="cell_programme"><?php echo __('Programme'); ?></th>
                        <th class="cell_certificate"><?php echo __('Certification'); ?></th>
                        <th><?php echo __('Male'); ?></th>
                        <th><?php echo __('Female'); ?></th>
                        <th><?php echo __('Total'); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ($val as $record) {
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if ($record['source'] == $v) {
                                $record_tag = "row_" . $k;
                            }
                        }
                        ?>
                        <tr>
                            <td class="<?php echo $record_tag; ?>"><?php echo $record['education_programme_name']; ?></td>
                            <td class="<?php echo $record_tag; ?>"><?php echo $record['education_certification_name']; ?></td>
                            <td class="cell-number <?php echo $record_tag; ?>"><?php echo is_null($record['male']) ? 0 : $record['male']; ?></td>
                            <td class="cell-number <?php echo $record_tag; ?>"><?php echo is_null($record['female']) ? 0 : $record['female']; ?></td>
                            <td class="cell-number <?php echo $record_tag; ?>"><?php echo $record['total']; ?></td>
                        </tr>
    <?php } ?>
                </tbody>
            </table>
        </fieldset>
<?php } ?>
</div>
<?php $this->end(); ?>
