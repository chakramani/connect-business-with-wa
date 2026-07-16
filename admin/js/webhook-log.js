/* Business Messaging Hub — Webhook Log page behavior */
jQuery(function ($) {
    /* Raw payload modal */
    $(document).on('click', '.wabmh-raw-btn', function(){
        // Use .attr() not .data() — jQuery .data() auto-parses valid JSON into an object
        var raw = $(this).attr('data-raw');
        try { raw = JSON.stringify(JSON.parse(raw), null, 2); } catch(e){}
        $('#wabmh-modal-body').text(raw);
        $('#wabmh-modal-overlay').fadeIn(150);
    });
    $('#wabmh-modal-close').on('click', function(){ $('#wabmh-modal-overlay').fadeOut(150); });
    $('#wabmh-modal-overlay').on('click', function(e){ if(e.target===this) $('#wabmh-modal-overlay').fadeOut(150); });

    /* Search */
    $('#wabmh-wh-search').on('input', function(){
        var q = $(this).val().toLowerCase();
        $('#wabmh-wh-table tbody tr').each(function(){
            $(this).toggleClass('wabmh-hidden', q.length > 0 && $(this).text().toLowerCase().indexOf(q) === -1);
        });
    });
});
