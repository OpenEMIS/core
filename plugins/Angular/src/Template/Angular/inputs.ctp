<?php
	use	Angular\View\Form\AngularInputsContext;
?>
<?php if (!is_null($_type)):?>
	<?php
		if ($_type!='date') {
			$context = new AngularInputsContext($request, $context);
			$this->HtmlField->Form->context($context);
		}
	?>
	<?= $this->HtmlField->render($_type, 'edit', $data, $_fieldAttr, $options); ?>
<?php else:?>
	<i class="hidden"></i>
<?php endif;?>
