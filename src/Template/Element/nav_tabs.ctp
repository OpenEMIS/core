<?php
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.mousewheel', ['block' => true]);
echo $this->Html->script('OpenEmis.../plugins/scrolltabs/js/jquery.scrolltabs', ['block' => true]);
?>

<?php if (isset($tabElements)) : ?>
	<?php $selectedAction = isset($selectedAction) ? $selectedAction : null; ?>
	<div id="tabs" class="nav nav-tabs horizontal-tabs">
		<?php foreach($tabElements as $element => $attr): ?>
			<span role="presentation" class="<?php echo ($element == $selectedAction) ? 'tab-active' : ''; ?>"><?php echo $this->Html->link(__($attr['text']), $attr['url']); ?></span>
		<?php endforeach; ?>
	</div>
<?php endif ?>
