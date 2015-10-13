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

<body class='default'>

	<?=  $this->element('OpenEmis.header'); ?>

	<?php
	$baseUrl = $this->Url->build([
		'plugin' => $this->request->params['plugin'],
		'controller' => $this->request->params['controller'],
		'action' => 'setJqxSpliterSize'
	]);
	?>
	<div id="main-splitter" url="<?= $baseUrl ?>">
		<div class="left-pane" style="<?= $SystemLayout_leftPanel; ?>">
			<?php 
        		if($htmlLangDir != 'rtl'){
        			echo $this->element('OpenEmis.navigation');
        		}
				else{
					echo $this->element('OpenEmis.header');
					echo $this->fetch('content');
					if (isset($modal)) {
						echo $this->element('ControllerAction.modal');
					}
				}
			?>
		</div>
        <div class="right-pane" style="<?= $SystemLayout_rightPanel; ?>">
        	<?php 
        		if($htmlLangDir != 'rtl'){
					echo $this->element('OpenEmis.header');
					echo $this->fetch('content');
					if (isset($modal)) {
						echo $this->element('ControllerAction.modal');
					}
        		}
				else{
					echo $this->element('OpenEmis.navigation');
				}
			?>
        </div>
	</div>
	<div id="jqxButton" class="menu-toggle">
		<i class="fa fa-angle-double-left"></i>
		<span class="menu-text"><?= __('Menu') ?></span>
	</div>
	
	<?= $this->element('OpenEmis.footer') ?>
	<?= $this->fetch('scriptBottom'); ?>
	<?= $this->element('OpenEmis.scriptBottom') ?>
</body>
</html>
