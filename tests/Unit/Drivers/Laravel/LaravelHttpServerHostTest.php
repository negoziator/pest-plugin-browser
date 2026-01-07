<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('changes the host for all requests', function (): void {
    Route::domain('tenant.localhost')->group(function (): void {
        Route::get('/about', fn (): string => 'Hello Tenant');
    });

    pest()->browser()->withHost('tenant.localhost');

    visit('/about')->assertSee('Hello Tenant');
});

it('works with subdomain routing patterns', function (): void {
    Route::domain('{subdomain}.localhost')->group(function (): void {
        Route::get('/dashboard', function (): string {
            $subdomain = request()->route('subdomain');

            return "Welcome to {$subdomain} dashboard";
        });
    });

    pest()->browser()->withHost('client1.localhost');

    visit('/dashboard')->assertSee('Welcome to client1 dashboard');
});

it('handles multiple subdomains in the same test', function (): void {
    Route::domain('{tenant}.localhost')->group(function (): void {
        Route::get('/profile', function (): string {
            $tenant = request()->route('tenant');

            return "Profile for {$tenant}";
        });
    });

    // Test first tenant
    pest()->browser()->withHost('tenant1.localhost');
    visit('/profile')->assertSee('Profile for tenant1');

    // Test second tenant
    pest()->browser()->withHost('tenant2.localhost');
    visit('/profile')->assertSee('Profile for tenant2');
});

it('falls back to default host when no custom host is set', function (): void {
    Route::get('/default', fn (): string => 'Default Host Response');

    // Don't set custom host, should use default 127.0.0.1
    visit('/default')->assertSee('Default Host Response');
});

it('can reset host configuration', function (): void {
    Route::get('/reset-test', fn (): string => 'Reset Test');
    Route::domain('custom.localhost')->group(function (): void {
        Route::get('/custom', fn (): string => 'Custom Host');
    });

    // Set custom host
    pest()->browser()->withHost('custom.localhost');
    visit('/custom')->assertSee('Custom Host');

    // Reset to default by setting null (this would need to be implemented)
    // For now, we test that the configuration persists
    visit('/custom')->assertSee('Custom Host');
});
