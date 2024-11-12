$(document).ready(function () {
    PaginationHandler.init();
});

const PaginationHandler = {
    init: function () {
        this.paginationList = $('.pagination');
        this.form = $('#search-limit').closest('form');

        this.bindEvents();
    },

    bindEvents: function () {
        this.paginationList.on('click', (event) => {
            this.handlePaginationClick(event);
        });
    },

    handlePaginationClick: function (event) {
        // Check if the clicked element is an <a> tag
        if ($(event.target).is('a')) {
            event.preventDefault(); // Prevent default link behavior
            
            // Get the page number from the href attribute
            const page = new URL(event.target.href).searchParams.get('page');
            
            // Modify the form action to include the page number in the URL
            let actionUrl = this.form.attr('action') || window.location.href;
            actionUrl = actionUrl.replace(/([?&]page=)\d+/, `$1${page}`); // Replace existing page query

            // If no page query exists, append the page number
            if (!actionUrl.includes('page=')) {
                actionUrl += (actionUrl.includes('?') ? '&' : '?') + 'page=' + page;
            }

            // Set the new action URL
            this.form.attr('action', actionUrl);
            
            // Submit the form
            this.form.submit();
        }
    }
};
