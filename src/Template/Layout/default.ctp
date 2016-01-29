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
<?php echo $this->element('OpenEmis.analytics') ?>

<body class='fuelux' ng-app="OE_Styleguide">
	
	<?=  $this->element('OpenEmis.header'); ?>

	<bg-splitter orientation="horizontal" id="rightPane" class="pane-wrapper">
		<bg-pane class="left-pane">
			<div class="pane-container">
				<?php 
	        		echo $this->element('OpenEmis.navigation');
				?>
			</div>
		</bg-pane>
		
		<bg-pane class="right-pane pane-container" min-size-p="60">	
			<div class="load-content">
				<div class="loader-text">
					<i class="fa kd-openemis"></i>
					<div class="loader lt-ie9"></div>
					<p>Loading...</p>
				</div>
			</div>		
			<?php 
				echo $this->element('OpenEmis.header');
				echo $this->fetch('content');
				if (isset($modal)) {
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
