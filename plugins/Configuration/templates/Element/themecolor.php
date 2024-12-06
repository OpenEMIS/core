<!--POCOR 8652 add theme dropdown in edit page-->
<style>
        .custom-dropdown {
            position: relative;
            width: 200px;
        }

        .custom-dropdown select {
            display: none; 
        }

        .dropdown-selected {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 8px;
            cursor: pointer;
            text-align: center;
        }

        .dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            z-index: 10;
            display: none;
        }

        .dropdown-item {
            padding: 6px;
            cursor: pointer;
            text-align: center;
        }

        .dropdown-item:hover {
            background: #f1f1f1;
        }

        /* Footer styling */
        .dropdown-footer {
            border-top: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            background-color: #f9f9f9;
            cursor: pointer;
        }

        .dropdown-footer:hover {
            background-color: #e9e9e9;
        }
    </style>
<?php $colorListPath = WWW_ROOT . 'themecolor' . DS . 'color.php'; 
$colorListing = include($colorListPath);
?>
<div class="input select required">
    <label for="themes-value">Value</label>
    <div class="input-select-wrapper custom-dropdown">
        <div class="dropdown-selected">-- Select a Theme Color --</div>
        <div class="dropdown-list" id="themes-value">
            <?php
            foreach ($colorListing as $hexValue) {
            ?>
             <div class="dropdown-item" style="background-color : <?= "#".$hexValue ?>; color: <?= "#fff" ?>;"><?= "#".$hexValue ?></div>
            <?php } ?>
        </div>
        <select name="Themes[value]" id="themes-value">
        <?php
            foreach ($colorListing as $hexValue) {
            ?>
            <option value="<?= "#".$hexValue ?>"><?= "#".$hexValue ?></option>
            <?php } ?>
        </select>
    </div>
</div>

<script>
    // JavaScript for toggling the dropdown visibility
    document.addEventListener('DOMContentLoaded', function () {
        const dropdown = document.querySelector('.custom-dropdown');
        const selected = dropdown.querySelector('.dropdown-selected');
        const list = dropdown.querySelector('.dropdown-list');
        const items = dropdown.querySelectorAll('.dropdown-item');
        const originalSelect = dropdown.querySelector('select');
        selected.addEventListener('click', function () {
            list.style.display = 'block';
        });

        // Handle item selection
        items.forEach(function (item) {
            item.addEventListener('click', function () {
                const value = item.textContent;
                selected.textContent = value;
                selected.style.backgroundColor = getComputedStyle(item).backgroundColor;
                selected.style.color = getComputedStyle(item).color; // Set selected text color
                originalSelect.value = value; // Update hidden select value
                list.style.display = 'none'; // Close dropdown after selection
            });
        });

        // Close dropdown if clicking outside
        document.addEventListener('click', function (event) {
            if (!dropdown.contains(event.target)) {
                list.style.display = 'none';
            }
        });
    });
</script>
