define(['jquery'], function($) {
    return {
        init: function() {

            $('.collapse').on('show.bs.collapse', function () {
                const icon = $(this).prev().find('.fa-chevron-down');
                icon.addClass('rotate');
            });

            $('.collapse').on('hide.bs.collapse', function () {
                const icon = $(this).prev().find('.fa-chevron-down');
                icon.removeClass('rotate');
            });

        }
    };
});