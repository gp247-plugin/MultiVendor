@extends($templatePathAdminVendor.'layout_portable')

@section('main')

@php
    $country = old('country', $vendor['country'] ?? '');
@endphp

<form action="{{ $url_action }}" method="post" accept-charset="UTF-8" id="form-main"
    enctype="multipart/form-data" class="space-y-4">
    @csrf

    <x-gp247::input name="first_name" id="first_name"
        :label="gp247_language_render('multi_vendor.first_name')"
        :value="old('first_name')"
        :error="$errors->first('first_name')" />

    <x-gp247::input name="last_name" id="last_name"
        :label="gp247_language_render('multi_vendor.last_name')"
        :value="old('last_name')"
        :error="$errors->first('last_name')" />

    <x-gp247::input name="phone" id="phone"
        :label="gp247_language_render('multi_vendor.phone')"
        :value="old('phone')"
        :error="$errors->first('phone')" />

    <x-gp247::input name="postcode" id="postcode"
        :label="gp247_language_render('multi_vendor.postcode')"
        :value="old('postcode')"
        :error="$errors->first('postcode')" />

    <x-gp247::input name="email" id="email"
        :label="gp247_language_render('multi_vendor.email')"
        :value="old('email')"
        :error="$errors->first('email')" />

    <x-gp247::input name="address1" id="address1"
        :label="gp247_language_render('multi_vendor.address1')"
        :value="old('address1')"
        :error="$errors->first('address1')" />

    <x-gp247::input name="address2" id="address2"
        :label="gp247_language_render('multi_vendor.address2')"
        :value="old('address2')"
        :error="$errors->first('address2')" />

    <x-gp247::searchable-select name="country"
        :label="gp247_language_render('multi_vendor.country')"
        :placeholder="gp247_language_render('multi_vendor.country')"
        :options="collect($countries)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
        :value="$country"
        :error="$errors->first('country')" />

    <x-gp247::input name="password" id="password" type="password"
        :label="gp247_language_render('multi_vendor.password')"
        :error="$errors->first('password')" />

    <x-gp247::input name="password_confirmation" id="password_confirmation" type="password"
        :label="gp247_language_render('multi_vendor.password_confirm')"
        :error="$errors->first('password_confirmation')" />

    <x-gp247::input name="store_code" id="store_code"
        :label="gp247_language_render('multi_vendor.store_code')"
        :placeholder="gp247_language_render('multi_vendor.store_code_placeholder')"
        :value="old('store_code')"
        :error="$errors->first('store_code')" />

    <x-gp247::button type="submit" variant="primary" size="lg" class="w-full">
        {{ gp247_language_render('multi_vendor.title_register') }}
    </x-gp247::button>

    <div class="pt-2 text-center text-sm">
        <a href="{{ gp247_route_admin('vendor.login') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
            <i class="fa fa-caret-right"></i> {{ gp247_language_render('multi_vendor.login') }}
        </a>
    </div>
</form>

@endsection
