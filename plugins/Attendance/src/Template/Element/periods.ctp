<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') {?>
    <div class="toolbar-responsive">
        <div class="toolbar-wrapper">
            <table class="table table-curved">
            <thead>
                <th><?= __('Default Period Name') ?></th>
                <th><?= __('Assigned Name') ?></th>                
            </thead>            
                <tbody>
                    <?php 
                    $j = 1;
                    for ($i=0; $i<$attendance_per_day; $i++) {  ?>
                        <tr>
                            <td>Period <?= $j;?></td>
                            <td><?php echo $this->Form->input('period['.$j.']',
                             [ 
                                'value' => (!empty($StudentAttendancePerDayPeriodsData[$i]['name'])) ? $StudentAttendancePerDayPeriodsData[$i]['name'] : "Period ".$j

                            ]); 
                            echo $this->Form->hidden('p'.$j,
                             [
                                'value' => (!empty($StudentAttendancePerDayPeriodsData[$i]['id'])) ? $StudentAttendancePerDayPeriodsData[$i]['id'] : ""

                            ]); ?></td>                            
                        </tr>
                    <?php $j++; } ?>
                </tbody>
        </table>
        </div>
    </div>
<?php } else if ($ControllerAction['action'] == 'view') {?>
    <div class="toolbar-responsive">
        <div class="toolbar-wrapper">
            <table class="table table-curved">
            <thead>
                <th><?= __('Default Period Name') ?></th>
                <th><?= __('Assigned Name') ?></th>                
            </thead>            
                <tbody>
                    <?php 
                    $j = 1;
                    for ($i=0; $i<$attendance_per_day; $i++) {  ?>
                        <tr>
                            <td>Period <?= $j;?></td>
                            <td><?php echo (!empty($StudentAttendancePerDayPeriodsData[$i]['name'])) ? $StudentAttendancePerDayPeriodsData[$i]['name'] : "Period ".$j ?></td> 
                        </tr>
                    <?php $j++; } ?>
                </tbody>
        </table>
        </div>
    </div>
<?php }?>

