/*
 * MultiVendor admin helpers (core 2.x).
 *
 * Replaces the jQuery/AdminLTE glue the plugin used on core 1.x ($.ajax,
 * $.pjax, iCheck, bootstrap-switch, x-editable, SweetAlert). Core 2.x loads no
 * jQuery, so these are plain fetch + the admin shell's own `notify` event
 * (ADR-005) that <x-gp247::toast> already listens for.
 *
 * Loaded from the plugin's own public folder; see resources/assets/README.md.
 */
(function () {
    'use strict';

    // WHY window.mvpCsrfToken first: core's admin layout renders no
    // <meta name="csrf-token"> (Livewire signs its own requests), so the plugin
    // publishes the token itself from Views/Admin/component/assets.blade.php.
    // The meta lookup stays as a fallback for any host layout that does ship one.
    function csrf() {
        if (window.mvpCsrfToken) {
            return window.mvpCsrfToken;
        }
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Show a toast through the admin shell's global notification channel.
     * type: success | error | warning | info
     */
    function notify(type, message) {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { type: type, message: message },
        }));
    }

    /**
     * POST form-encoded data and parse the JSON envelope the plugin's
     * controllers return: {error: 0|1, msg: string, ...}.
     *
     * Resolves with the parsed body; rejects only on transport/parse failure so
     * callers can tell "server said no" from "request never landed".
     */
    function post(url, data) {
        var body = new FormData();
        body.append('_token', csrf());
        Object.keys(data || {}).forEach(function (key) {
            body.append(key, data[key]);
        });

        return fetch(url, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (response) {
            return response.json();
        });
    }

    /**
     * POST and report the outcome as a toast — the shape almost every screen
     * needs. Returns the parsed body so callers can react further.
     * `onSuccess` runs only when the server reported error === 0.
     */
    function postAndNotify(url, data, successMessage, onSuccess) {
        return post(url, data).then(function (body) {
            if (parseInt(body.error, 10) === 0) {
                notify('success', successMessage || body.msg || '');
                if (typeof onSuccess === 'function') {
                    onSuccess(body);
                }
            } else {
                notify('error', body.msg || '');
            }
            return body;
        }).catch(function (e) {
            notify('error', String(e));
        });
    }

    window.mvp = {
        csrf: csrf,
        notify: notify,
        post: post,
        postAndNotify: postAndNotify,
    };
})();
