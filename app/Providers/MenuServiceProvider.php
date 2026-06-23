<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Menu;
class MenuServiceProvider extends ServiceProvider
{
    public function boot()
    {
        try {
            $m = new Menu();
            $menu = $m->listar_menus_y_submenus();
            $this->app->instance('menu', $menu);
        } catch (\Exception $e) {
            // Manejo de errores si es necesario
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
}
