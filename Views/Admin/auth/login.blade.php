@extends($templatePathAdminVendor.'layout_portable')

@section('main')

@if (admin()->user())
    {{-- A staff admin session and a vendor session cannot coexist. --}}
    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-center text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
        {!! gp247_language_render('Plugins/MultiVendor::lang.admin.note_admin_login') !!}
        <a href="{{ gp247_route_admin('admin.logout') }}" class="font-semibold underline">CLICK HERE</a>
    </div>
@else

<form action="{{ gp247_route_admin('vendor.postLogin') }}" method="post" class="space-y-4">
    @csrf

    <x-gp247::input name="email"
        :label="gp247_language_render('multi_vendor.email')"
        :placeholder="gp247_language_render('multi_vendor.email')"
        :value="old('email')"
        :error="$errors->first('email')" />

    <x-gp247::input name="password" type="password"
        :label="gp247_language_render('multi_vendor.password')"
        :placeholder="gp247_language_render('multi_vendor.password')"
        :error="$errors->first('password')" />

    <x-gp247::checkbox name="remember" value="1"
        :label="gp247_language_render('multi_vendor.remember_me')"
        :checked="(bool) old('remember')" />

    <x-gp247::button type="submit" variant="primary" size="lg" class="w-full">
        {{ gp247_language_render('multi_vendor.login') }}
    </x-gp247::button>

    <div class="flex flex-col items-center gap-1 pt-2 text-sm">
        @if (gp247_config_global('MultiVendor_allow_register'))
            <a href="{{ gp247_route_admin('vendor.register') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                <i class="fa fa-caret-right"></i> {{ gp247_language_render('multi_vendor.title_register') }}
            </a>
        @endif

        <a href="{{ gp247_route_admin('vendor.forgot') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
            <i class="fa fa-caret-right"></i> {{ gp247_language_render('multi_vendor.password_forgot') }}
        </a>
    </div>
</form>

@endif

@endsection
