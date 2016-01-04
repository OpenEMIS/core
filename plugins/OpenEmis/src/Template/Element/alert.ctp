<?php
$session = $this->request->session();
if ($session->check('_alert')) :
	$_alertArray = $session->read('_alert');
	
	$session->delete('_alert');
?>

<?php 
if (!empty($_alertArray)) {
	foreach ($_alertArray as $key => $_alert) {
		$class = 'alert ' . $_alert['class'];
 ?>
		<div class="<?php echo $class; ?>">
			<?php
			if($_alert['closeButton']) {
				echo '<a class="close" aria-hidden="true" href="#" data-dismiss="alert">&times;</a>';
			}
			echo $_alert['message'];
			?>
		</div>
<?php 
	}
} 
?>

<?php endif; ?>
