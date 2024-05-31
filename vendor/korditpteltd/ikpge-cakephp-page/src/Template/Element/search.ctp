<?php
$searchText = $this->Page->getQueryString('search');
?>

<div class="search">
    <div class="input-group">
        <div class="input text">
            <input type="text" id="search" value="<?= $searchText ?>" class="form-control search-input focus" data-input-name="Search[searchField]" placeholder="Search" onkeypress="if (event.keyCode == 13) Page.querystring('search', this.value, this)">
        </div>

        <span class="input-group-btn">
            <button class="btn btn-xs btn-reset" type="button" onclick="Page.querystring('search', null, this)"><i class="fa fa-close"></i></button>
            <button class="btn btn-default btn-xs" data-toggle="tooltip" data-placement="bottom" type="button" onclick="Page.querystring('search', document.getElementById('search').value, this)" data-original-title="Search">
                <i class="fa fa-search"></i>
            </button>

            <!-- <button id="search-toggle" class="btn btn-default btn-xs" ng-class="selectedState" data-toggle="tooltip" data-placement="bottom" type="button" ng-click="toggleAdvancedSearch()" data-original-title="Advanced Search">
                <i class="fa fa-search-plus"></i>
            </button> -->
        </span>
    </div>
</div>
