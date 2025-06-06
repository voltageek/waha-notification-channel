<?php

namespace Cactus\Notifications\Channels\WAHA;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;


class WhatsappServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('waha-notification-channel')
            ->hasConfigFile('whatsapp')
            ->discoversMigrations()
            ->runsMigrations();
    }

    public function packageRegistered()
    {
        $this->app->bind(Whatsapp::class, static fn () => new Whatsapp(
            config('whatsapp.api_key'),
            config('whatsapp.url'),
            config('whatsapp.session'),
            app(HttpClient::class),
        ));

        Notification::resolved(static function (ChannelManager $service) {
            $service->extend('whatsapp', static fn ($app) => $app->make(WhatsappChannel::class));
        });
    }
}
