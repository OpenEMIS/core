<div class="row">
	<div id="<?php echo $chartContainerId; ?>"><?php echo __('Charts will load here');?></div>
	<script type="text/javascript">
		var <?php echo $chartVarId; ?> = new FusionCharts("<?php echo $this->webroot; ?>FusionCharts/js/Charts/<?php echo $swfUrl;?>", "<?php echo $chartId; ?>", "<?php echo $width;?>", "<?php echo $height;?>", "0", "1");
		<?php echo $chartVarId; ?>.setJSONUrl("<?php echo $this->Html->url($chartURLdata); ?>/" + Math.random());
		<?php echo $chartVarId; ?>.render("<?php echo $chartContainerId; ?>");
	</script>
</div>