<!DOCTYPE html>
<html lang="<?= $htmlLang; ?>" dir="<?= $htmlLangDir; ?>" class="<?= $htmlLangDir == 'rtl' ? 'rtl' : '' ?>">
<head>
	<?= $this->Html->charset(); ?>
	<title><?= $_productName ?></title>

	<?php
	echo $this->Html->meta('icon');
	echo $this->fetch('meta');

	echo $this->element('styles');
	echo $this->fetch('css');

	echo $this->element('scripts');
	echo $this->fetch('script');
		
	?>

</head>
<?php echo $this->element('OpenEmis.analytics') ?>

<body class='fuelux' ng-app="OE_Styleguide">
	
	<bg-splitter orientation="horizontal" class="pane-wrapper">
		<bg-pane class="left-pane">
			<div class="pane-container">

				<?php echo $this->element('OpenEmis.navigation');?>

			</div>
		</bg-pane>
		
		<bg-pane class="right-pane pane-container" min-size-p="60">
			<header>
				<nav class="navbar navbar-fixed-top">
					<div class="navbar-left">
						<div class="menu-handler">
							<button class="menu-toggle" type="button">
								<i class="fa fa-bars"></i>
							</button>
						</div>
						<a href="/">
							<div class="brand-logo">
								<i class="kd-openemis"></i>
								<h1><?php echo $_productName ?></h1>
							</div>
						</a>
					</div>
					<div class="navbar-right">

						<div class="header-navigation">

							<div class="btn-group">
						        <a class="btn" href="<?= $this->Url->build($homeUrl) ?>">
						            <i class="fa fa-home"></i>
						        </a>
						    </div>

						</div>

					</div>
				</nav>
			</header>

			<div class="content-wrapper">
				
				<div class="page-header">
					<h2 id="main-header"><?= __('RESTful API Documentation')?> - <?=__('Plugin Version '.$version)?></h2>
				</div>

				<div class="wrapper">
					<div class="wrapper-child">
						
						<div class="panel">
							<div class="panel-body">

								<?php echo $this->fetch('content');?>	

							</div>
						</div>

					</div>
				</div>

			</div>

		</bg-pane>
	</bg-splitter>	

	<?= $this->element('OpenEmis.footer') ?>
	<?= $this->fetch('scriptBottom'); ?>
	<?= $this->element('OpenEmis.scriptBottom') ?>

</body>
</html>
