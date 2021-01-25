<?php
$this->Html->css('ControllerAction.../plugins/datepicker/css/bootstrap-datepicker.min', ['block' => true]);
$this->Html->css('ControllerAction.../plugins/timepicker/css/bootstrap-timepicker.min.css', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/datepicker/js/bootstrap-datepicker.min', ['block' => true]);
$this->Html->script('ControllerAction.../plugins/timepicker/js/bootstrap-timepicker.min.js', ['block' => true]);  

    $alias = $ControllerAction['table']->alias();
    $fieldKey = 'meeting';
    $action = $ControllerAction['action'];


    if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') {
        $this->Form->unlockField($alias . '.' . $fieldKey);
    }

    if ($ControllerAction['action'] == 'view' || $ControllerAction['action'] == 'edit') {
            $viewRenderData = $data;
    }

?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Date of Meeting') ?></th>
                <th><?= __('Start Time') ?></th>
                <th><?= __('End Time')?></th>
                <th><?= __('Comment')?></th>
            </thead>

            <?php if ($viewRenderData->has('institution_committee_meeting') && !empty($viewRenderData->institution_committee_meeting)) : ?>
                <tbody>
                    <?php foreach ($viewRenderData->institution_committee_meeting as $i => $timeslot) : ?>
                        <tr>
                            <td><?= date('d-m-Y',strtotime($timeslot->meeting_date)) ?></td>
                            <td><?= date('h:i A',strtotime($timeslot->start_time)) ?></td>
                            <td><?= date('h:i A',strtotime($timeslot->end_time))	 ?></td>
                            <td><?= $timeslot->comment ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>

