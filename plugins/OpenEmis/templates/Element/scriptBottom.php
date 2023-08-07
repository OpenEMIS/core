<?php
    echo $this->Html->script('OpenEmis.angular/kd-angular-splitter');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/inline.bundle');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/polyfills.bundle');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/vendor.bundle');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/main.bundle');
    echo $this->Html->css('OpenEmis.../js/angular/ngx-adaptor/styles.bundle');
?>

<script type="text/javascript">
$(document).ready(function() {
	Chosen.init();
	Checkable.init();
	MobileMenu.init();
	TableResponsive.init();
	Tooltip.init();
	ScrollTabs.init();
	Header.init();
	// ImageUploader.init();
	// Gallery.init();
});

</script>

<style type="text/css">
.error .chosen-choices {
    border-color: #CC5C5C !important;
}

/* POCOR-4359: temp added overwrites css styling for autocomplete as styles.bundle.css has .ui-autocomplete class that will cause the current autocomplete in core to not show - Added by KK*/
body .ui-autocomplete {
	position: absolute;
}
</style>
