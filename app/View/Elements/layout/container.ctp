<?php echo $this->element('breadcrumb'); ?>
<div class="content_wrapper">
    <h1>
        <span><?php echo $this->fetch('contentHeader'); ?></span>
        <?php echo $this->fetch('contentActions'); ?>
    </h1>
	<?php 
	echo $this->element('alert');
	echo $this->fetch('contentBody');
	?>
</div>
