<?php echo $this->Html->script('OpenEmis.angular/kd-angular-splitter'); ?>
<style type="text/css">
.error .chosen-choices {
    border-color: #CC5C5C !important;
}
</style>
<script type="text/javascript">
$(document).ready(function() {
	Chosen.init();
	Checkable.init();
	MobileMenu.init();
	TableResponsive.init();
	Tooltip.init();
	ScrollTabs.init();
	Header.init();
});

</script>
