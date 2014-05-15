<?php
$gradesCount = count($grades);
?>
<table class="table">
    <tbody>
        <tr class="th_bg">
            <td rowspan="2"><?php echo __('Age'); ?></td>
            <td rowspan="2"><?php echo __('Gender'); ?></td>
            <td colspan="<?php echo $gradesCount; ?>"><?php echo __('Grades'); ?></td>
            <td colspan="2"><?php echo __('Totals'); ?></td>
        </tr>
        <tr class="th_bg">
            <?php foreach ($grades AS $gradeName) { ?>
                <td><?php echo $gradeName; ?></td>
            <?php } ?>
            <td></td>
            <td><?php echo __('Both'); ?></td>
        </tr>

        <?php foreach ($dataRowsArr AS $row) { ?>
            <?php if ($row['type'] == 'input') { ?>
                <tr age="<?php echo $row['age'] ?>" gender="<?php echo $row['gender'] == 'M' ? 'male' : 'female'; ?>">
                <?php } else { ?>
                <tr>
                <?php } ?>
                <?php foreach ($row['data'] AS $dataKey => $dataValue) { ?>
                    <?php if ($dataKey == 'grades') { ?>
                        <?php foreach ($dataValue AS $gradeId => $censusValue) { ?>
                            <td><?php echo $censusValue['value']; ?></td>
                        <?php } ?>
                    <?php }else if($dataKey == 'firstColumn' || $dataKey == 'lastColumn' || $dataKey == 'age'){?>
                        <td rowspan="2"><?php echo $dataValue; ?></td>
                    <?php } else if ($dataKey == 'colspan2') { ?>
                        <td colspan="2"><?php echo $dataValue; ?></td>
                    <?php } else { ?>
                        <td><?php echo $dataValue; ?></td>
                    <?php } ?>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>