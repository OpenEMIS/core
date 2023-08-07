<div class="dropdown">
	<button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
		<?= __('Select') ?><span class="caret-down"></span>
	</button>

	<ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
		<div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

		<?php foreach ($buttons as $key => $btn) : ?>
			<li role="presentation">
				<?= $this->Html->link($btn['label'], $btn['url'], $btn['attr']) ?>
			</li>
		<?php endforeach ?>
	</ul>
</div>
