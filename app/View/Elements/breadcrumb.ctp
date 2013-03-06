<?php echo $this->Html->css('breadcrumb', 'stylesheet', array('inline' => false)); ?>

<?php
$home = $this->Html->link(
	$this->Html->image('home_icon.png', array('title' => __('Home'))),
	array('plugin'=>null,'controller' => 'Home', 'action' => 'index'), 
	array('escape' => false));
?>

<ul id="breadcrumb">
    <li><?php echo $home ?></li>
	<?php foreach($_breadcrumbs as $b) { ?>
	<li><?php echo $b['selected'] ? $this->Utility->ellipsis($b['title']) : $this->Html->link($this->Utility->ellipsis($b['title']), $b['link']['url']) ?></li>
	<?php } ?>
</ul>