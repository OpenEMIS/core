<?php
	$toolbar = $this->fetch('toolbar');
	$toolbarClass = [];

	if (isset($toolbarButtons) && $toolbarButtons->offsetExists('search')) {
		$toolbarClass[] = 'toolbar-search';

		$found = false;
		foreach ($toolbarButtons as $button) {
			if ((array_key_exists('type', $button) && $button['type'] == 'button') || !array_key_exists('type', $button)) {
				$found = true;
				break;
			}
		}
		if ($found==false) {
			$toolbarClass[] = 'btn-none';
		}
	}
	if (isset($indexElements) && array_key_exists('advanced_search', $indexElements)) {
		$toolbarClass[] = 'toolbar-search-adv';
	}
?>

<div class="content-wrapper">

	<?= $this->element('OpenEmis.breadcrumbs') ?>

	<div class="page-header">
		<h2><?= $this->fetch('contentHeader') ?></h2>
		<?php if (!empty($toolbar)) : ?>
			<div class="toolbar <?= implode(' ', $toolbarClass) ?>">
				<?= $this->fetch('toolbar') ?>
			</div>
		<?php endif ?>
	</div>

	<div class="wrapper">
		<div class="wrapper-child">
			<?= $this->fetch('contentBody') ?>
		</div>
	</div>
</div>