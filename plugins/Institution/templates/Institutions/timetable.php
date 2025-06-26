<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('table2excel.js', ['block' => true])?>
<?= $this->Html->script('Schedule.angular/timetable.svc', ['block' => true]); ?>
<?= $this->Html->script('Schedule.angular/timetable.ctrl', ['block' => true]); ?>

<script>
    sessionStorage.setItem('nbn', '<?php echo $user;?>');
	sessionStorage.setItem('pbn', '<?php echo $pass;?>');
	localStorage.setItem('encoded_url', '<?php echo $encodedPart;?>');
    localStorage.setItem('institutionName', '<?php echo $institutionName;?>');
    localStorage.setItem('institution_id', '<?php echo $institutionDefaultId;?>');
    localStorage.setItem('timetable_id', '<?php echo $timetable_id;?>');
</script>
<div>
	<?= $this->element('OpenEmis.breadcrumbs') ?>
    <app-root></app-root>
    <?php
        echo $this->Html->script(BUILD_MAIN);
        echo $this->Html->script(BUILD_POLYFILLS);
        echo $this->Html->script(BUILD_RUNTIME);
        echo $this->Html->script(BUILD_SCRIPTS);
        echo $this->Html->css(STYLE_GUIDE);
    ?>
</div>
