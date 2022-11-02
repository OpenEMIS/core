angular.module('aggrid.locale.svc', ['kd.orm.svc'])
.service('AggridLocaleSvc',['$q', 'KdOrmSvc', function($q, KdOrmSvc) {
	var localeText = {
        // for filter panel
        page: 'Page',
        more: 'More',
        to: 'to',
        of: 'of',
        next: 'Next',
        last: 'Last',
        first: 'First',
        previous: 'Previous',
        loadingOoo: 'Loading...',
        // for set filter
        selectAll: 'Select Allen',
        searchOoo: 'Search...',
        blanks: 'Blank',
        // for number filter and text filter
        filterOoo: 'Filter...',
        applyFilter: 'Apply Filter...',
        // for number filter
        equals: 'Equals',
        notEqual: 'Not Equals',
        lessThanOrEqual: 'Less Than Or Equal',
        greaterThanOrEqual: 'Greater Than Or Equal',
        inRange:'In Range',
        lessThan: 'Less Than',
        greaterThan: 'Greater Than',
        // for text filter
        contains: 'Contains',
        startsWith: 'Starts with',
        endsWith: 'Ends with',
        // the header of the default group column
        group: 'Group',
        columns: 'Columns',
        rowGroupColumns: 'Pivot Columns',
        rowGroupColumnsEmptyMessage: 'Please drag columns to group',
        valueColumns: 'Value Columns',
        pivotMode: 'Pivot Mode',
        groups: 'Groups',
        values: 'Values',
        pivots: 'Pivots',
        valueColumnsEmptyMessage: 'Drag columns to aggregate',
        pivotColumnsEmptyMessage: 'Drag here to pivot',
        // other
        noRowsToShow: 'No rows',
        // enterprise menu
        pinColumn: 'Pin Column',
        valueAggregation: 'Value Agg',
        autosizeThiscolumn: 'Autosize this column',
        autosizeAllColumns: 'Autosize all columns',
        groupBy: 'Group by',
        ungroupBy: 'UnGroup by',
        resetColumns: 'Reset Those Columns',
        expandAll: 'Expand All',
        colpseAll: 'Colapse All',
        toolPanel: 'Tool Panel',
        // enterprise menu pinning
        pinLeft: 'Pin <<',
        pinRight: 'Pin >>',
        noPin: 'Do not Pin <>',
        // enterprise menu aggregation and status panel
        sum: 'Sum',
        min: 'Min',
        max: 'Max',
        first: 'First',
        st: 'st',
        none: 'None',
        count: 'Count',
        average: 'Average',
        // standard menu
        copy: 'Copy',
        ctrlC: 'Ctrl + C',
        paste: 'Paste',
        ctrlV: 'Ctrl + V'
    };

	return {
		getTranslatedGridLocale: function() {
			KdOrmSvc.base(angular.baseUrl);
			KdOrmSvc.init({translation: 'translate'});
            var success = function(response, deferred) {
                var translated = response.data.translated;
                deferred.resolve(translated);
            };
            return translation.translate(localeText, {success:success, defer: true});
		}
	}
}]);
