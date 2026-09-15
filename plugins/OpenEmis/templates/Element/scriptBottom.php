<?php
    echo $this->Html->script('OpenEmis.angular/kd-angular-splitter');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/inline.bundle');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/polyfills.bundle');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/vendor.bundle');
    echo $this->Html->script('OpenEmis.angular/ngx-adaptor/main.bundle');
    echo $this->Html->css('OpenEmis.../js/angular/ngx-adaptor/styles.bundle');
?>

<script type="text/javascript">
    // POCOR-9786: set on every page (not just the few controller actions that pass it explicitly) so the
    // Angular SPA (frontend/src/app/api.service.ts apiV4BaseUrl) always resolves api/v4/ against this
    // deployment's real base URL, instead of falling back to window.location.href - which is wrong for
    // any page nested under /Plugin/Controller/action/... and worse the deeper the current route is.
    // Computed directly (not read from session) because System.baseCoreUrl is only ever written by
    // UsersTable::afterLogin() - any session already active before that ran (or any auth flow that
    // skips it, e.g. SSO) would otherwise see an empty value here.
    localStorage.setItem('baseCoreUrl', '<?= h(\Cake\Routing\Router::url('/', true)) ?>');
</script>

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
