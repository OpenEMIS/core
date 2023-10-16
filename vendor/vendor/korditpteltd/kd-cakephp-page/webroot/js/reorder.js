$(document).ready(function() {
    Reorder.init();
});

var Reorder = {
    init: function() {
        // Sortable only when mouse over the arrows
        $("td.sorter").mousedown(function() {
            Reorder.enableSortable(this);
        });

        // Disable sortable on any other portion of the body if the mouse is move away
        $(document).on("mouseup", function(){
            Reorder.disableSortable();
        });
    },

    enableSortable: function(obj) {
        var originalOrder = Reorder.getOrder("td","data-row-id");

        var preventCollapse = function(e, ui) {
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        };

        // Sortable on tbody
        var url = $(obj).closest('table').attr('url');
        var tbody = $(obj).closest('tbody');
        tbody.sortable({
            forcePlaceholderSize: true,
            helper: preventCollapse,
            cursor: "none",
            axis: "y",
            stop: function(event, ui){
                if (url) {
                    currentOrder = Reorder.getOrder("td","data-row-id");
                    if (!Reorder.compare(currentOrder,originalOrder)){
                        $.ajax({
                            cache: false,
                            url: url,
                            type: "POST",
                            data: {
                                ids: JSON.stringify(currentOrder)
                            },
                            traditional: true,
                            success: function(data){
                                originalOrder = currentOrder;
                            }
                        });
                    }
                }
            }
        });

        // Re-enable the sortable if the mouse has already been release
        tbody.sortable('enable');
    },

    disableSortable: function() {
        if ($("#sortable tbody").hasClass('ui-sortable')) {
            $("#sortable tbody").sortable('disable');
        }
    },

    compare: function(array1, array2) {
        if (array1.length==array2.length) {
            for (i = 0; i<array1.length; i++) {
                if (!(array1[i] == array2[i])) {
                    return false;
                }
            }
            return true;
        }
    },

    getOrder: function(htmlTag, attributeName) {
        return $(htmlTag).map(function(){
            return $(this).attr(attributeName);
        }).get();
    },
};
