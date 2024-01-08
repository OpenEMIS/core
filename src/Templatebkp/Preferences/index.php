<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', 'Preferences');

$this->start('contentBody');
$this->start('toolbar');
	echo $this->Html->link('<i class="fa fa-pencil"></i>', [], ['class' => 'btn btn-xs btn-default', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => 'Edit' , 'escape' => false]);	
$this->end();

?>

<?= $this->element('preferences_tabs') ?>


<?php $this->end() ?>

