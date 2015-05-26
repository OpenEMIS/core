<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>

<div class="panel">
	<div class="panel-body">
		<?= $this->element('advanced_search') ?>
		<?= $this->element('OpenEmis.alert') ?>
		<!--?= $this->element('data_overview') ?-->
		<!--?= $this->element('nav_tabs') ?-->
		<?= $this->fetch('panelBody') ?>
	</div>
</div>

<?php $this->end() ?>
