{{--
    One live-editing settings field (TailAdmin).

    Replaces the v1 x-editable / iCheck widgets, which needed jQuery + Bootstrap —
    both gone in core 2.x. Editing persists immediately through mvp.postAndNotify(),
    the same "change one field, save one field" contract the v1 screens had, and the
    same one core's own <ConfigForm> screens use.

    @param string $url      endpoint receiving {storeId, name, value}
    @param mixed  $storeId  store scope passed straight through to the endpoint
    @param string $name     field/config key
    @param string $type     text|number|password|select|checklist|bool
    @param mixed  $value    current value ("checklist" expects a JSON array string)
    @param array  $options  value => label map, for select / checklist
    @param array  $extra    extra key => value pairs merged into the POST body
                            (e.g. the record id an endpoint expects as `pk`)
--}}
@php
    $type    = $type ?? 'text';
    $options = $options ?? [];
    $value   = $value ?? '';

    $inputClass = 'w-full rounded-lg border border-gray-300 px-2 py-1 text-sm '
        . 'focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 '
        . 'dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';

    // "checklist" is stored as a JSON array (see gp247_captcha_page()).
    $checked = [];
    if ($type === 'checklist') {
        $decoded = json_decode((string) $value, true);
        $checked = is_array($decoded) ? $decoded : [];
    }

    $savedMsg = gp247_language_render('admin.msg_change_success');
    $extra = $extra ?? [];
@endphp

<div x-data="{
        save(value) {
            mvp.postAndNotify(
                @js($url),
                Object.assign({ storeId: @js($storeId), name: @js($name), value: value }, @js($extra)),
                @js($savedMsg)
            );
        },
    }">

    @if ($type === 'bool')
        <x-gp247::checkbox name="{{ $name }}" value="1"
            :checked="(bool) $value"
            x-on:change="save($event.target.checked ? 1 : 0)" />

    @elseif ($type === 'select')
        <select class="{{ $inputClass }}" x-on:change="save($event.target.value)">
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ (string) $value === (string) $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
            @endforeach
        </select>

    @elseif ($type === 'checklist')
        {{-- Multi-select stored as a JSON array, matching the v1 x-editable "checklist". --}}
        <div x-data="{ picked: @js(array_values($checked)) }" class="flex flex-col gap-1">
            @foreach ($options as $optValue => $optLabel)
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <x-gp247::checkbox x-model="picked" value="{{ $optValue }}"
                        x-on:change="$nextTick(() => save(JSON.stringify(picked)))" />
                    <span>{{ $optLabel }}</span>
                </label>
            @endforeach
        </div>

    @elseif ($type === 'password')
        {{-- Secrets never render as readable text; autocomplete off keeps the
             browser from offering the admin's own saved passwords. --}}
        <input type="password" autocomplete="new-password" value="{{ $value }}"
            class="{{ $inputClass }}" x-on:change="save($event.target.value)">

    @else
        <input type="{{ $type === 'number' ? 'number' : 'text' }}" value="{{ $value }}"
            class="{{ $inputClass }}" x-on:change="save($event.target.value)">
    @endif
</div>
