<?php //echo $this->Form->control('Themes.value', $attr);

?>
<!--POCOR-8652 start-->
<!-- <script type="text/javascript">
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
</script> -->
<!--POCOR-8652 end-->

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
    const selectElement = document.getElementById('themes-value');
    const options = selectElement.querySelectorAll('option');

    // Loop through all options in the select element
    options.forEach(option => {
        // Skip the placeholder option (empty value)
        if (option.value === "") return;

        // Create the correct option structure with style
        const hexValue = option.value;
        const contrastColor = getContrastColor(hexValue); // Assuming this function exists to determine contrast color

        // Set the correct styles and text for each option
        option.style.backgroundColor = hexValue;
        option.style.color = contrastColor;
        option.textContent = hexValue; // Set text content to the hex value
    });
});

// Function to calculate contrast color (for readability, use a simple light/dark contrast)
function getContrastColor(hex) {
    // Convert hex to RGB
    let r = parseInt(hex.slice(1, 3), 16);
    let g = parseInt(hex.slice(3, 5), 16);
    let b = parseInt(hex.slice(5, 7), 16);
    
    // Calculate the luminance
    let luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    
    // Return black or white based on luminance
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
}

</script>




