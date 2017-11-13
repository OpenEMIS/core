<?php
$version = file_get_contents('../version');
?>

    <footer>
        <?= 'Copyright' ?> &copy; <?= date('Y') ?>  OpenEMIS. <?= 'All rights reserved.' ?> | <?= 'Version' . ' ' . $version ?>
    </footer>
</body>
</html>
