<?php
$this->extend('OpenEmis./Layout/Panel');
?>
<?php
$this->start('panelBody');
//POCOR-9594: start - render all timetables grouped by term → shift
if (!empty($timetables)):
    $termGroups = [];
    foreach ($timetables as $t) {
        $termName = $t->schedule_term->name ?? __('Unknown Term');
        $termGroups[$termName][] = $t;
    }
    foreach ($termGroups as $termName => $termTimetables):
?>
        <h2 style="padding: 10px 0 5px;"><?= h($termName) ?></h2>
<?php
        foreach ($termTimetables as $t):
            $shiftName = $t->schedule_interval->shift->shift_option->name ?? __('Unknown Shift');
?>
        <h3 style="padding: 5px 0;"><?= h($shiftName) ?></h3>
        <?= $this->element('Timetables/_student_timetable', [
            'timetable_id'        => $t->id,
            'userId'              => $userId,
            'institutionDefaultId'=> $institutionDefaultId,
            'academicPeriodId'    => $academicPeriodId,
            'is_manual_exist'     => $is_manual_exist ?? [],
        ]) ?>
<?php
        endforeach;
    endforeach;
else:
    echo '<div class="alert alert-info">' . __('There are no published timetables.') . '</div>';
endif;
//POCOR-9594: end
$this->end();
?>