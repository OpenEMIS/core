<?php
$ngController = '';
$wrapperClass = 'wrapper';
$crumbs = [];
?>

<script type="text/javascript">
window.onload = function(e) {
    Page.querystringValue = Page.getParamValue('querystring');
}

var Page = {
    querystringValue: ''
};

Page.updateUrlParamValue = function(key, value) {
    var url = window.location.href;
    var regex = new RegExp("\\b(" + key + "=).*?(&|$)\\b");

    if (url.indexOf("?") == -1) { // no querystring params
        url += "?" + key + "=" + value;
    } else {
        if (url.indexOf(key) == -1) { // key not exists in querystring
            url += "&" + key + "=" + value;
        } else {
            url = url.replace(regex, '$1' + value + '$2');
        }
    }
    return url;
}

Page.removeUrlParam = function(key) {
    var href = window.location.href;
    var regex = new RegExp("\\b(" + key + "=).*?(&|$)\\b");
    url = href.replace(regex, '');
    if (url.substr(url.length - 1) == '?') { // if last character is '?' then remove it
        url = url.substr(0, url.length-1);
    }
    return url;
}

Page.querystring = function(key, value) {
    if (value != null && value.trim().length == 0) {
        return;
    }
    var querystringValue = this.querystringValue;

    if (querystringValue != null) {
        querystringValue = JSON.parse(querystringValue.hexDecode());
    } else {
        querystringValue = {};
    }

    if (value == null) {
        delete querystringValue[key];
    } else {
        querystringValue[key] = value;
    }

    var count = 0;
    for(var prop in querystringValue) {
        if(querystringValue.hasOwnProperty(prop)) ++count;
    }
    if (count > 0) {
        querystringValue = JSON.stringify(querystringValue).hexEncode();
        window.location.href = this.updateUrlParamValue('querystring', querystringValue);
    } else {
        window.location.href = this.removeUrlParam('querystring');
    }
}

Page.getParamValue = function(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
}

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }
String.prototype.hexEncode = function() {
    var hex, i;
    var result = "";
    for (i=0; i<this.length; i++) {
        hex = this.charCodeAt(i).toString(16);
        result += ("000"+hex).slice(-4);
    }

    return result;
}

String.prototype.hexDecode = function() {
    var i;
    var hexes = this.match(/.{1,4}/g) || [];
    var result = "";
    for(i = 0; i<hexes.length; i++) {
        result += String.fromCharCode(parseInt(hexes[i], 16));
    }

    return result;
}
</script>

<div class="content-wrapper" <?= $ngController; ?>>
    <?= $this->element('Page.breadcrumb') ?>

    <div class="page-header">
        <h2 id="main-header"><?= $header ?></h2>
        <div class="toolbar toolbar-search">
            <?= $this->fetch('toolbar') ?>
        </div>
    </div>

    <div class="<?= $wrapperClass ?>">
        <div class="wrapper-child">
            <?= $this->fetch('contentBody') ?>
        </div>
    </div>
</div>

<?php
$this->Page->afterRender();
?>
