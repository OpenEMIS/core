<?php
$this->extend('OpenEmis./Layout/Container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model._content_header")));

$this->start('contentBody');
$panelHeader = $this->fetch('panelHeader');
?>

<div class="panel">
	<div class="panel-body">
		<?= $this->element('OpenEmis.alert') ?>
		<!-- POCOR-7742 start -->
        <?php if($this->request->getParam('pass')[0] == 'add'){ ?>
        <style>
            .input.textarea > label[for='surveyquestions-params'] {
                display: none;
                border: none;
                background: none;
            }
        </style>
        <?php } ?>
        <!-- POCOR-7742 start -->
		<!--?= $this->element('data_overview') ?-->
		<?php
		// if (isset($indexDashboard)) {
		// 	echo $this->element($indexDashboard);
		// }
		?>
		<?= $this->element('nav_tabs') ?>
		<?= $this->fetch('panelBody') ?>
	</div>
</div>

<?php $this->end() ?>
