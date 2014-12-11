<?php
$description = __d('open_emis', 'OpenEMIS: The Open Source Education Management Information System');
?>

<!DOCTYPE html>
<html lang="<?php echo $lang_locale; ?>" dir="<?php echo $lang_dir; ?>" class="<?php echo $lang_dir == 'rtl' ? 'rtl' : '' ?>">
<head>
	<?php echo $this->Html->charset(); ?>
	<title><?php echo $description ?></title>
	<?php
		echo $this->Html->meta('favicon', 'favicon.ico', array('type' => 'icon'));
		echo $this->fetch('meta');
		
		echo $this->Html->css('default/bootstrap.min', array('media' => 'screen'));
		echo $this->Html->css('login', array('media' => 'screen'));
		echo $this->Html->css('layout', array('media' => 'screen'));
		
		if($lang_dir=='rtl') {
			echo $this->Html->css('rtl', array('media' => 'screen'));
		}
		
		echo $this->Html->script('jquery');
		echo $this->Html->script('css_browser_selector');
	?>
</head>

<body onload="$('#SecurityUserUsername').focus()" class="login">
	<div class="body-wrapper">
		<div id="header">
			<div class="col-md-6">
				<div class="logo">
					<a href="https://www.openemis.org" target="_blank"><?php echo $this->Html->image('logo.png', array('title' => $_productName, 'alt' => $_productName)) ?></a>
				</div>
				<h1><?php echo $_productName ?></h1>
			</div>
		</div>

		<div class="login-box">
			<div class="title"><?php echo $_productName ?></div>
			<?php 
			echo $this->element('alert');
			echo $this->Form->create('SecurityUser', array(
				'url' => array('plugin' => false, 'controller' => 'Security', 'action' => 'login'),
				'inputDefaults' => array(
					'required' => false,
					'div' => 'form-group',
					'label' => false,
					'class' => 'form-control',
					'autocomplete' => 'off'
				)
			));
			echo $this->Form->input('username', array('value' => $username, 'placeholder' => __('Username')));
			echo $this->Form->input('password', array('value' => $password, 'placeholder' => __('Password')));

			if (isset($showLanguage) && $showLanguage) {
				echo $this->Form->input('language', array('options' => $languageOptions, 'value' => $lang, 'onchange' => "$('#reload').click()"));
			}
			?>

			<div class="form-group">
				<?php echo $this->Form->button(__('Login'), array('type' => 'submit', 'name' => 'submit', 'value' => 'login', 'class' => 'btn btn-primary btn-login')) ?>
				<button class="hidden" value="reload" name="submit" type="submit" id="reload">reload</button>
			</div>
			<?php echo $this->Form->end() ?>
		</div>

		<div class="partners">
			<?php
				foreach($images as $image) {
					echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $image["id"]));
				}
			?>
		</div>
		
		<?php echo $this->element('layout/footer') ?>
	</div>
</body>
</html>

