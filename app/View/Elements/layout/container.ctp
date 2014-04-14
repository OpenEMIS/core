<div class="body_title"><?php echo __($bodyTitle); ?></div>
<div class="body_content">
	<?php echo $this->element('left_nav'); ?>
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
?>
