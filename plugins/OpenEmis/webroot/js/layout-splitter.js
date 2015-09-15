/* SPLITTER JS v.1.0.0 */
$(document).ready(function() {
    /* jqx starts here */
});

function initNavigation() {
    /*$('#jqxButton').hide();*/
    var isRTL = ($('.rtl #main-splitter').length > 0) ? true : false;
    var rtlClassName = (isRTL) ? ".rtl " : "";
    var panelId = (isRTL) ? 1 : 0;
    var panelsObject = getPanelsState(isRTL);
    console.log(JSON.stringify(panelsObject.data));
    $('#main-splitter').jqxSplitter({
        'orientation': 'vertical',
        width: '100%',
        panels: panelsObject.data
    });
    $('#main-splitter').jqxSplitter('refresh');
    $('#main-splitter').on('resize', function(event) {
        var panels = event.args.panels;

        console.log(panels[panelId].size);
        console.log(panelId);
        /*show and hide button*/
        if (panels[panelId].size <= 120) {
            $(rtlClassName + '#jqxButton').show();
            /*$(rtlClassName + '#jqxButton i').addClass('menu-nudge');*/
        } else {
            $(rtlClassName + '#jqxButton').hide();
            /*$(rtlClassName + '#jqxButton i').removeClass('menu-nudge');*/
        }
        /*store updated panel settings*/
        storePanelsState(panels);
    });

    /*on click event*/
    $('#jqxButton').on('click', function() {
        checkNavToggle(isRTL);
    })

    /*on first load*/
    loadNavToggle(isRTL);
}

function checkNavToggle(isRTL) {
    var rtlClassName = (isRTL) ? ".rtl " : "";

    var angleDefaultOpenIcon = (isRTL) ? "icon-rotate-right" : "icon-rotate-left";
    var angleDefaultCloseIcon = (isRTL) ? "icon-rotate-left" : "icon-rotate-right";

    var sideNavOpen = getSideNavState();
    var panels = $(rtlClassName + '#main-splitter').jqxSplitter('panels');
    $(rtlClassName + '#main-splitter').jqxSplitter({
        panels: panels
    });

    if (!$(rtlClassName + '#jqxButton i').hasClass('animate')) {
        $(rtlClassName + '#jqxButton i').addClass('animate');
    }

    if (sideNavOpen == 'open') {
        sideNavOpen = 'close';
        storeSideNavState(sideNavOpen);
        $(rtlClassName + '#main-splitter').jqxSplitter('collapse');
        if ($(rtlClassName + '#jqxButton i').hasClass(angleDefaultOpenIcon)) {
            $(rtlClassName + '#jqxButton i').removeClass(angleDefaultOpenIcon);
        }
        $(rtlClassName + '#jqxButton i').addClass(angleDefaultCloseIcon);
    } else {
        sideNavOpen = 'open';
        storeSideNavState(sideNavOpen);
        $(rtlClassName + '#main-splitter').jqxSplitter('expand');
        if ($(rtlClassName + '#jqxButton i').hasClass(angleDefaultCloseIcon)) {
            $(rtlClassName + '#jqxButton i').removeClass(angleDefaultCloseIcon);
        }
        $(rtlClassName + '#jqxButton i').addClass(angleDefaultOpenIcon);
    }

    /*store current panel settings*/
    storePanelsState(panels);
}

function loadNavToggle(isRTL) {
    var rtlClassName = (isRTL) ? ".rtl " : "";
    var sideNavOpen = getSideNavState();

    var angleDefaultOpenIcon = (isRTL) ? "icon-rotate-right" : "icon-rotate-left";
    var angleDefaultCloseIcon = (isRTL) ? "icon-rotate-left" : "icon-rotate-right";

    if (sideNavOpen == 'open') {
        $(rtlClassName + '#main-splitter').jqxSplitter('expand');
        $(rtlClassName + '#jqxButton i').addClass(angleDefaultOpenIcon);
    } else {
        $(rtlClassName + '#main-splitter').jqxSplitter('collapse');
        $(rtlClassName + '#jqxButton i').addClass(angleDefaultCloseIcon);
    }
}

function storePanelsState(refObject) {
    var lastPanelState = [];
    lastPanelState.push({
        size: refObject[0].size,
        min: refObject[0].min,
        collapsible: refObject[0].collapsible
    });
    lastPanelState.push({
        size: refObject[1].size,
        min: refObject[1].min,
        collapsible: refObject[1].collapsible
    });

    var type = ($('.rtl #main-splitter').length > 0) ? "RTL" : "LTR";
    var newRefObject = {
        type: type,
        data: lastPanelState
    };

    //post panel width size to server 
    $.ajax({
            method: "POST",
            url: "App/setJqxSpliterSize",
            data: {
                panelLeft: refObject[0].size,
                panelRight: refObject[1].size,
            }
        });


    var textValue = JSON.stringify(newRefObject);
    window.sessionStorage.setItem('panelState', textValue);
}

function getPanelsState(isRTL) {
    var refObject = window.sessionStorage.getItem('panelState');
    if (refObject == null) {
        /*load default when the data is not found*/
        refObject = getDefaultPanelSettings(isRTL);
    } else {
        refObject = JSON.parse(refObject);
        var type = ($('.rtl #main-splitter').length > 0) ? 'RTL' : "LTR";
        if (type != refObject.type) {
            var isRTL = ($('.rtl #main-splitter').length > 0) ? true : false;
            refObject = getDefaultPanelSettings(isRTL);
        }
    }
    return refObject;
}

function getDefaultPanelSettings(isRTL) {
    var refObject = {};
    var navWidth = 180;
    if (isRTL) { /*id is RTL load RTL settings*/
        var paneWidth = $(window).width() - navWidth;
        refObject = {
            type: 'RTL',
            data: [{
                min: '60%',
                size: paneWidth,
                collapsible: false
            }, {
                min: 100,
                size: navWidth,
                collapsible: true
            }]
        };
    } else {
        refObject = {
            type: 'LTR',
            data: [{
                size: navWidth,
                min: 100,
                collapsible: true
            }, {
                min: '60%',
                size: '90%',
                collapsible: false
            }]
        };
    }

    return refObject;
}

function storeSideNavState(textValue) {
    window.sessionStorage.setItem('toggleState', textValue);
}

function getSideNavState() {
    var refObject = window.sessionStorage.getItem('toggleState');
    if (refObject == null) {
        /*default when the data is not found*/
        refObject = 'open';
    }
    return refObject;
}
