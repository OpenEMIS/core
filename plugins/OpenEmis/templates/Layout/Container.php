<?php
	$toolbar = $this->fetch('toolbar');
	$toolbarClass = [];

	if (isset($toolbarButtons) && $toolbarButtons->offsetExists('search')) {
		$toolbarClass[] = 'toolbar-search';

		$found = false;
		foreach ($toolbarButtons as $button) {
			if ((isset($button['type']) && $button['type'] == 'button') || !isset($button['type'])) {
				$found = true;
				break;
			}
		}
		if ($found==false) {
			$toolbarClass[] = 'btn-none';
		}
	}
	if (isset($indexElements) && isset($indexElements['advanced_search'])) {
		$toolbarClass[] = 'toolbar-search-adv';
	}

	// For Angular
	if (isset($ngController)) {
		$ngController = 'ng-controller="'.$ngController.'"';
	} else {
		$ngController = '';
	}

	// For page where no Breadcrumb
	if (isset($noBreadcrumb)) {
		$wrapperClass = 'wrapper no-breadcrumb';
	} else {
		$wrapperClass = 'wrapper';
	}
?>

<div class="content-wrapper" <?= $ngController; ?>>

	<?= $this->element('OpenEmis.breadcrumbs') ?>

	<div class="page-header">
		<h2 id="main-header"><?= $this->fetch('contentHeader') ?></h2>
		<?php if (!empty($toolbar)) : ?>
			<div class="toolbar <?= implode(' ', $toolbarClass) ?>">
				<?= $this->fetch('toolbar') ?>
			</div>
		<?php endif ?>
	</div>

	<div class="<?= $wrapperClass;?>">
		<div class="wrapper-child">
			<?= $this->fetch('contentBody') ?>
		</div>
	</div>
</div>
