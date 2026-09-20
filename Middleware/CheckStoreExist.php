<?php
namespace App\GP247\Plugins\MultiVendor\Middleware;
use Closure;
class CheckStoreExist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!\GP247\Core\Models\AdminStore::find(vendor()->user()->store_id) && !$this->shouldPassThrough($request)) {
            return redirect()->route('vendor_admin_store.index')->with(['error' => gp247_language_render('multi_vendor.update_info_store_msg')]);
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

        $routeName = $request->path();
        $excepts = [
            'vendor_admin/vendor_update',
        ];
        return in_array($routeName, $excepts);

    }
}
