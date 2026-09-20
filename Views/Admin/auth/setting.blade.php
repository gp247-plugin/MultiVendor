@extends($templatePathAdminVendor.'layout')

@section('main')

@php
    $country = old('country', $vendor['country'] ?? '');
@endphp

<form action="{{ $url_action }}" method="post" accept-charset="UTF-8" id="form-main" enctype="multipart/form-data">
    @csrf

    <x-gp247::card :title="$title_description ?? ''">
        <div class="space-y-4">
            <x-gp247::input name="first_name" id="first_name"
                :label="gp247_language_render('multi_vendor.first_name')"
                :value="old('first_name', $vendor['first_name'] ?? '')"
                :error="$errors->first('first_name')" />

            <x-gp247::input name="last_name" id="last_name"
                :label="gp247_language_render('multi_vendor.last_name')"
                :value="old('last_name', $vendor['last_name'] ?? '')"
                :error="$errors->first('last_name')" />

            <x-gp247::input name="phone" id="phone"
                :label="gp247_language_render('multi_vendor.phone')"
                :value="old('phone', $vendor['phone'] ?? '')"
                :error="$errors->first('phone')" />

            <x-gp247::input name="postcode" id="postcode"
                :label="gp247_language_render('multi_vendor.postcode')"
                :value="old('postcode', $vendor['postcode'] ?? '')"
                :error="$errors->first('postcode')" />

            <x-gp247::input name="email" id="email"
                :label="gp247_language_render('multi_vendor.email')"
                :value="old('email', $vendor['email'] ?? '')"
                :error="$errors->first('email')" />

            <x-gp247::input name="address1" id="address1"
                :label="gp247_language_render('multi_vendor.address1')"
                :value="old('address1', $vendor['address1'] ?? '')"
                :error="$errors->first('address1')" />

            <x-gp247::input name="address2" id="address2"
                :label="gp247_language_render('multi_vendor.address2')"
                :value="old('address2', $vendor['address2'] ?? '')"
                :error="$errors->first('address2')" />

            <x-gp247::searchable-select name="country"
                :label="gp247_language_render('multi_vendor.country')"
                :placeholder="gp247_language_render('multi_vendor.country')"
                :options="collect($countries)->map(fn($v, $k) => ['id' => $k, 'label' => $v])->values()->all()"
                :value="$country"
                :error="$errors->first('country')" />

            <x-gp247::input name="password" id="password" type="text"
                :label="gp247_language_render('multi_vendor.password')"
                :value="old('password') ?? ''"
                :error="$errors->first('password')"
                :help="$vendor ? gp247_language_render('multi_vendor.admin.keep_password') : null" />
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between">
                <x-gp247::button type="reset" variant="warning">{{ gp247_language_render('action.reset') }}</x-gp247::button>
                <x-gp247::button type="submit" variant="primary">{{ gp247_language_render('action.submit') }}</x-gp247::button>
            </div>
        </x-slot:footer>
    </x-gp247::card>
</form>

@endsection
