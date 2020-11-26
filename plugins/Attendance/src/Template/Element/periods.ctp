<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') {?>
    <div class="toolbar-responsive">
        <div class="toolbar-wrapper">
            <table  id="table-reorder" class="table table-curved">
            <thead>
                <th><?= __('Default Period Name') ?></th>
                <th><?= __('Assigned Name') ?></th> 
                <th><?= __('Reorder') ?></th>                
            </thead>            
                <tbody>
                    <?php 
                    $j = 1;
                    for ($i=0; $i<$attendance_per_day; $i++) {  ?>
                        <tr>
                            <td>Period <?php echo (!empty($StudentAttendancePerDayPeriodsData[$i]['period'])) ? $StudentAttendancePerDayPeriodsData[$i]['period'] : $j ?></td>
                            <td><?php echo $this->Form->input('period['.$j.']',
                             [ 
                                'value' => (!empty($StudentAttendancePerDayPeriodsData[$i]['name'])) ? $StudentAttendancePerDayPeriodsData[$i]['name'] : "Period ".$j

                            ]); 
                            echo $this->Form->hidden('p'.$j,
                             [
                                'value' => (!empty($StudentAttendancePerDayPeriodsData[$i]['id'])) ? $StudentAttendancePerDayPeriodsData[$i]['id'] : ""

                            ]); ?></td>    
                            <td class="sorter">  
                                <div class="reorder-icon">
                                <a href="#"><i class="fa fa-arrows-alt"></i></a>
                                </div>
                            </td>                        
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
                <th><?= __('Reorder') ?></th>         
            </thead>            
                <tbody>
                    <?php 
                    $j = 1;
                    
                    for ($i=0; $i<$attendance_per_day; $i++) {  ?>
                        <tr>
                            <td>Period <?php echo (!empty($StudentAttendancePerDayPeriodsData[$i]['period'])) ? $StudentAttendancePerDayPeriodsData[$i]['period'] : $j ?></td>
                            <td><?php echo (!empty($StudentAttendancePerDayPeriodsData[$i]['name'])) ? $StudentAttendancePerDayPeriodsData[$i]['name'] : "Period ".$j ?></td> 
                            <td class="sorter">  
                                    <div class="reorder-icon">
                                    <i class="fa fa-arrows-alt"></i>
                                    </div>
                            </td>
                        </tr>
                    <?php $j++; } ?>
                </tbody>
        </table>
        </div>
    </div>
<?php }?>
<?php echo $this->Html->script('/controller_action/js/reorder.js');?>







