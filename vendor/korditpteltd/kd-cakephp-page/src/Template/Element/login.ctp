<?php
$description = __d('open_emis', $_productName);
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
					<i class="kd-openemis"></i>
					<h1>OpenEMIS Core</h1>
				</span>
			</div>
			<?php
			echo $this->element('OpenEmis.alert');

			echo $this->Form->create('Users', [
				'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin'],
				'class' => 'form-horizontal'
			]);
			if (!$_sso) {
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
				<?= $this->Form->button(__('Login'), ['type' => 'submit', 'name' => 'submit', 'value' => 'login', 'class' => 'btn btn-primary btn-login']) ?>
				<button class="hidden" value="reload" name="submit" type="submit" id="reload">reload</button>
			</div>
			<?= $this->Form->end() ?>
		</div>

		<?= $this->element('OpenEmis.footer') ?>
	</div>
</body>
</html>
