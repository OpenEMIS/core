<?php
$url = '#';
if (!empty($homeUrl)) {
	$url = $this->Url->build($homeUrl);
}
?>

<header>
	<nav class="navbar navbar-fixed-top">
		<div class="navbar-left">
			<div class="menu-handler">
				<button class="menu-toggle" type="button">
					<i class="fa fa-bars"></i>
				</button>
			</div>
			<a href="<?= $url ?>">
				<div class="brand-logo">
					<?php if (!$productLogo) : ?>
					<i class="kd-openemis"></i>
					<?php else: ?>
					<?= $this->Html->image($productLogo, [
						'style' => 'max-height: 35px;'
					]); ?>
					<?php endif; ?>
					<h1><?php echo $_productName ?></h1>
				</div>
			</a>
		</div>
		<?php if (!isset($headerSideNav) || (isset($headerSideNav) && $headerSideNav)) : ?>
		<div class="navbar-right">
			<?php echo $this->element('OpenEmis.header_navigation') ?>
		</div>
		<?php endif ?>
	</nav>
</header>
