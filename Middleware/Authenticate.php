<?php

namespace App\GP247\Plugins\MultiVendor\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $redirectTo = gp247_route_admin('vendor.login');
        
        // Check if user is guest
        if (vendor()->guest() && !$this->shouldPassThrough($request)) {
            return redirect()->guest($redirectTo);
        }
        
        // Check if logged in user has store_id
        if (vendor()->user() && empty(vendor()->user()->store_id) && !$this->shouldPassThrough($request)) {
            Auth::guard('vendor')->logout();
            return redirect()->guest($redirectTo)->withErrors([
                'email' => gp247_language_render('multi_vendor.store_id_required'),
            ]);
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should pass through verification.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function shouldPassThrough($request)
    {

        $routeName = $request->route()->getName();
        $excepts = [
            'vendor.login',
            'vendor.postLogin',
            'vendor.logout',
            'vendor.forgot',
            'vendor.postForgot',
            'vendor.register',
            'vendor.postRegister',
            'vendor.password_reset',
            'vendor.password_request',
        ];
        return in_array($routeName, $excepts);

    }
}
