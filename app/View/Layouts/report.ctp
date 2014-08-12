<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php echo $this->Html->charset(); ?>
<title>
    <?php echo $title_for_layout; ?>
</title>
<?php
    echo $this->Html->meta('icon');
	echo $this->Html->css('style');
	echo $this->Html->css('common');
	echo $this->Html->css('body_common');
	echo $this->Html->css('table');
?>
</head>
<body>
<div id="main">    
    <?php echo $content_for_layout; ?>
    <?php echo $this->element('sql'); ?>
</div>
</body>
</html>