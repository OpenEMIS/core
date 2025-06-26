<script>
// Now you can use sessionData to set session storage values in JavaScript
    localStorage.removeItem('institution_id');
    localStorage.removeItem('encoded_url');
    localStorage.removeItem('institutionName');
    localStorage.removeItem('institutionIndexUrl');
    localStorage.removeItem('baseUrl');
    sessionStorage.removeItem('username');
    sessionStorage.removeItem('password');

    sessionStorage.setItem('nbn', '<?php echo $user;?>');
    sessionStorage.setItem('pbn', '<?php echo $pass;?>');
    localStorage.setItem('encoded_url', '<?php echo $url;?>');
    localStorage.setItem('institutionName', '<?php echo $institutionName;?>');
    localStorage.setItem('institution_id', '<?php echo $institution_id;?>');
    localStorage.setItem('institutionIndexUrl', '<?php echo $institutionIndexUrl;?>');
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
