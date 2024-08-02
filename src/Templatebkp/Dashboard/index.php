<div class="split-handler splitter-slide-in ng-scope" style="left: 192px;">
	<div id="uniquemenu" class="menu-btn">
		<i class="fa fa-angle-left"></i>
	</div>
</div>

<script type="text/javascript">
	var id = 0;
	$(document).ready(function() {
		$('#uniquemenu').click(function() {
			if(id % 2 == 0){
				$("body.fuelux .right-pane").addClass("hideLeftPane");
				$(".menu-btn").addClass("leftMenuSplitter");
				$("body.fuelux .right-pane").removeClass("showLeftPane");
			} else {
				$("body.fuelux .right-pane").addClass("showLeftPane");
				$(".menu-btn").removeClass("leftMenuSplitter");
				$("body.fuelux .right-pane").removeClass("hideLeftPane");
			}
			id++
		});
});
	
</script>

<app-root></app-root>
<?php
	echo $this->Html->script('angular11/dashboard/runtime.7b63b9fd40098a2e8207');
	echo $this->Html->script('angular11/dashboard/polyfills.0947d4c9434ec41ea5bf');
	// echo $this->Html->css('angular11/dashboard/styles.1e4a81f00ad2e120aa7a');
	echo $this->Html->css('angular11/dashboard/newStyles');
	echo $this->Html->script('angular11/dashboard/main.f111d50a847940645a74');
	echo $this->Html->script('angular11/dashboard/scripts.d46a215e198ba486ca2a');
?>
