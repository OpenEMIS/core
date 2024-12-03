<!--POCOR-8652 start-->
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded event fired');

        // Get the current URL
        const currentUrl = window.location.href;
        console.log('Current URL:', currentUrl);

        // Check if the URL contains "Configurations/Themes"
        if (currentUrl.includes('Configurations/Themes')) {
            console.log('"Configurations/Themes" found in URL.');

            // Find the select element with name="Themes[value]"
            let selectElement = document.querySelector('select[name="Themes[value]"]');
            if (selectElement) {
                console.log('Select element found:', selectElement);

                let options = selectElement.options;
                for (let i = 0; i < options.length; i++) {
                    let hexValue = options[i].value;
                    console.log(`Option ${i} - Original Value: ${hexValue}`);

                    // Ensure hex value starts with '#' for valid color code
                    if (!hexValue.startsWith('#')) {
                        hexValue = '#' + hexValue;
                        console.log(`Updated Hex Value: ${hexValue}`);
                    }

                    // Set the background color of the option
                    try {
                        options[i].style.backgroundColor = hexValue;

                        // Set the text color based on the background brightness
                        options[i].style.color = (parseInt(hexValue.replace('#', ''), 16) > 0xffffff / 2) ? 'black' : 'white';
                        console.log(`Option ${i} - Background: ${options[i].style.backgroundColor}, Text Color: ${options[i].style.color}`);
                    } catch (error) {
                        console.error(`Error setting styles for option ${i}:`, error);
                    }
                }
            } else {
                console.error('Select element not found. Ensure the element exists in the DOM.');
            }
        } else {
            console.log('"Configurations/Themes" not found in the URL.');
        }
    });
</script>
<!--POCOR-8652 end-->