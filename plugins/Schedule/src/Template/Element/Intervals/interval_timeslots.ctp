<?php
    $alias = $ControllerAction['table']->alias();
    $fieldKey = 'timeslots';
    $action = $ControllerAction['action'];

    if ($ControllerAction['action'] == 'add') {
        $this->Form->unlockField($alias . '.' . $fieldKey);
    }
    // pr($data);
    // die;

    if ($ControllerAction['action'] == 'view') {
        if ($data->has('schedule_interval')) {
            $viewRenderData = $data->schedule_interval;
        } else {
            $viewRenderData = $data;
        }
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Start Time') ?></th>
                <th><?= __('End Time') ?></th>
                <th><?= __('Interval') . ' (' . __('mins') . ')' ?></th>
            </thead>

            <?php if ($viewRenderData->has('timeslots') && !empty($viewRenderData->timeslots)) : ?>
                <tbody>
                    <?php foreach ($viewRenderData->timeslots as $i => $timeslot) : ?>
                        <tr>
                            <td><?= $timeslot->start_time ?></td>
                            <td><?= $timeslot->end_time ?></td>
                            <td><?= $timeslot->interval ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endif ?>
        </table>
    </div>

<?php elseif ($ControllerAction['action'] == 'add') : ?>
    <?php
        $addButtonAttr = [
            'label' => __('Add Interval'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "$('#reload').val('addTimeslot').click();"
        ];

        if (!$data->has('institution_shift_id') || $ControllerAction['action'] == 'edit') {
            $addButtonAttr['disabled'] = 'disabled';
        }

        echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add').'</span>', $addButtonAttr);
    ?>
    <div class="input clearfix required">
        <label><?= __($attr['label']) ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <th><?= __('Start Time') ?></th>
                            <th><?= __('End Time') ?></th>
                            <th><?= __('Interval') . ' (' . __('mins') . ')' ?></th>
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
                                                echo $this->Form->input("$fieldPrefix.interval", [
                                                    'type' => 'number',
                                                    'label' => false,
                                                    'onkeyup' => "changeIntervalInput(this)"
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
            </div>
        </div>
    </div>
<script>
    ;(function($){
    $.fn.extend({
        donetyping: function(callback,timeout){
            timeout = timeout || 1e3; // 1 second default timeout
            var timeoutReference,
                doneTyping = function(el){
                    if (!timeoutReference) return;
                    timeoutReference = null;
                    callback.call(el);
                };
            return this.each(function(i,el){
                var $el = $(el);
                // Chrome Fix (Use keyup over keypress to detect backspace)
                // thank you @palerdot
                $el.is(':input') && $el.on('keyup keypress paste',function(e){
                    // This catches the backspace button in chrome, but also prevents
                    // the event from triggering too preemptively. Without this line,
                    // using tab/shift+tab will make the focused element fire the callback.
                    if (e.type=='keyup' && e.keyCode!=8) return;
                    
                    // Check if timeout has been set. If it has, "reset" the clock and
                    // start over again.
                    if (timeoutReference) clearTimeout(timeoutReference);
                    timeoutReference = setTimeout(function(){
                        // if we made it here, our timeout has elapsed. Fire the
                        // callback
                        doneTyping(el);
                    }, timeout);
                }).on('blur',function(){
                    // If we can, fire the event since we're leaving the field
                    doneTyping(el);
                });
            });
        }
    });
})(jQuery);

function changeIntervalInput(interval){
   $(interval).donetyping(function(){
     $('#reload').val('changeInterval').click();
  });
}
    
</script>
<?php elseif ($ControllerAction['action'] == 'edit') : ?>
    <div class="input clearfix required">
        <label><?= __($attr['label']) ?></label>
        <div class="input-form-wrapper">
            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <th><?= __('Start Time') ?></th>
                            <th><?= __('End Time') ?></th>
                            <th><?= __('Interval') . ' (' . __('mins') . ')' ?></th>
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
                                                echo $slot->interval;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        <?php endif ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
