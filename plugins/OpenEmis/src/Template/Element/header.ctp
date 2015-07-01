<header>
	<nav class="navbar navbar-fixed-top">
		<?php if (!isset($menuToggle) || (isset($menuToggle) && $menuToggle)) : ?>
		<div id="menu-toggle">
			<a href="#menu-toggle" class="btn toggle-btn"><i class="fa fa-reorder"></i></a>
		</div>
		<?php endif ?>
		<div class="navbar-left">
			<span class="brand-logo">
				<i class="kd-openemis ltl-view"></i>
				<h1><?php echo $_productName ?></h1>
				<i class="kd-openemis rtl-view"></i>
			</span>
		</div>
		<?php if (!isset($headerSideNav) || (isset($headerSideNav) && $headerSideNav)) : ?>
		<div class="navbar-right">
			<?php echo $this->element('OpenEmis.header_navigation') ?>
		</div>
		<?php endif ?>
	</nav>
</header>


<!-- Menu Toggle Script -->
<script>
	$("#menu-toggle").click(function(e) {
	    e.preventDefault();
	    $("#wrapper").toggleClass("toggled");
	});
</script>
