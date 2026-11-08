<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

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
        Model::preventLazyLoading(! app()->isProduction());

        Number::useLocale('id');
        Number::useCurrency('IDR');

        Blade::directive('currency', function ($expression) {
            return "<?php echo 'Rp ' . number_format((float) ($expression), 0, ',', '.'); ?>";
        });

        // Live critical stock notification counter for navbar bell (FR-NTF-01, AC-STK-04)
        View::composer('layouts.navigation', function ($view) {
            $count = Product::active()
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->count();

            $view->with('criticalStockCount', $count);
        });
    }
}
