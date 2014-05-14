<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Absence') . ' - '. __('Students'));

$this->start('contentActions');
echo $this->Html->link(__('Attendance'), array('action' => 'attendanceStudent'), array('class' => 'divider'));
echo $this->Html->link(__('Add'), array('action' => 'attendanceStudentAbsenceAdd'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

?>

<div id="classes" class="content_wrapper">

</div>
<?php $this->end(); ?>