<?php

 elseif ($ControllerAction['action'] == 'add') : ?>
    <?php
        $addButtonAttr = [
            'label' => __('Action'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "$('#reload').val('addTimeslot').click();"
        ];
        echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add Meeting').'</span>', $addButtonAttr);
    ?>
<div class="table-responsive">
    <table class="table">
        <thead>
            <th><?= __('Date of Meeting') ?>
            <div class="tooltip-desc" style="display: inline-block;">
            <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="Date of Meeting." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th><?= __('Start Time') ?>
            <div class="tooltip-desc" style="display: inline-block;">
                <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="Start Time." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th><?= __('End Time') ?>
            <div class="tooltip-desc" style="display: inline-block;">
                <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="End Time." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th><?= __('Comment')?>
            <div class="tooltip-desc" style="display: inline-block;">
                <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="Comment." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th></th>
            <th class="cell-delete"></th>
        </thead>
        <?php if (isset($data[$fieldKey])) : ?>
            <tbody>
                <?php foreach ($data[$fieldKey] as $i => $slot) : ?>
                    <?php
                        $fieldPrefix = "$alias.$fieldKey.$i";
                        $joinDataPrefix = $fieldPrefix . '._joinData';
                        $meetingDateId = $alias.$i.'_meeting_date';
                        $meetingStartTimeId = $alias.$i.'_start_time';
                        $meetingEndTimeId = $alias.$i.'_end_time';
                    ?>
                    <tr>
                        <td>
                        <div class="input-group date" id="<?= $meetingDateId;?>" style="">
		                    <div class="input text">
                                <?php
                                echo $this->Form->input("$fieldPrefix.meeting_date", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                    //'value' => date('d-m-Y')
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>
                        </div>
                        </td>
                        <td>
                        <div class="input-group time" id="<?= $meetingStartTimeId;?>">
                            <div class="input text">
                                <?php
                                echo $this->Form->input("$fieldPrefix.start_time", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                   //'value' => date("h:i A")
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                        <div class="input-group time " id="<?= $meetingEndTimeId;?>">
                            <div class="input text">
                            <?php
                                echo $this->Form->input("$fieldPrefix.end_time", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                     //'value' => date("h:i A")
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                            <?php
                                echo $this->Form->input("$fieldPrefix.comment", [
                                    'type' => 'text',
                                    'label' => false
                                ]);
                            ?>
                        </td>
                        <td>
                            <?php
                                if ($i == (count($data[$fieldKey]) - 1)) {
                                    echo '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>' . __('Delete') .'</span></button>';
                                }
                            ?>
                        </td>
                    </tr>
                    <script>
                        $(function () {
                            var datepicker<?= $i;?> = $('#<?= $meetingDateId;?>').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true,"defaultDate": new Date()});
                            $(document).on('DOMMouseScroll mousewheel scroll', function() {
                                window.clearTimeout(t);
                                t = window.setTimeout(function() {
                                    datepicker<?= $i;?>.datepicker('place');

                                });
                            });
                            var <?=$meetingStartTimeId;?> = $('#<?=$meetingStartTimeId;?>').timepicker({defaultTime: false});
                            var <?=$meetingEndTimeId;?> = $('#<?=$meetingEndTimeId;?>').timepicker({defaultTime: false });
                            $(document).on('DOMMouseScroll mousewheel scroll', function() {
                                window.clearTimeout(t);
                                t = window.setTimeout(function() {
                                    <?=$meetingStartTimeId;?>.timepicker('place');
                                    <?=$meetingEndTimeId;?>.timepicker('place');

                                });
                            });
                        });

                    </script>
                <?php endforeach ?>
            </tbody>
        <?php endif ?>
    </table>
</div>
<?php 

elseif ($ControllerAction['action'] == 'edit') : ?>
 <?php
        $addButtonAttr = [
            'label' => __('Action'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "$('#reload').val('addTimeslot').click();"
        ];
        echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add Meeting').'</span>', $addButtonAttr);
    ?>
<div class="table-responsive">
    <table class="table">
        <thead>
            <th><?= __('Date of Meeting') ?>
            <div class="tooltip-desc" style="display: inline-block;">
            <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="Date of Meeting." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th><?= __('Start Time') ?>
            <div class="tooltip-desc" style="display: inline-block;">
                <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="Start Time." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th><?= __('End Time') ?>
            <div class="tooltip-desc" style="display: inline-block;">
                <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="End Time." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th><?= __('Comment')?>
            <div class="tooltip-desc" style="display: inline-block;">
                <i class="fa fa-info-circle fa-lg table-tooltip icon-blue" tooltip-placement="top" uib-tooltip="Comment." tooltip-append-to-body="true" tooltip-class="tooltip-blue" data-original-title="" title=""></i>
            </div></th>
            <th></th>
            <th class="cell-delete"></th>
        </thead>
            <?php if ($viewRenderData->has('institution_committee_meeting') && !empty($viewRenderData->institution_committee_meeting)) : ?>
                <tbody>
                    <?php foreach ($viewRenderData->institution_committee_meeting as $i => $timeslot) : ?>
                    <tr>
                        <td>
                        <div class="input-group date" id="InstitutionTestCommittees_meeting_date_edit" style="">
		                    <div class="input text">
                                <?php
                                echo $this->Form->input("meeting_date", [
                                    'type' => 'text',
                                    'label' => false,
                                    'value' => $timeslot->meeting_date,
                                    'disabled' =>'disabled'
                                ]);
                                ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>
                        </div>
                        </td>
                        <td>
                        <div class="input-group time " id="InstitutionTestCommittees_start_time_edit">
                            <div class="input text">
                                <?php
                                echo $this->Form->input("start_time", [
                                    'type' => 'text',
                                    'label' => false,
                                    'value' => date('h:i A',strtotime($timeslot->start_time)),
                                    'disabled' =>'disabled'
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                        <div class="input-group time " id="InstitutionTestCommittees_end_time_edit">
                            <div class="input text">
                            <?php
                                echo $this->Form->input("end_time", [
                                    'type' => 'text',
                                    'label' => false,
                                    'value' => date('h:i A',strtotime($timeslot->end_time)),
                                    'disabled' =>'disabled'
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                            <?php
                                echo $this->Form->input("comment", [
                                    'type' => 'text',
                                    'label' => false,
                                    'value' => $timeslot->comment,
                                    'disabled' =>'disabled'
                                ]);
                            ?>
                        </td>
                        <td>
                        <button  onclick="deleteMeetingOnClick(<?php echo $timeslot->id;?>)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action">
                            <i class="fa fa-trash"></i>&nbsp;<span>Delete</span>
                        </button>
                        </td>
                        
                    </tr>
                    
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        <?php if (isset($data[$fieldKey])) : ?>
            <tbody>
                <?php foreach ($data[$fieldKey] as $i => $slot) : ?>
                    <?php
                        $fieldPrefix = "$alias.$fieldKey.$i";
                        $joinDataPrefix = $fieldPrefix . '._joinData';
                        $meetingDateEditId = $alias.$i.'_meeting_date_edit';
                        $meetingStartTimeEditId = $alias.$i.'_start_time_edit';
                        $meetingEndTimeEditId = $alias.$i.'_end_time_edit';
                    ?>
                    <tr id= 'tr1'>
                        <td>
                        <div class="input-group date" id="<?= $meetingDateEditId ;?>" style="">
		                    <div class="input text">
                                <?php
                                echo $this->Form->input("$fieldPrefix.meeting_date", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                    //'value' => date('d-m-Y')
                                ]);
                                ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>
                        </div>
                        </td>
                        <td>
                        <div class="input-group time " id="<?= $meetingStartTimeEditId ;?>">
                            <div class="input text">
                                <?php
                                echo $this->Form->input("$fieldPrefix.start_time", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                    //'value' => date("h:i A")
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                        <div class="input-group time " id="<?= $meetingEndTimeEditId ;?>">
                            <div class="input text">
                            <?php
                                echo $this->Form->input("$fieldPrefix.end_time", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                     //'value' => date("h:i A")
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                            <?php
                                echo $this->Form->input("$fieldPrefix.comment", [
                                    'type' => 'text',
                                    'label' => false,
                                    'autocomplete' =>'off'
                                ]);
                            ?>
                        </td>
                        <td>
                        <?php
                            if ($i == (count($data[$fieldKey]) - 1)) {
                                echo '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>' . __('Delete') .'</span></button>';
                            }
                        ?>
                        </td>
                    </tr>
                    <script>
                        $(function () {
                            var datepicker<?= $i;?> = $('#<?= $meetingDateEditId;?>').datepicker({"format":"dd-mm-yyyy","todayBtn":"linked","orientation":"auto","autoclose":true,"defaultDate": new Date()});
                            $(document).on('DOMMouseScroll mousewheel scroll', function() {
                                window.clearTimeout(t);
                                t = window.setTimeout(function() {
                                    datepicker<?= $i;?>.datepicker('place');

                                });
                            });
                            var <?=$meetingStartTimeEditId;?> = $('#<?=$meetingStartTimeEditId;?>').timepicker({defaultTime: false});
                            var <?=$meetingEndTimeEditId;?> = $('#<?=$meetingEndTimeEditId;?>').timepicker({defaultTime: false});
                            $(document).on('DOMMouseScroll mousewheel scroll', function() {
                                window.clearTimeout(t);
                                t = window.setTimeout(function() {
                                    <?=$meetingStartTimeEditId;?>.timepicker('place');
                                    <?=$meetingEndTimeEditId;?>.timepicker('place');

                                });
                            });
                        });
                    </script>
                <?php endforeach ?>
            </tbody>
        <?php endif ?>
    </table>
</div>
<?php endif ?>
<script>
    function deleteMeetingOnClick(meetingId){
        var targeturl = '<?= \Cake\Routing\Router::url(["controller"=>"Institutions","action"=>"deleteCommiteeMeetingById"]); ?>';
        var request = $.ajax({
                url: targeturl,
                type: "GET",
                data: {meetingId : meetingId}, 
                cache: false,
                success: function(data){
                    window.location.reload();
                }
            });
            request.fail(function(jqXHR, textStatus) {
                alert( "Request failed: " + textStatus );
            });
    }  
</script>
