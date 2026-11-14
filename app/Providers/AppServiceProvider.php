<?php

namespace App\Providers;

use App\Services\PwaHomeService;
use App\Services\TopbarNotificationService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() && ($root = config('app.url'))) {
            URL::forceRootUrl($root);
        } elseif (request()->isSecure()) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', static function ($view): void {
            $user = auth()->user();
            $notifications = ['count' => 0, 'items' => []];

            if ($user && ! $user->isGuest()) {
                try {
                    $notifications = app(TopbarNotificationService::class)->build((int) $user->id, (string) $user->role);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $view->with('notifications', $notifications);
        });

        View::composer('pwa.partials.nav', static function ($view): void {
            $user = auth()->user();
            $zoomLive = false;
            $pwaMenu = [
                'userName' => '',
                'userInitials' => '',
                'userAvatar' => '',
                'roleLabel' => '',
                'courseTitle' => '',
                'isLearner' => false,
            ];

            if ($user) {
                try {
                    $homes = app(PwaHomeService::class);
                    $zoomLive = $homes->zoomIsLive($user);
                    $pwaMenu = array_merge($homes->headerFields($user), [
                        'isLearner' => $user->isLearner(),
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $view->with('zoomLive', $zoomLive)->with('pwaMenu', $pwaMenu);
        });
    }
}
