# laravel-shortcode
A implementation of shortcodes in a package for Laravel

```php
'providers' => [
    AriasBros\Shortcode\Providers\ShortcodeServiceProvider::class,     
],

'aliases' => [
    "Shortcode" => AriasBros\Shortcode\Facades\Shortcode::class
],
```
        
```php
use Shortcode;

Shortcode::composer("shortcode_tag", "App\Http\ViewShortcodes\MyShortcode");
```

```php
use AriasBros\Shortcode\Contracts\Factory as ShortcodeFactory;

public function boot(ShortcodeFactory $shortcode)
{   
    $shortcode->composer("shortcode_tag", "App\Http\ViewShortcodes\MyShortcode");
}
```

```php
<?php
    
namespace App\Http\ViewShortcodes;

use AriasBros\Shortcode\Contracts\Shortcode;

class MyShortcode implements Shortcode
{
    /**
     * @return Illuminate\View\View
     */
    public function compose($attrs = null)
    {
        return view("shortcodes.my-shortcode", $attrs);
    }
}
```