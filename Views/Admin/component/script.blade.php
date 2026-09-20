{{--
    Shared vendor-admin scripts, jQuery-free.

    v1 relied on jQuery + iCheck + select2 + SweetAlert + a jQuery file-manager
    plugin, none of which core 2.x loads. This file keeps the SAME global names the
    vendor screens call (alertJs / alertMsg / format_number / the `.lfm` picker) so
    those screens keep working, but implements them with plain DOM APIs and the
    admin shell's `notify` toast channel.
--}}
<script>
(function () {
    'use strict';

    /**
     * Toast helpers. Same signatures the v1 SweetAlert wrappers had — `note` is
     * appended to the message because the toast UI has no separate subtitle.
     */
    window.alertJs = function (type, msg) {
        window.mvp.notify(type || 'error', msg || '');
    };

    window.alertMsg = function (type, msg, note) {
        window.mvp.notify(type || 'error', [msg, note].filter(Boolean).join(' — '));
    };

    window.alertConfirm = function (type, msg) {
        window.mvp.notify(type || 'warning', msg || '');
    };

    /** Thousands-separated integer, unchanged from v1. */
    window.format_number = function (n) {
        return n.toFixed(0).replace(/./g, function (c, i, a) {
            return i > 0 && c !== '.' && (a.length - i) % 3 === 0 ? ',' + c : c;
        });
    };

    /**
     * File-manager picker.
     *
     * Delegated from document so it also covers markup inserted after load. Keeps
     * the v1 data-attribute contract: data-input (target field id), data-preview
     * (preview container id), data-type (LFM folder category).
     */
    var LFM_PREFIX = @js(gp247_route_admin('vendor_admin.home').'/'.config('lfm.url_prefix'));

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.lfm');
        if (!trigger) {
            return;
        }
        e.preventDefault();

        var type = trigger.dataset.type || 'other';
        var input = document.getElementById(trigger.dataset.input);
        var preview = document.getElementById(trigger.dataset.preview);

        window.open(LFM_PREFIX + '?type=' + encodeURIComponent(type), @js(gp247_language_render('admin.file_manager')), 'width=900,height=600');

        // LFM calls this back from the popup with the picked items.
        window.SetUrl = function (items) {
            var paths = items.map(function (item) { return item.url; }).join(',');

            if (input) {
                input.value = paths;
                // Native events so both plain listeners and Livewire see the change.
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (preview) {
                preview.innerHTML = '';
                items.forEach(function (item) {
                    var img = document.createElement('img');
                    img.src = item.thumb_url;
                    img.className = 'max-h-24 w-auto rounded border border-gray-200 dark:border-gray-700';
                    preview.appendChild(img);
                });
                preview.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };
    });

    /**
     * "Select all rows" toggle used by the list screens: flips every row checkbox
     * inside the nearest table and swaps the button's own icon.
     */
    document.addEventListener('click', function (e) {
        var button = e.target.closest('.grid-select-all');
        if (!button) {
            return;
        }

        var scope = button.closest('[data-grid]') || document;
        // WHY [data-id] as well as the legacy class: the x-gp247 checkbox
        // component drops a `class` attribute passed to it (it owns the input's
        // classes) but keeps data-*, so row checkboxes are identified by data-id.
        // (Do NOT write the component tag in angle brackets here — Blade parses
        //  it as a component even inside a JS comment and breaks compilation.)
        var boxes = scope.querySelectorAll('input[type="checkbox"][data-id], .grid-row-checkbox');
        var check = button.dataset.checked !== '1';

        boxes.forEach(function (box) {
            box.checked = check;
            box.dispatchEvent(new Event('change', { bubbles: true }));
        });

        button.dataset.checked = check ? '1' : '0';
        var icon = button.querySelector('.far');
        if (icon) {
            icon.classList.toggle('fa-check-square', check);
            icon.classList.toggle('fa-square', !check);
        }
    });
})();
</script>
