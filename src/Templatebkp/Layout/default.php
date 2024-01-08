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
	$icon = strpos($_productName, 'School') !== false ? '_school' : '';
	echo $this->Html->meta('icon', 'favicon'.$icon.'.ico');
	echo $this->fetch('meta');

	echo $this->element('styles');
	echo $this->fetch('css');

	echo $this->element('scripts');
	echo $this->fetch('script');

    echo $this->element('Angular.app');
	?>
</head>

<?php echo $this->element('OpenEmis.analytics') ?>

<body class='fuelux' ng-controller="AppCtrl">

	<?= $this->element('OpenEmis.header'); ?>

	<bg-splitter orientation="horizontal" class="pane-wrapper" resize-callback="splitterDragCallback" elements="getSplitterElements">
		<bg-pane id="leftPane" class="left-pane" max-size-p="30">
			<div class="pane-container">
				<?php
	        		echo $this->element('OpenEmis.navigation');
				?>
			</div>
		</bg-pane>

		<bg-pane id="rightPane" class="right-pane pane-container">
			<?php
				echo $this->fetch('content');
				if (isset($modals)) {
					echo $this->element('ControllerAction.modal');
				}
			?>
		</bg-pane>
	</bg-splitter>

	<?= $this->element('OpenEmis.footer') ?>
	<?= $this->fetch('scriptBottom'); ?>
	<?= $this->element('OpenEmis.scriptBottom') ?>

</body>
</html>
