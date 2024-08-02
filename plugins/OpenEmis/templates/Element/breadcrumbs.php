<?php
if (!isset($_breadcrumbs)) {
	$_breadcrumbs = [];
}
if (!empty($_breadcrumbs)) {
	if (empty($homeUrl)) {
		$homeUrl = [];
	}
?>
<ul class="breadcrumb panel-breadcrumb">
	<li><a href="<?= $this->Url->build($homeUrl) ?>"><i class="fa fa-home"></i></a></li>
	
	<?php foreach($_breadcrumbs as $b) : ?>
	<li>
		<?php
		$title = $this->Text->truncate(__($b['title']), '30', ['ellipsis' => '...', 'exact' => false]);
		echo $b['selected'] ? $title : $this->Html->link($title, $b['link']['url']);
		?>
	</li>
	<?php endforeach ?>
</ul>
<?php
}
?>
<!--for POCOR-8127 starts-->
<script>
// Assume you're outputting the session values into a JavaScript object
var sessionData = {
    username: "<?php echo $_SESSION['auth_username']; ?>",
    password: "<?php echo $_SESSION['auth_password']; ?>"
};
// Now you can use sessionData to set session storage values in JavaScript
// sessionStorage.setItem('username', sessionData.username);
// sessionStorage.setItem('password', sessionData.password);
</script>
<!--for POCOR-8127 ends-->