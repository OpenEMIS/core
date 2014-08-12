<div class="body_title">
	<?php
	$controllers = array('InstitutionSites', 'InstitutionSiteReports', 'Census', 'Students', 'Staff');
	// if the current controller exists in the list, hyperlink the header
	if (in_array($this->params['controller'], $controllers) && $this->Session->check('InstitutionSite.id')) {
		$id = $this->Session->read('InstitutionSite.id');
		$name = $this->Session->read('InstitutionSite.data.InstitutionSite.name');
		echo $this->Html->link($name, array('controller' => 'InstitutionSites', 'action' => 'view', $id));
	} else {
		echo __($bodyTitle);
	}
	?>
</div>
<div class="body_content">
	<?php echo $this->element('layout/left_nav'); ?>
	<div class="body_content_right">
		<?php echo $this->element('breadcrumb'); ?>
		<div id="<?php echo $this->fetch('contentId'); ?>" class="content_wrapper <?php echo $this->fetch('contentClass'); ?>">
			<h1>
				<span><?php echo $this->fetch('contentHeader'); ?></span>
				<?php echo $this->fetch('contentActions'); ?>
			</h1>
			<?php 
			echo $this->element('alert');
			echo $this->fetch('contentBody');
			?>
		</div>
	</div>
</div>
<?php
if(isset($datepicker) && !empty($datepicker)) {
	echo $this->element('layout/datepicker');
}
if(isset($timepicker) && !empty($timepicker)) {
	echo $this->element('layout/timepicker');
}
?>
