<footer>
    <?php if (!$footerText) : ?>
    <?= __('Copyright') ?> &copy; 2015 - <?= date('Y') ?>  <?=$footerBrand ?>. <?= __('All rights reserved.') ?>
    <?php else: ?>
    <?= str_replace('{{currentYear}}', date('Y'), $footerText) ?>
    <?php endif; ?>
    | <?= __('Version') . ' ' . $SystemVersion ?>

    <!--POCOR-8652-->
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Get the current URL
            const currentUrl = window.location.href;

            // Check if the URL contains "Configurations/Themes"
            if (currentUrl.includes('Configurations/Themes')) {
                // Now run your script
                let selectElement = document.querySelector('select[name="Themes[value]"]');
                if (selectElement) {
                    let options = selectElement.options;
                    for (let i = 0; i < options.length; i++) {
                        let hexValue = options[i].value;
                        
                        // Ensure hex value starts with '#' for valid color code
                        if (!hexValue.startsWith('#')) {
                            hexValue = '#' + hexValue;
                        }

                        // Set the background color of the option
                        options[i].style.backgroundColor = hexValue;

                        // Set the text color based on the background brightness (optional)
                        options[i].style.color = (parseInt(hexValue.replace('#', ''), 16) > 0xffffff / 2) ? 'black' : 'white';
                    }
                }
            }
        });
    </script>

</footer>