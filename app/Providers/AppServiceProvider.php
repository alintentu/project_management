<?php

namespace App\Providers;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->ensureLocalTestUser();
    }

    private function ensureLocalTestUser(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if ($this->app->runningInConsole()) {
            return;
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        if (User::query()->exists()) {
            return;
        }

        if (Schema::hasTable('roles') && ! Role::query()->where('name', 'admin')->exists()) {
            Artisan::call('db:seed', [
                '--class' => PermissionsSeeder::class,
                '--force' => true,
            ]);
        }

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        if (Schema::hasTable('roles')) {
            $user->assignRole('admin');
        }
    }
}
