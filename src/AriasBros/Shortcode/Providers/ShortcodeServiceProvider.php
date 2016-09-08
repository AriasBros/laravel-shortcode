<?php

namespace AriasBros\Shortcode\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * @since 1.0.0
 */
class ShortcodeServiceProvider extends ServiceProvider
{
    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function boot()
    {
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    public function register()
    {
        require_once(dirname(dirname(__FILE__)) . "/Helpers/shortcodes.php");
        require_once(dirname(dirname(__FILE__)) . "/Helpers/wordpress.php");
                
        $this->app->singleton("\AriasBros\Shortcode\Contracts\Factory", function ($app) {
            return new \AriasBros\Shortcode\ShortcodeFactory();
        });                 
    }
}
