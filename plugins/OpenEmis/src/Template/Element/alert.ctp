
<!--
<div class="alert alert-success" role="alert">Your profile is successfully deleted. </div>
<div class="alert alert-info" role="alert">Please fill in the form below.</div>
<div class="alert alert-warning" role="alert">This is a warning text.</div>
<div class="alert alert-danger" role="alert">Please fill in the required the information.</div>
-->

<?php
$session = $this->request->session();
if($session->check('_alert')) :
	$_alert = $session->read('_alert');
	$alertTypes = $_alert['types'];
	$class = 'alert ' . $alertTypes[$_alert['type']];
	//unset($_SESSION['_alert']);
	$session->delete('_alert');
?>

<div class="<?php echo $class; ?>">
	<?php
	if($_alert['dismissOnClick']) {
		echo '<a class="close" aria-hidden="true" href="#" data-dismiss="alert">&times;</a>';
	}
	echo $_alert['message'];
	?>
</div>

<?php endif; ?>
