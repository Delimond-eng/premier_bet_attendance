/* 
 *LIONNEL NAWEJ KAYEMBE
 */
(function(window, $) {
    'use strict';

    function getCsrfToken() {
        var m = $('meta[name="csrf-token"]').attr('content');
        return m || '';
    }

    function ajaxOptions(method, url, data) {
        var opts = {
            url: url,
            type: method,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            }
        };
        if (data !== undefined) {
            opts.contentType = 'application/json';
            opts.data = JSON.stringify(data);
        }
        return opts;
    }

    function ajaxGet(url, params) {
        var opts = {
            url: url,
            type: 'GET',
            dataType: 'json',
            data: params || {}
        };
        return $.ajax(opts);
    }

    function ajaxPost(url, data) {
        return $.ajax(ajaxOptions('POST', url, data));
    }

    function ajaxPut(url, data) {
        return $.ajax(ajaxOptions('PUT', url, data));
    }

    function ajaxDelete(url) {
        return $.ajax(ajaxOptions('DELETE', url));
    }

    function showAlert(message, type) {
        var map = { success: 'success', danger: 'error', info: 'info' };
        var icon = map[type] || 'info';
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                title: message || '',
                icon: icon,
                timer: 2500,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
            });
            return;
        }
        var cls = 'alert-' + (type || 'info');
        var $a = $('<div>').addClass('alert ' + cls + ' alert-dismissible').attr('role', 'alert');
        $a.text(message);
        $a.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
        $('.page-wrapper').prepend($a);
        setTimeout(function() { $a.fadeOut(300, function() { $a.remove(); }); }, 3500);
    }

    window.ProjectHelpers = {
        ajaxGet: ajaxGet,
        ajaxPost: ajaxPost,
        ajaxPut: ajaxPut,
        ajaxDelete: ajaxDelete,
        showAlert: showAlert
    };

})(window, jQuery);
