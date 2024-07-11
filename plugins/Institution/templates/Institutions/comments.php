<?= $this->Html->script('app/components/alert/alert.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/comments/institutions.comments.svc', ['block' => true]); ?>
<?= $this->Html->script('Institution.angular/comments/institutions.comments.ctrl', ['block' => true]); ?>


<script>
// Assume you're outputting the session values into a JavaScript object
var sessionData = {
    username: "<?php echo 'admin' ?>",
    password: "<?php echo 'ZGVtbw==' ?>"
};

// Now you can use sessionData to set session storage values in JavaScript
	sessionStorage.setItem('username', sessionData.username);
	sessionStorage.setItem('password', sessionData.password);

	localStorage.setItem('encoded_url', '<?php echo $encodedUrl;?>');
	localStorage.setItem('institutionId', '<?php echo $institutionId;?>');
	localStorage.setItem('institutionClassId', '<?php echo $institutionClassId;?>');
	localStorage.setItem('reportCardId', '<?php echo $reportCardId;?>');
	localStorage.setItem('institutionName', '<?php echo $institutionName;?>');
	localStorage.setItem('baseUrl', '<?php echo $baseUrl;?>');

</script>

<div>
	<?= $this->element('OpenEmis.breadcrumbs') ?>
    <app-root></app-root>
    <?php
        echo $this->Html->script(BUILD_MAIN);
        echo $this->Html->script(BUILD_POLYFILLS);
        echo $this->Html->script(BUILD_RUNTIME);
        echo $this->Html->script(BUILD_SCRIPTS);
        echo $this->Html->css(STYLE_GUIDE);
    ?>
</div>