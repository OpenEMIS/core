<div class="button-responsive custom-buttons dropdown">
	<?php
		if (!empty($moreButtonLink)) {
			echo $this->Html->link($moreButtonLink['title'], $moreButtonLink['url'], $moreButtonLink['options']);
		}
	?>
	<?php if (!empty($actionButtons)) : ?>
		<ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
			<div class="dropdown-arrow">
				<i class="fa fa-caret-up"></i>
			</div>
			<?php foreach ($actionButtons as $key => $actionButton) : ?>
				<li role="presentation">
					<?= $this->Html->link($actionButton['label'], $actionButton['url'], $actionButton['attr']); ?>
				</li>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</div>
