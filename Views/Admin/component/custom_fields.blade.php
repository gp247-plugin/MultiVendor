{{--
    Admin-defined custom fields for a plain (non-Livewire) form.

    v1 pulled in 'gp247-core::component.render_form_custom_field'; core 2.x
    removed that view (its replacement binds through Livewire's $wire, which
    these controller-rendered forms have no access to). This partial renders the
    same definitions as plain `fields[<code>]` inputs — the exact payload shape
    the plugin's controllers already validate and pass to
    gp247_custom_field_update().

    @param string      $type   custom-field type = the target table (e.g. shop_supplier)
    @param object|null $object the record being edited, for current values
--}}
@php
    // Definitions come from the helper rather than the controller: the
    // controllers call (new AdminCustomField)->getCustomField($type), which
    // resolves against the definition table itself and returns nothing usable.
    $customDefs = function_exists('gp247_custom_field_list') ? gp247_custom_field_list($type) : collect();

    $currentValues = ($object && method_exists($object, 'getCustomFields'))
        ? $object->getCustomFields()
        : collect();

    $inputCls = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm '
        . 'focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 '
        . 'dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

@if (is_countable($customDefs) && count($customDefs))
<div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ gp247_language_render('admin.custom_field.title') }}
    </p>

    <div class="space-y-3">
        @foreach ($customDefs as $field)
            @php
                $opts = json_decode($field->default ?? '', true) ?: [];
                $value = old('fields.'.$field->code, $currentValues[$field->code]->text ?? '');
                $error = $errors->first('fields.'.$field->code);
            @endphp
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ gp247_language_render($field->name) }}@if ($field->required) <span class="text-red-500">*</span>@endif
                </label>

                @switch($field->option)
                    @case('textarea')
                        <textarea name="fields[{{ $field->code }}]" rows="2" class="{{ $inputCls }}">{{ $value }}</textarea>
                        @break

                    @case('select')
                        <select name="fields[{{ $field->code }}]" class="{{ $inputCls }}">
                            <option value="">--</option>
                            @foreach ($opts as $optVal => $optLabel)
                                <option value="{{ $optVal }}" {{ (string) $value === (string) $optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                        @break

                    @case('checkbox')
                        @php $checkedValues = is_array($value) ? $value : array_filter(explode(',', (string) $value)); @endphp
                        <div class="flex flex-wrap gap-3">
                            @foreach ($opts as $optVal => $optLabel)
                                <x-gp247::checkbox :label="$optLabel"
                                    name="fields[{{ $field->code }}][]"
                                    value="{{ $optVal }}"
                                    :checked="in_array((string) $optVal, array_map('strval', $checkedValues), true)" />
                            @endforeach
                        </div>
                        @break

                    @case('radio')
                        <div class="flex flex-wrap gap-3">
                            @foreach ($opts as $optVal => $optLabel)
                                <label class="flex cursor-pointer select-none items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{-- WHY inline accent-color: the admin Tailwind bundle ships no
                                         accent-* utility, so this is set inline to render blue. --}}
                                    <input type="radio" name="fields[{{ $field->code }}]" value="{{ $optVal }}"
                                        {{ (string) $value === (string) $optVal ? 'checked' : '' }}
                                        style="accent-color:#2563eb" class="cursor-pointer">
                                    <span>{{ $optLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @default
                        {{-- Typed inputs (number/date/email/url/color…) use the option
                             name as the native input type; unknown/empty falls back to text. --}}
                        <input type="{{ $field->option ?: 'text' }}" name="fields[{{ $field->code }}]"
                            value="{{ $value }}" class="{{ $inputCls }}">
                @endswitch

                @if ($error)
                    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
