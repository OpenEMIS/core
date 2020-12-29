<?php
    $alias = $ControllerAction['table']->alias();
    $fieldKey = 'meeting';
    $action = $ControllerAction['action'];


    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField($alias . '.' . $fieldKey);
    }
    // pr($data);
    // die;

    if ($ControllerAction['action'] == 'view') {
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

        // if (!$data->has('institution_shift_id') || $ControllerAction['action'] == 'edit') {
        //     $addButtonAttr['disabled'] = 'disabled';
        // }

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
                    ?>
                    <tr>
                        <td>
                        <!-- <div class="input-group date  " id="InstitutionTestCommittees_meeting_date" style="">
		                    <div class="input text">
                                <input type="text" name="InstitutionTestCommittees[meeting_date]" class="form-control" id="institutiontestcommittees-meeting-date" value="23-12-2020">
                            </div>		
                            <span class="input-group-addon">
                            <i class="glyphicon glyphicon-calendar"></i>
                            </span>
	                    </div> -->
                        <div class="input-group date" id="InstitutionTestCommittees_meeting_date" style="">
		                    <div class="input text">
                                <?php
                                echo $this->Form->input("$fieldPrefix.meeting_date", [
                                    'type' => 'text',
                                    'label' => false,
                                    'value' => date('d-m-Y')
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i>
                        </div>
                        </td>
                        <td>
                        <!-- <div class="input-group time " id="InstitutionTestCommittees_start_time">
                            <div class="input text">
                                <input type="text" name="InstitutionTestCommittees[start_time]" class="form-control" id="institutiontestcommittees-start-time" value="02:27 PM">
                            </div>        
                            <span class="input-group-addon">
                                <i class="glyphicon glyphicon-time"></i>
                            </span>
                        </div> -->
                        <div class="input-group time " id="InstitutionTestCommittees_start_time">
                            <div class="input text">
                                <?php
                                echo $this->Form->input("$fieldPrefix.start_time", [
                                    'type' => 'text',
                                    'label' => false,
                                   'value' => date("h:i A")
                                ]);
                            ?>
                            </div>
                            <span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
                        </div>
                        </td>
                        <td>
                        <!--<div class="input-group time " id="InstitutionTestCommittees_start_time">
                                <div class="input text">
                                    <input type="text" name="InstitutionTestCommittees[start_time]" class="form-control" id="institutiontestcommittees-start-time" value="02:27 PM">
                                </div>        
                                <span class="input-group-addon">
                                    <i class="glyphicon glyphicon-time"></i>
                                </span>
                        </div> -->
                        <div class="input-group time " id="InstitutionTestCommittees_end_time">
                            <div class="input text">
                            <?php
                                echo $this->Form->input("$fieldPrefix.end_time", [
                                    'type' => 'text',
                                    'label' => false,
                                     'value' => date("h:i A")
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
        <?php if (isset($data[$fieldKey])) : ?>
            <tbody>
                <?php foreach ($data[$fieldKey] as $i => $slot) : ?>
                <?php  echo '<pre>';print_r($slot);die;?>
                    <?php
                        $fieldPrefix = "$alias.$fieldKey.$i";
                        $joinDataPrefix = $fieldPrefix . '._joinData';
                    ?>
                    <tr>
                        <td>
                            <?php
                                echo $slot->meeting_date;
                            ?>
                        </td>
                        <td>
                            <?php
                                echo $slot->start_time;
                            ?>
                        </td>
                        <td>
                            <?php
                                echo $slot->end_time;
                            ?>
                        </td>
                         <td>
                            <?php
                                echo $slot->comment;
                            ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        <?php endif ?>
    </table>
</div>
<?php endif ?>
