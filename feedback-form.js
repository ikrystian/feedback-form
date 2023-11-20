jQuery(document).ready(function ($) {
    const AJAX_URL = `${window.location.origin}/wp-admin/admin-ajax.php`;
    $('#feedback-form').on('submit', (e) => {
        e.preventDefault();
        $('.feedback-form button[type=submit]').prop('disabled', true);
        const formData = $(this).serialize();
        $.ajax({
            url: `${window.location.origin}/wp-admin/admin-ajax.php`,
            type: 'POST',
            data: formData,
            success: (response) => {
                $('.feedback-form').text(response.data.message);
            },
            error: (error) => {
                console.error(error);
            }
        });
    });

    let page = 1;

    function loadEntries() {
        $.ajax({
            url: `${window.location.origin}/wp-admin/admin-ajax.php`,
            type: 'POST',
            data: {
                action: 'load_feedback_entries',
                page: page
            },
            success: (response) => {
                response.data.forEach(entry => {
                    $('#feedback-entries').append('<button class="show-person-details" data-id="' + entry.id + '">' + entry.first_name + '</button>');
                });
                page++;

            },
            error: (error) => {
                console.error(error);
            }
        });
    }

    if ($('.entries').length > 0) {
        loadEntries();
    }
    $('.page-number').click((e) => {
        e.preventDefault();
        const page = $(this).data('page');
        $.post(AJAX_URL, {action: 'load_feedback_entries', page}, (response) => {
            $('#feedback-entries').empty();
            response.data.forEach(el => {
                $('#feedback-entries').append('<button class="show-person-details" data-id="' + el.id + '">' + el.first_name + '</button>');
            })
        });
    });

    $('body').on('click', '.show-person-details', () => {
        const id = $(this).data('id');
        $.post(AJAX_URL, {action: 'load_feedback_details', id}, (response) => {
            const {first_name, last_name, email, subject, message} = response.data[0];
            const contentTemplate = `
                <p>${first_name}</p>
                <p>${last_name}</p> 
                <p>${email}</p> 
                <p>${subject}</p> 
                <p>${message}</p> 
            `;
            $('#feedback-details').html(contentTemplate)
        });
    });
});