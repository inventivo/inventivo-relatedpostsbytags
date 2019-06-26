jQuery( document ).ready(function() {
    function equalHeight(element) {
        jQuery(element).matchHeight();
    };

    if(jQuery('.equal').length) {
        equalHeight('.equal');
        jQuery(window).resize(function () {
            equalHeight('.equal');
        });
    }
});