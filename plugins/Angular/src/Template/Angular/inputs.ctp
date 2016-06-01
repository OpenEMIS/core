<?php
	use	Angular\View\Form\AngularInputsContext;
?>
<?php if (!is_null($_type)):?>
	<?php
		if ($_type!='date') {
			// we need this so that the error div block will be included
			// as for date inputs, will re-visit again to make it work.
			$context = new AngularInputsContext($request, $context);
			$this->HtmlField->Form->context($context);
		}
	?>
	<?= $this->HtmlField->render($_type, 'edit', $data, $_fieldAttr, $options); ?>
<?php else:?>
	<i class="hidden"></i>
<?php endif;?>
