<?php
$session = $this->request->session();
if ($session->check('_alert')) :
	$_alert = $session->read('_alert');
	$class = 'alert ' . $_alert['class'];
	$session->delete('_alert');
?>

<div class="<?php echo $class; ?>">
	<?php
	if($_alert['closeButton']) {
		echo '<a class="close" aria-hidden="true" href="#" data-dismiss="alert">&times;</a>';
	}
	echo $_alert['message'];
	?>
</div>

<?php endif; ?>
