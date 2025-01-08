<?php
/*
This file is part of SeAT

Copyright (C) 2021  Kagurazaka Nyaa <developer@waw-eve.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace BJK\Decoy\Seat;

use Seat\Services\AbstractSeatPlugin;

/**
 * Class DecoyServiceProvider.
 *
 * @package BJK\Decoy\Seat
 */
class DecoyServiceProvider extends AbstractSeatPlugin
{
    public function boot()
    {
        $this->add_routes();
        $this->add_publications();
        $this->add_views();
        $this->add_translations();
        $this->add_migrations();
    }

    public function register()
    {
        // Overload sidebar with your package menu entries
        $this->mergeConfigFrom(__DIR__ . '/Config/decoyFuel.sidebar.php', 'package.sidebar');

        // Register generic permissions
        //$this->registerPermissions(__DIR__ . '/Config/decoyFuel.permissions.php', 'decoyFuel');
    }

    /**
     * Include routes.
     */
    private function add_routes()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
    }

    /**
     * Add content which must be published (generally, configuration files or static ones).
     */
    private function add_publications()
    {
        // $this->publishes([
        //     __DIR__ . '/resources/css' => public_path('web/css'),
        //     __DIR__ . '/resources/img' => public_path('mumble/img'),
        //     __DIR__ . '/resources/js' => public_path('mumble/js'),
        // ], ['public', 'seat']);
    }

    /**
     * Import translations.
     */
    private function add_translations()
    {
        // $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'mumble');
    }

    /**
     * Import views.
     */
    private function add_views()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'fuel');
    }

    /**
     * Import database migrations.
     */
    private function add_migrations()
    {
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations/');
    }

    /**
     * Return the plugin public name as it should be displayed into settings.
     *
     * @return string
     * @example SeAT Web
     *
     */
    public function getName(): string
    {
        return 'SeAT Decoy Tools';
    }

    /**
     * Return the plugin repository address.
     *
     * @example https://github.com/eveseat/web
     *
     * @return string
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/BJKerridge/seat-decoy';
    }

    /**
     * Return the plugin technical name as published on package manager.
     *
     * @return string
     * @example web
     *
     */
    public function getPackagistPackageName(): string
    {
        return 'seat-upgrades';
    }

    /**
     * Return the plugin vendor tag as published on package manager.
     *
     * @return string
     * @example eveseat
     *
     */
    public function getPackagistVendorName(): string
    {
        return 'bjk';
    }

}