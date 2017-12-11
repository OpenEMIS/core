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
		echo $this->Html->css('OpenEmis.kordit/kordit', ['media' => 'screen']);
		echo $this->Html->css('OpenEmis.layout', ['media' => 'screen']);
		echo $this->Html->css('OpenEmis.layout.rtl', ['media' => 'screen']);

		if (isset($theme)) {
			echo $this->Html->css($theme);
			echo $this->Html->css('themes/layout.min');
		}

		echo $this->Html->script('OpenEmis.css_browser_selector');
		echo $this->Html->script('OpenEmis.jquery.min');
	?>
</head>

<body onload="$('input[type=text]:first').focus()" class="login">
	<div class="body-wrapper">
		<?= $this->element('OpenEmis.header', ['headerSideNav' => false, 'menuToggle' => false]) ?>

		<div class="login-box">
			<div class="title">
				<span class="title-wrapper">
					<i class="kd-openemis ltl-view"></i>
					<h1><?= $_productName ?></h1>
					<i class="kd-openemis rtl-view"></i>
				</span>
			</div>
			<?php
			echo $this->element('OpenEmis.alert');

			echo $this->Form->create('Users', [
				'url' => ['plugin' => 'User', 'controller' => 'Users', 'action' => 'updatePassword'],
				'class' => 'form-horizontal'
			]);
			echo $this->Form->input('username', ['placeholder' => __('Username'), 'label' => false, 'value' => $username]);
			echo $this->Form->input('password', ['placeholder' => __('Current Password'), 'label' => false]);
			echo $this->Form->input('new_password', ['type' => 'password', 'placeholder' => __('New Password'), 'label' => false]);
			echo $this->Form->input('retype_password', ['type' => 'password', 'placeholder' => __('Retype Password'), 'label' => false]);
			?>

			<div class="form-group">
				<?= $this->Form->button(__('Update'), ['type' => 'submit', 'name' => 'submit', 'value' => 'update', 'class' => 'btn btn-primary btn-login']) ?>
				<?= $this->Html->link(__('Cancel'), ['action' => 'login'], ['class' => 'btn btn-primary btn-cancel']) ?>
				<button class="hidden" value="reload" name="submit" type="submit" id="reload">reload</button>
			</div>
			<?= $this->Form->end() ?>
		</div>

		<?= $this->element('OpenEmis.footer') ?>
	</div>
</body>
</html>
