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
				<div class="left-menu">
					<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

						<ul id="nav-menu-1" class="nav nav-level-1 collapse in" role="tabpanel" data-level="1">
							<li><a class="accordion-toggle panel-heading" href="/rest/doc" data-toggle="" data-parent="#accordion" aria-expanded="true" aria-controls="nav-menu-2"><span><span><i class="fa kd-institutions"></i></span><b>Documentation</b></span></a></li>
							<ul id="nav-menu-2" class="nav nav-level-2 collapse in" role="tabpanel" data-level="2">
								<li><a href="#" id="Institutions-dashboard-1053">Page 1</a></li>
								<li><a class="accordion-toggle panel-heading collapsed" href="#nav-menu-3" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="nav-menu-3"><span>Page 2</span></a></li>
								<ul id="nav-menu-3" class="nav nav-level-3 collapse" role="tabpanel" data-level="3">
									<li><a href="#" id="Institutions-view">Sub-Page 1</a></li>
									<li><a href="#" id="Institutions-Attachments-index">Sub-Page 2</a></li>
									<li><a href="#" id="Institutions-History-index">Sub-Page 3</a></li>
								</ul>
							</ul>
						</ul>

					</div>
				</div>
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
					<h2 id="main-header"><?= __('Rest API Documentation')?></h2>
				</div>

				<div class="wrapper">
					<div class="wrapper-child">
						
						<div class="panel">
							<div class="panel-body">

								yippee!!

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
