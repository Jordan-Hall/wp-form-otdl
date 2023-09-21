jQuery(document).ready(function($) {
    // Invalidate download link using AJAX
    $('a[href*="invalidate="]').click(function(e) {
        e.preventDefault();
        let link = $(this);
        $.post(otdl_ajax_object.ajax_url, {
            action: 'otdl_invalidate_link',
            unique_key: link.attr('href').split('invalidate=')[1]
        }, function(response) {
            alert(response);
            link.parent().prev().text(current_time('mysql')); // Assuming the "Downloaded" column is right before the "Action" column
        });
    });
});
