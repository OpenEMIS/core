<?php
$this->extend('OpenEmis./Layout/Panel');
?>
<?php
$this->start('panelBody');
echo $this->element('Timetables/_student_timetable');
$this->end();
?>