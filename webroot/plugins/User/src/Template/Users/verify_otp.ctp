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
				'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'verifyOtp',$encryptdata],
				'class' => 'form-horizontal'
			]);
			if ($enableLocalLogin) {
				echo $this->Form->input('otp', ['placeholder' => __('OTP'), 'label' => false, 'minLength' => 6, 'maxLength'=> 6, 'required'=>'required']);
				echo $this->Form->hidden('username', ['placeholder' => __('Username'), 'label' => false, 'value' => $username]);
				echo $this->Form->hidden('password', ['placeholder' => __('Password'), 'label' => false, 'value' => $password]);
			}
			?>
			<div class="form-group">
				<?php if ($enableLocalLogin) : ?>
				<?= $this->Form->button(__('Submit'), ['type' => 'submit', 'name' => 'submit', 'value' => 'login', 'class' => 'btn btn-primary btn-login']) ?>
				<?php endif; ?>
			<?= $this->Form->end() ?>

			</div>
		</div>

		<?= $this->element('OpenEmis.footer') ?>
	</div>
</body>
</html>
