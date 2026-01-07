<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('can visit subdomain routes with browser testing', function (): void {
    Route::domain('app.localhost')->group(function (): void {
        Route::get('/', fn (): string => '
            <html>
            <head><title>App Subdomain</title></head>
            <body>
                <h1>Welcome to App Subdomain</h1>
                <div id="content">This is the app subdomain content</div>
            </body>
            </html>
        ');
    });

    pest()->browser()->withHost('app.localhost');

    visit('/')
        ->assertSee('Welcome to App Subdomain')
        ->assertSeeIn('#content', 'This is the app subdomain content')
        ->assertTitle('App Subdomain');
});

it('handles dynamic subdomain routing', function (): void {
    Route::domain('{tenant}.localhost')->group(function (): void {
        Route::get('/dashboard', fn (): string => '
            <html>
            <head><title>Tenant Dashboard</title></head>
            <body>
                <h1>Dashboard for '.request()->route('tenant').'</h1>
                <div id="tenant-info">Tenant: '.request()->route('tenant').'</div>
                <nav>
                    <a href="/settings" id="settings-link">Settings</a>
                </nav>
            </body>
            </html>
        ');

        Route::get('/settings', fn (): string => '
            <html>
            <head><title>Tenant Settings</title></head>
            <body>
                <h1>Settings for '.request()->route('tenant').'</h1>
                <div id="tenant-settings">Configure '.request()->route('tenant').'</div>
            </body>
            </html>
        ');
    });

    pest()->browser()->withHost('acme.localhost');

    visit('/dashboard')
        ->assertSee('Dashboard for acme')
        ->assertSeeIn('#tenant-info', 'Tenant: acme')
        ->click('#settings-link')
        ->assertSee('Settings for acme')
        ->assertSeeIn('#tenant-settings', 'Configure acme');
});

it('works with Laravel Sail style subdomains', function (): void {
    // Simulate Laravel Sail subdomain routing pattern
    Route::domain('{subdomain}.localhost')->group(function (): void {
        Route::get('/api/health', fn (): array => [
            'status' => 'ok',
            'subdomain' => request()->route('subdomain'),
            'host' => request()->getHost(),
        ]);
    });

    pest()->browser()->withHost('api.localhost');

    visit('/api/health')
        ->assertSee('"status":"ok"')
        ->assertSee('"subdomain":"api"')
        ->assertSee('"host":"api.localhost"');
});

it('can switch between different subdomains in the same test', function (): void {
    Route::domain('{service}.localhost')->group(function (): void {
        Route::get('/status', function (): string {
            $service = request()->route('service');

            return "
                <html>
                <body>
                    <div id='service-name'>{$service}</div>
                    <div id='status'>online</div>
                </body>
                </html>
            ";
        });
    });

    // Test first service
    pest()->browser()->withHost('auth.localhost');
    visit('/status')
        ->assertSeeIn('#service-name', 'auth')
        ->assertSeeIn('#status', 'online');

    // Switch to second service
    pest()->browser()->withHost('billing.localhost');
    visit('/status')
        ->assertSeeIn('#service-name', 'billing')
        ->assertSeeIn('#status', 'online');

    // Switch to third service
    pest()->browser()->withHost('notifications.localhost');
    visit('/status')
        ->assertSeeIn('#service-name', 'notifications')
        ->assertSeeIn('#status', 'online');
});
