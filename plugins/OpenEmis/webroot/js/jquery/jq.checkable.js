//iCheck v.1.0.0

//Checkbox, Radio and Checkbox/Radio in Tables
var Checkable = {
    init: function() {
        this.initTableCheckable();
    },

    initTableCheckable: function() {
        if ($.fn.tableCheckable) {
            $('.table-checkable')
                .tableCheckable()
                .on('masterChecked', function(event, master, slaves) {
                    if ($.fn.iCheck) {
                        $(slaves).iCheck('update');
                    }
                })
                .on('slaveChecked', function(event, master, slave) {
                    if ($.fn.iCheck) {
                        $(master).iCheck('update');
                    }
                });
        }
    }
};
