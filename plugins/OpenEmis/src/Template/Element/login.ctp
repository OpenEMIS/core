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
		echo $this->Html->meta('favicon', 'favicon'.$icon.'.ico', ['type' => 'icon']);
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

			echo $this->Form->create('Users', [
				'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin'],
				'class' => 'form-horizontal'
			]);
			if ($enableLocalLogin) {
				echo $this->Form->input('username', ['placeholder' => __('Username'), 'label' => false, 'value' => $username]);
				echo $this->Form->input('password', ['placeholder' => __('Password'), 'label' => false, 'value' => $password]);
			}
			?>
			<?php
				if (isset($showLanguage) && $showLanguage) :
			?>
				<div class="input-select-wrapper">
				<?= $this->Form->input('System.language', [
						'label' => false,
						'options' => $languageOptions,
						'value' => $htmlLang,
						'onchange' => "$('#reload').click()"
					]);
				?>
				</div>
			<?php endif;?>
			<div class="form-group">
				<?php if ($enableLocalLogin) : ?>
				<?= $this->Form->button(__('Login'), ['type' => 'submit', 'name' => 'submit', 'value' => 'login', 'class' => 'btn btn-primary btn-login']) ?>
				<?php endif; ?>
				<button class="hidden" value="reload" name="submit" type="submit" id="reload">reload</button>
			<?= $this->Form->end() ?>

			<div class="links-wrapper">
				<a target="_self" href="./ForgotUsername"><?php echo __('Forgot username?') ?></a>
				<a target="_self" href="./ForgotPassword"><?php echo __('Forgot password?') ?></a>
			</div>


			<?php
				if ($authentications) :
			?>

			<?php if ($authentications && $enableLocalLogin) : ?>
			<hr />
				<?= '<center>'.__('OR').'</center>'?>
			<hr />
			<?php endif;?>
				<div class="input-select-wrapper sso-options">
				<?php
					echo $this->Form->input('idp', [
						'options' => $authentications,
						'label' => false,
						'onchange' => 'window.document.location.href=this.options[this.selectedIndex].value;'
					]);
				?>
				</div>
			<?php endif; ?>

			</div>
		</div>

		<?= $this->element('OpenEmis.footer') ?>
	</div>
</body>
</html>
