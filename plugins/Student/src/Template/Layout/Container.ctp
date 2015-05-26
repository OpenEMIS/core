<div id="page-content-wrapper">
	<?php
		$toolbar = $this->fetch('toolbar');
	?>

	<div class="content-wrapper">

		<?= $this->element('ControllerAction.breadcrumbs') ?>

		<div class="page-header">
			<h2><?= $this->fetch('contentHeader') ?></h2>
			<?php if (!empty($toolbar)) : ?>
				<div class="toolbar">
					<?= $this->fetch('toolbar') ?>
				</div>
			<?php endif ?>
		</div>

		<div class="wrapper">
			<?= $this->fetch('contentBody') ?>
		</div>
	</div>

</div>
