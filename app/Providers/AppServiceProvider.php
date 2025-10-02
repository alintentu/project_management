<?php

namespace App\Providers;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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

        $this->ensureStoragePaths();
        $this->ensureSqliteDatabase();
        $this->ensureLocalTestUser();
    }

    private function ensureStoragePaths(): void
    {
        $paths = [
            storage_path('app/public'),
            storage_path('app/private'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($paths as $path) {
            File::ensureDirectoryExists($path);
        }
    }

    private function ensureSqliteDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if (! $database || $database === ':memory:') {
            return;
        }

        if (! str_contains($database, DIRECTORY_SEPARATOR)) {
            $database = database_path($database);
            config(['database.connections.sqlite.database' => $database]);
        }

        if (! file_exists($database)) {
            File::ensureDirectoryExists(dirname($database));
            File::put($database, '');
        }
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
