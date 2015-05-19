<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

?>
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

<body>
	<div id="wrapper">
		<div id="sidebar-wrapper">
			<?= $this->element('OpenEmis.navigation'); ?>
		</div>	
		<?php 
		echo $this->element('OpenEmis.header');
		echo $this->fetch('content');
		if (isset($modal)) {
			echo $this->element('ControllerAction.modal');
		}
		echo $this->fetch('scriptBottom');
		?>

	</div>
	<?= $this->element('OpenEmis.footer') ?>
</body>

<script type="text/javascript">
$(function () {
	$('[data-toggle="tooltip"]').tooltip();
})
</script>
</html>
