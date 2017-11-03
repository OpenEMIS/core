<?php
include_once "config.php";
session_start();

$totalSteps = 5;
$currentStep = 1;
if(!empty($_GET) || isset($_GET['step'])) {
	$currentStep = $_GET['step'];
}
?>

<div class="content index">
	<div class="index-content">
		<div class="installer">
			<div class="installer-bar">
				<div class="installer-line"></div>
				<div class="installer-steps">
					<?php
					for($i=1; $i<=$totalSteps; $i++) {
						$class = '';
						if($currentStep == $i) {
							$class = 'on';
						} else if($currentStep > $i) {
							$class = 'pass';
						}
						echo sprintf('<p class="%s"><span>%d</span></p>', $class, $i);
					}
					?>
				</div>
			</div><!-- end installer-bar-->
			
			<div class="installer-content">
			<?php
			$page = 'step'.$currentStep.'.php';
			include_once('steps/' . $page);
			?>
			</div><!-- end installer-content -->
		</div><!-- end installer -->
	</div>
</div>