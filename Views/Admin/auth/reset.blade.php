@extends($templatePathAdminVendor.'layout_portable')

@section('main')

<form method="POST" action="{{ gp247_route_admin('vendor.password_request') }}"
    aria-label="{{ gp247_language_render('multi_vendor.password_reset') }}" class="space-y-4">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <x-gp247::input name="email" type="email" id="email" required autofocus
        :label="gp247_language_render('multi_vendor.email')"
        :value="old('email')"
        :error="$errors->first('email')" />

    <x-gp247::input name="password" type="password" id="password" required
        :label="gp247_language_render('multi_vendor.password')"
        :error="$errors->first('password')" />

    <x-gp247::input name="password_confirmation" type="password" id="password-confirm" required
        :label="gp247_language_render('multi_vendor.password_confirm')" />

    <x-gp247::button type="submit" variant="primary" size="lg" class="w-full">
        {{ gp247_language_render('multi_vendor.password_reset') }}
    </x-gp247::button>
</form>

@endsection
