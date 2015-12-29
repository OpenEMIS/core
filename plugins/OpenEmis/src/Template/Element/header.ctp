<?php
$session = $this->request->session();
$homeUrl = $session->check('System.home') ? $session->read('System.home') : [];
$url = '';
if (!empty($homeUrl)) {
	$url = $this->Url->build($homeUrl);
}
?>

<header>
	<nav class="navbar navbar-fixed-top">
		<div class="navbar-left">
			<a href="<?= $this->Url->build($homeUrl) ?>">
				<span class="brand-logo">
					<i class="kd-openemis"></i>
					<h1><?php echo $_productName ?></h1>
				</span>
			</a>
		</div>
		<?php if (!isset($headerSideNav) || (isset($headerSideNav) && $headerSideNav)) : ?>
		<div class="navbar-right">
			<?php echo $this->element('OpenEmis.header_navigation') ?>
		</div>
		<?php endif ?>
	</nav>
</header>