<?php
$description = __d('open_emis', $_productName);
$icon = strpos($_productName, 'School') !== false ? '_school' : '';
?>

<!DOCTYPE html>
<html lang="<?= $htmlLang; ?>" dir="<?= $htmlLangDir; ?>" class="<?= $htmlLangDir == 'rtl' ? 'rtl' : '' ?>">
<head>
	<?= $this->Html->charset(); ?>
	<title><?= $description ?></title>
	<?php
		echo $this->Html->meta(['name' => 'viewport', 'content' => 'width=320, initial-scale=1']);
		echo $this->Html->meta('favicon', 'favicon.ico', ['type' => 'icon']);
		echo $this->fetch('meta');
		
		echo $this->Html->css('OpenEmis.../plugins/bootstrap/css/bootstrap.min', ['media' => 'screen']);
		echo $this->Html->css('OpenEmis.../plugins/font-awesome/css/font-awesome.min', ['media' => 'screen']);
		echo $this->Html->css('OpenEmis.reset', ['media' => 'screen']);
		
		$debug = Cake\Core\Configure::read('debug');
		if ($debug) { //This is to the dev testing purpose.
			echo $this->Html->css('OpenEmis.master');
		} else {
			echo $this->Html->css('OpenEmis.master.min');
		}

		if (isset($theme)) {
			echo $this->Html->css($theme);
		}
		
		echo $this->Html->script('OpenEmis.lib/css_browser_selector');
		echo $this->Html->script('OpenEmis.lib/jquery/jquery.min');
		echo $this->Html->script('OpenEmis.../plugins/bootstrap/js/bootstrap.min');
	?>

	<link rel="stylesheet" href="<?= $this->Url->css('themes/layout.min') ?>?timestamp=<?=$lastModified?>" >
	<!--[if gte IE 9]>
	<?php
		echo $this->Html->css('OpenEmis.ie/ie9-fixes');
	?>
	<![endif]-->
</head>
<?php echo $this->element('OpenEmis.analytics') ?>

<body onload="$('input[type=text]:first').focus()" class="login">
	<div class="body-wrapper">

		<div class="login-box">
			<div class="title">
				<span class="title-wrapper">
					<?php if (!$productLogo) : ?>
					<i class="kd-openemis"></i>
					<?php else: ?>
					<?= $this->Html->image($productLogo, [
						'style' => 'max-height: 45px; vertical-align: top'
					]); ?>
					<?php endif; ?>
					<h1><?= $_productName ?></h1>
				</span>
			</div>
			
			<?php 
			echo $this->element('OpenEmis.alert');
			?>
			
			<?php 
			echo $this->Form->create('Users', [
				'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'postForgotPassword'],
				'class' => 'form-horizontal'
			]);
			?>

			<div class="form-description">
				<?= __('To reset your password, enter the email address or username you used in this system. A link will be emailed to you which will let you reset your password.') ?>
			</div>

			<?php 
			echo $this->Form->input('username', ['placeholder' => __('Username or Email'), 'label' => false]);
			?>

			<div class="form-group">
				<?= $this->Form->button(__('Reset Password'), ['type' => 'submit', 'name' => 'submit', 'class' => 'btn btn-primary btn-login']) ?>
			</div>
			<div class="links-wrapper">
				<a target="_self" href="./"><?= __('Return to login') ?></a>
			</div>
			<?= $this->Form->end() ?>
		</div>
		
		<?= $this->element('OpenEmis.footer') ?>
	</div>
</body>
</html>
