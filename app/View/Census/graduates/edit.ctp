<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Graduates'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'graduates', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusGraduate', array(
    'inputDefaults' => array('label' => false, 'div' => false),
    'url' => array('controller' => 'Census', 'action' => 'graduatesEdit')
));
echo $this->element('census/year_options');
?>

<div id="graduates" class="content_wrapper edit">

    <?php
    $index = 0;
    $total = 0;
    foreach ($data as $key => $val) {
        ?>
        <fieldset class="section_group">
            <legend><?php echo $key ?></legend>

            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <td class="table_cell cell_programme"><?php echo __('Programme'); ?></td>
                        <td class="table_cell cell_certificate"><?php echo __('Certification'); ?></td>
                        <td class="table_cell"><?php echo __('Male'); ?></td>
                        <td class="table_cell"><?php echo __('Female'); ?></td>
                        <td class="table_cell"><?php echo __('Total'); ?></td>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ($val as $record) {
                        $total += $record['male'] + $record['female'];
                        $record_tag = "";
                        foreach ($source_type as $k => $v) {
                            if ($record['source'] == $v) {
                                $record_tag = "row_" . $k;
                            }
                        }
                        ?>
                        <tr>
                            <?php
                            echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
                            echo $this->Form->hidden($index . '.education_programme_id', array('value' => $record['education_programme_id']));
                            echo $this->Form->hidden($index . '.institution_site_id', array('value' => $record['institution_site_id']));
                            ?>
                            <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_programme_name']; ?></td>
                            <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['education_certification_name']; ?></td>
                            <td class="table_cell">
                                <div class="input_wrapper">
                                    <?php
                                    echo $this->Form->input($index . '.male', array(
                                        'id' => 'CensusGraduateMale',
                                        'class' => 'computeTotal ' . $record_tag,
                                        'type' => 'text',
                                        'value' => is_null($record['male']) ? 0 : $record['male'],
                                        'maxlength' => 9,
                                        'onkeypress' => 'return utility.integerCheck(event)',
                                        'onkeyup' => 'Census.computeTotal(this)'
                                    ));
                                    ?>
                                </div>
                            </td>
                            <td class="table_cell">
                                <div class="input_wrapper">
                                    <?php
                                    echo $this->Form->input($index . '.female', array(
                                        'id' => 'CensusGraduateFemale',
                                        'class' => 'computeTotal ' . $record_tag,
                                        'type' => 'text',
                                        'value' => is_null($record['female']) ? 0 : $record['female'],
                                        'maxlength' => 9,
                                        'onkeypress' => 'return utility.integerCheck(event)',
                                        'onkeyup' => 'Census.computeTotal(this)'
                                    ));
                                    ?>
                                </div>
                            </td>
                            <td class="table_cell cell_total cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></td>
                        </tr>
                        <?php
                        $index++;
                    }
                    ?>
                </tbody>

                <tfoot>
                    <tr>

                        <td class="table_cell"></td>
                        <td class="table_cell"></td>
                        <td class="table_cell"></td>
                        <td class="table_cell cell_label">Total</td>
                        <td class="table_cell cell_value cell_number"><?php echo $total; ?></td>
                    </tr>
                </tfoot>
            </table>
        </fieldset>
    <?php } ?>

    <?php if (!empty($data)) { ?>
        <div class="controls">
            <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
            <?php echo $this->Html->link(__('Cancel'), array('action' => 'graduates', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
        </div>
    <?php } ?>

    <?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
