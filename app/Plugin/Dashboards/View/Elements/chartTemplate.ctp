<div class="gen-chart-render left">
	<div id="<?php echo $chartContainerId; ?>"><?php echo __('Charts will load here');?></div>
	<script type="text/javascript">
		<?php 
			$tWidth = empty($tWidth)? 400:$tWidth;
			$tHeight = empty($tHeight)? 300:$tHeight;
		?>
		var <?php echo $chartVarId; ?> = new FusionCharts("<?php echo $this->webroot; ?>Dashboards/js/Charts/<?php echo $swfUrl;?>", "<?php echo $chartId; ?>", "<?php echo $tWidth;?>", "<?php echo $tHeight;?>", "0", "1");
		<?php echo $chartVarId; ?>.setJSONUrl("<?php echo $this->Html->url($chartURLdata); ?>/" + Math.random());
		<?php echo $chartVarId; ?>.render("<?php echo $chartContainerId; ?>");
	</script>
</div>