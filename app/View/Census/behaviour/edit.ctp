<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'behaviour', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusBehaviour', array(
    'inputDefaults' => array('label' => false, 'div' => false),
    'url' => array('controller' => 'Census', 'action' => 'behaviourEdit')
));
echo $this->element('census/year_options');
?>

<div id="behaviour" class="content_wrapper edit">

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
            $index = 0;
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
                    <?php
                    echo $this->Form->hidden($index . '.id', array('value' => $record['id']));
                    echo $this->Form->hidden($index . '.student_behaviour_category_id', array('value' => $record['student_behaviour_category_id']));
                    ?>
                    <td class="table_cell <?php echo $record_tag; ?>"><?php echo $record['name']; ?></td>
                    <td class="table_cell">
                        <div class="input_wrapper">
                            <?php
                            echo $this->Form->input($index . '.male', array(
                                'type' => 'text',
                                'class' => 'computeTotal ' . $record_tag,
                                'value' => empty($record['male']) ? 0 : $record['male'],
                                'maxlength' => 10,
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
                                'type' => 'text',
                                'class' => 'computeTotal ' . $record_tag,
                                'value' => empty($record['female']) ? 0 : $record['female'],
                                'maxlength' => 10,
                                'onkeypress' => 'return utility.integerCheck(event)',
                                'onkeyup' => 'Census.computeTotal(this)'
                            ));
                            ?>
                        </div>
                    </td>
                    <td class="table_cell cell_total cell_number"><?php echo $record['male'] + $record['female']; ?></td>
                </tr>
    <?php $index++;
} ?>
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

    <div class="controls">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
<?php echo $this->Html->link(__('Cancel'), array('action' => 'behaviour', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
    </div>
<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>