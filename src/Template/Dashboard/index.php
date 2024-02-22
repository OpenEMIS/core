<app-root></app-root>
<?php
	/*echo $this->Html->script('angular11/main/main.655812e91d2fbe4ecb7e');
	echo $this->Html->script('angular11/main/polyfills.0947d4c9434ec41ea5bf');
	echo $this->Html->script('angular11/main/runtime.7b63b9fd40098a2e8207');
	echo $this->Html->script('angular11/main/scripts.d46a215e198ba486ca2a');
	echo $this->Html->css('angular11/dashboard/newStyles');*/
	echo $this->Html->script(BUILD_MAIN);
	echo $this->Html->script(BUILD_POLYFILLS);
	echo $this->Html->script(BUILD_RUNTIME);
	echo $this->Html->script(BUILD_SCRIPTS);
	echo $this->Html->css(BUILD_STYLE);
?>
<!--for POCOR-8127 starts-->
<script>
// Assume you're outputting the session values into a JavaScript object
var sessionData = {
    username: "<?php echo $_SESSION['auth_username']; ?>",
    password: "<?php echo $_SESSION['auth_password']; ?>"
};
// Now you can use sessionData to set session storage values in JavaScript
sessionStorage.setItem('username', sessionData.username);
sessionStorage.setItem('password', sessionData.password);
</script>
<!--for POCOR-8127 ends-->