<?php if (!is_null($_type)):?>
	<?= $this->HtmlField->render($_type, 'edit', $data, $_fieldAttr, $options); ?>
<?php else:?>
	<i class="hidden"></i>
<?php endif;?>
