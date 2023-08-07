<?php 
$file = 'analytics.txt';
$path = WWW_ROOT.$file;

if (file_exists($path)) {
	$code = file_get_contents($path);
	if (!empty($code)) {
		$code = str_replace("\r", '', $code);
        $code = str_replace("\n", '', $code);
?>

<script type="text/javascript">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '<?= $code ?>', 'auto');
ga('send', 'pageview');
</script>

<?php
	} // close for 'if empty code'
} // close for 'if file exists'
?>
