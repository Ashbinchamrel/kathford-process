<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorPortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = Vendor::find($request->session()->get('vendor_portal_id'));

        if (! $vendor || ! $vendor->is_active || ! $vendor->portal_enabled) {
            $request->session()->forget('vendor_portal_id');
            return redirect()->route('vendor.portal.login')
                ->withErrors(['email' => 'Please sign in to access the vendor portal.']);
        }

        $request->attributes->set('portal_vendor', $vendor);
        return $next($request);
    }
}
