<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="rubrics_template" class="content_wrapper">
    <h1>
        <span><?php echo $this->Utility->ellipsis(__($subheader), 50); ?></span>
        <?php
        echo $this->Html->link(__('Back'), array('action' => 'qualityRubricView', $id), array('class' => 'divider'));
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php $params = implode('/', $this->params['pass']); ?>
    <?php
    $defaultAction = 'qualityRubricAnswerView';
    if ($_accessControl->check($this->params['controller'], 'qualityRubricAnswerExec') && $editiable) {
        $defaultAction='qualityRubricAnswerExec';
    }
    ?>
    <table class="table allow_hover full_width" action="<?php echo $this->params['controller'].'/'.$defaultAction.'/'.$params ?>/">
        <thead class="table_head">
            <tr>
                <td class="cell_id_no"><?php echo __('No.') ?></td>
                <td><?php echo __('Section Header') ?></td>
                <td class="cell_status"><?php echo __('Status') ?></td>
            </tr>
        </thead>
        <tbody class="table_body">
            <?php foreach ($data as $key => $item) { ?>
                <tr class="table_row"  row-id="<?php echo $item[$modelName]['id']; ?>">
                    <td class="table_cell"><?php echo $key + 1; ?></td>
                    <td class="table_cell"><?php echo $item[$modelName]['title']; ?></td>

                    <?php
                    if (!empty($questionStatusData[$item[$modelName]['id']])) {
                        switch ($questionStatusData[$item[$modelName]['id']]) {
                            case 'Not Started':
                                $fontColor = 'font-red';
                                break;
                            case 'Not Completed':
                                $fontColor = 'font-orange';
                                break;
                            case 'Completed':
                                $fontColor = 'font-green';
                                break;
                        }
                    } else {
                        $fontColor = '';
                    }
                    ?>
                    <td class="table_cell cell_status <?php echo $fontColor; ?>"><?php echo empty($questionStatusData[$item[$modelName]['id']]) ? '-' : $questionStatusData[$item[$modelName]['id']]; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>