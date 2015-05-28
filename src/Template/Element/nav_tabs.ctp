<?php if (isset($tabElements)) : ?>
	<?php $selectedAction = isset($selectedAction) ? $selectedAction : null; ?>
	<div id="tabs" class="nav nav-tabs horizontal-tabs">
		<?php foreach($tabElements as $element => $attr): ?>
			<span role="presentation" class="<?php echo ($element == $selectedAction) ? 'tab-active' : ''; ?>"><?php echo $this->Html->link($attr['text'], $attr['url']); ?></span>
		<?php endforeach; ?>
	</div>
<?php endif ?>

<script type="text/javascript">
	$(document).ready(function(){
	$('#tabs').scrollTabs();
	});
</script>
