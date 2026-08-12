<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Group;
use App\Models\Package;
use App\Models\PackageSubscription;
use App\Models\User;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $usersCount = User::count();
        $adminsCount = Admin::count();
        $groupsCount = Group::count();
        $packagesCount = Package::count();
        $subscriptionsCount = PackageSubscription::count();
        $bannersCount = Banner::count();
        $couponsCount = Coupon::count();
        $contactsCount = Contact::count();

        $welcome = $this->dashboardWelcomeStats($usersCount, $groupsCount);
        $menus = $this->dashboardMenus(
            $usersCount,
            $adminsCount,
            $groupsCount,
            $packagesCount,
            $subscriptionsCount,
            $bannersCount,
            $couponsCount,
            $contactsCount
        );

        return view('dashboard.index', compact(
            'usersCount',
            'adminsCount',
            'groupsCount',
            'packagesCount',
            'subscriptionsCount',
            'bannersCount',
            'couponsCount',
            'contactsCount',
            'welcome',
            'menus'
        ));
    }

    protected function dashboardWelcomeStats(int $usersCount, int $groupsCount): array
    {
        $admin = auth('admin')->user();
        $now = Carbon::now()->locale(app()->getLocale());
        $hour = (int) $now->format('H');

        if ($hour >= 5 && $hour < 12) {
            $greeting = __('Good morning');
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = __('Good afternoon');
        } else {
            $greeting = __('Good evening');
        }

        return [
            'greeting' => $greeting,
            'name' => $admin->name,
            'day' => $now->format('d'),
            'month_year' => $now->translatedFormat('F Y'),
            'users_count' => $usersCount,
            'groups_count' => $groupsCount,
            'new_users_today' => User::query()->whereDate('created_at', $now->toDateString())->count(),
        ];
    }

    protected function dashboardMenus(
        int $usersCount,
        int $adminsCount,
        int $groupsCount,
        int $packagesCount,
        int $subscriptionsCount,
        int $bannersCount,
        int $couponsCount,
        int $contactsCount
    ): array {
        return [
            ['name' => __('users'), 'url' => route('admin.users.index'), 'icon' => 'icon-users', 'count' => $usersCount, 'group' => 'users'],
            ['name' => __('admins'), 'url' => route('admin.admins.index'), 'icon' => 'icon-user-check', 'count' => $adminsCount, 'group' => 'users'],
            ['name' => __('groups'), 'url' => route('admin.groups.index'), 'icon' => 'icon-users', 'count' => $groupsCount, 'group' => 'users'],
            ['name' => __('subscriptions'), 'url' => route('admin.subscriptions.index'), 'icon' => 'icon-check-circle', 'count' => $subscriptionsCount, 'group' => 'users'],
            ['name' => __('packages'), 'url' => route('admin.packages.index'), 'icon' => 'icon-package', 'count' => $packagesCount, 'group' => 'content'],
            ['name' => __('banners'), 'url' => route('admin.banners.index'), 'icon' => 'icon-image', 'count' => $bannersCount, 'group' => 'content'],
            ['name' => __('coupons'), 'url' => route('admin.coupons.index'), 'icon' => 'icon-tag', 'count' => $couponsCount, 'group' => 'content'],
            ['name' => __('contact us'), 'url' => route('admin.contacts.index'), 'icon' => 'icon-mail', 'count' => $contactsCount, 'group' => 'content'],
        ];
    }
}
