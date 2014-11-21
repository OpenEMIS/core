<div id="header">
	<div class="col-md-12">
		<div class="logo">
			<a href="<?php echo $this->Html->url(array('plugin' => false, 'controller' => 'Home', 'action' => 'index')) ?>"><?php echo $this->Html->image('logo.png', array('title' => $_productName, 'alt' => $_productName)) ?></a>
		</div>

		<?php echo $this->element('layout/header_side_nav'); ?>
		<?php echo $this->element('layout/top_nav'); ?>
	</div>
</div>
