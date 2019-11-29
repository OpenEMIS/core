<?php
$this->extend('OpenEmis./Layout/Panel');
?>
<?php
$this->start('panelBody');
echo $this->element('Timetables/controls');
echo $this->element('Timetables/_timetable');
$this->end();
?>