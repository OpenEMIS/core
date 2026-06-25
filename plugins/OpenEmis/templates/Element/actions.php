<?php
//use Cake\Log\Log;
?>
<div class="dropdown">
	<button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
		<?= __('Select') ?><span class="caret-down"></span>
	</button>

	<ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
		<div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

		<?php foreach ($buttons as $key => $btn) : ?>
			<?php
				if ($key == 'view') {
					//Log::debug('[TEMP-LOG] Actions element: view button url array = ' . var_export($btn['url'], true));
					$builtUrl = $this->Url->build($btn['url']);
					//Log::debug('[TEMP-LOG] Actions element: view built URL = ' . $builtUrl);
				}
			?>
			<li role="presentation">
				<?= $this->Html->link($btn['label'], $btn['url'], $btn['attr']) ?>
			</li>
		<?php endforeach ?>
<!--        --><?//= die() ?>
	</ul>
</div>
