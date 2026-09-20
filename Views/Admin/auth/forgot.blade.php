@extends($templatePathAdminVendor.'layout_portable')

@section('main')

<form method="POST" action="{{ gp247_route_admin('vendor.postForgot') }}" id="gp247-form-process" class="space-y-4">
    @csrf

    <x-gp247::input name="email" type="email" id="email" required
        :label="gp247_language_render('customer.email')"
        :placeholder="gp247_language_render('multi_vendor.email')"
        :value="old('email')"
        :error="$errors->first('email')" />

    <x-gp247::button type="submit" variant="primary" size="lg" class="w-full">
        {{ gp247_language_render('action.submit') }}
    </x-gp247::button>
</form>

@endsection
