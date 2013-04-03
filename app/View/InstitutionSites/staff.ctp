<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
//echo $this->Html->script('search', false);
//echo $this->Html->script('institution_site_students', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff_search" class="content_wrapper search">
    <h1><?php echo __('List of Staff'); ?></h1>
    <?php echo $this->element('alert'); ?>
	
	
</div>
