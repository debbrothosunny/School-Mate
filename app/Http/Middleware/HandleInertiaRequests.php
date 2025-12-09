<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */

    public function share(Request $request): array
    {
        // Always call the parent share method first to merge existing props
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'contact_info' => $request->user()->contact_info,
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],
            // FIX APPLIED HERE: Sharing 'success' and 'error' flash messages
            'flash' => [
                // Your existing 'message' key
                'message' => fn () => $request->session()->get('message'),
                
                // NEW: Add 'success' to expose it to $page.props.flash.success
                'success' => fn () => $request->session()->get('success'),
                
                // Recommended: Also add 'error' for displaying global errors
                'error' => fn () => $request->session()->get('error'),
            ],
            // --- Notifications logic remains the same ---
            'unreadNotificationsCount' => fn () => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            'unreadNotifications' => fn () => $request->user() ? $request->user()->unreadNotifications : collect([]),
        ]);
    }
}