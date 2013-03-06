<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
echo $this->Html->script('jquery.ui', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<script>

</script>
<div id="staff" class="content_wrapper">
	
	<h1>
		<span>Programmes </span>
		<a class="void link-edit divider">Edit</a>
		<!-- <?php echo $this->Html->link('History', array('action' => 'history'), array('class' => 'divider')); ?> -->
	</h1>
	
</div>