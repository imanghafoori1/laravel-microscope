<h1 align="center">
    Find Bugs Before They Bite
</h1>
<h2 align="center">
    Automatic Code Refactor
</h2>
<h3 align="center">
    and, New Way of Code Generation
</h3>

<h3 align="center">
So, Give your eyes a rest, this will check it for you.
</h3>
<p align="center">
    <img width="300px" src="https://user-images.githubusercontent.com/6961695/78522127-920e9e80-77e1-11ea-869a-05a29466e6b0.png" alt="widgetize_header"></img>
</p>

<h4 align="center">
Built with :heart: for lazy laravel developers ;)
</h4>

<p align="center">
<a href="https://packagist.org/packages/imanghafoori/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/8d75e05f4b67de65b51e10772b054f506aa8cfab/68747470733a2f2f696d672e736869656c64732e696f2f7061636b61676973742f762f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70652e7376673f7374796c653d666c61742d737175617265" alt="Latest Version on Packagist" data-canonical-src="https://img.shields.io/packagist/v/imanghafoori/laravel-microscope.svg?style=round-square" style="max-width:100%;"></a>
<a href="https://travis-ci.org/imanghafoori1/laravel-self-test" rel="nofollow"><img src="https://camo.githubusercontent.com/63b18ae839896de4604ede21595326389fed0b1f/68747470733a2f2f696d672e736869656c64732e696f2f7472617669732f696d616e676861666f6f7269312f6c61726176656c2d73656c662d746573742f6d61737465722e7376673f7374796c653d666c61742d737175617265" alt="Build Status" data-canonical-src="https://img.shields.io/travis/imanghafoori1/laravel-self-test/master.svg?style=round-square" style="max-width:100%;"></a>
<a href="https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/ee6d1b9eee22268201b7e253867c6bb64838651e/68747470733a2f2f696d672e736869656c64732e696f2f7363727574696e697a65722f672f696d616e676861666f6f7269312f6c61726176656c2d6d6963726f73636f70652e7376673f7374796c653d666c61742d737175617265" alt="Quality Score" data-canonical-src="https://img.shields.io/scrutinizer/g/imanghafoori1/laravel-microscope.svg?style=round-square" style="max-width:100%;"></a>
<a href="https://packagist.org/packages/imanghafoori/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/5441e915afbdb81de92b808965f294b0c7d18c52/68747470733a2f2f706f7365722e707567782e6f72672f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70652f642f6461696c79" alt="Daily Downloads" data-canonical-src="https://poser.pugx.org/imanghafoori/laravel-microscope/d/daily" style="max-width:100%;"></a>
<a href="https://packagist.org/packages/imanghafoori/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/7f10826df8cf3fb52525fd2494554c0e587b8bb7/68747470733a2f2f696d672e736869656c64732e696f2f7061636b61676973742f64742f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70652e7376673f7374796c653d666c61742d737175617265" alt="Total Downloads" data-canonical-src="https://img.shields.io/packagist/dt/imanghafoori/laravel-microscope.svg?style=round-square" style="max-width:100%;"></a>
<a href="/imanghafoori1/laravel-microscope/blob/master/LICENSE.md"><img src="https://camo.githubusercontent.com/d885b3999bb863974fb67118174bb0402d089a89/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f6c6963656e73652d4d49542d626c75652e7376673f7374796c653d726f756e642d737175617265" alt="Software License" data-canonical-src="https://img.shields.io/badge/license-MIT-blue.svg?style=round-square" style="max-width:100%;"></a></p>

## Key things to know:

- It is created to be **smarter than phpstorm** and other IDEs in finding errors.
- It is created to **understand laravel run-time** and magic.
- It does **not show you stupid false errors**, all the errors are really errors.
- If you have written a lot of tests for your app, you may not need this.
- **It can refactor your code**, by applying `early returns` automatically.

## <g-emoji class="g-emoji" alias="arrow_down" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/2b07.png">⬇️</g-emoji> Installation 

You can install the package via composer:

```bash
composer require imanghafoori/laravel-microscope --dev
```

Although this project has already a lot of features, but it is still under active development, so you have to update it almost everyday in order to get the latest improvements and bug fixes.

```bash
composer update imanghafoori/laravel-microscope
```


## <g-emoji class="g-emoji" alias="gem" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f48e.png">💎</g-emoji> Usage

You can run:
<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:events
</h4></p>
<p>
<h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:gates
 </h4></p>
<p>
<h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:views
</h4></p>
<p>
<h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:routes 
 </h4></p>

<p>
<h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:psr4 
</h4></p>
 
<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:imports  </h4>
</p>  

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:stringy_classes </h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:dd 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:early_returns 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:compact 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:blade_queries 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:action_comments 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:bad_practices 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:extract_blades 
</h4></p>

<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan pp:route
</h4></p>


<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:generate
</h4></p>



<p><h4>
<g-emoji class="g-emoji" alias="small_blue_diamond" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f539.png">🔹</g-emoji> php artisan check:all 
</h4></p>

Also You will have access to some global helper functions:
 - microscope_dd_listeners($event);
 
 In case you wonder what are the listeners and where are they?! 
 You can use this (0_o) `microscope_dd_listeners(MyEvent::class);`  This call, also can be in `boot` or `register` as well.
And it works like a normal `dd(...);` meaning that it will halt.

## <g-emoji class="g-emoji" alias="book" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f4d6.png">📖</g-emoji> What the Commands do?

Lets start with:
```php
php artisan check:early_returns
```

This will scan all your Psr-4 loaded classes and flattens your functions ans loops by applying the early return rule.
For example:

```php
<?php

forearch ($products as $product) {
    if ($someCond) {
        // A lot of code 1
        // A lot of code 1
        // A lot of code 1
        // A lot of code 1
        // A lot of code 1
        if ($someOtherCond) {
            // A lot more code 2
            // A lot more code 2
            // A lot more code 2
            // A lot more code 2 
            // A lot more code 2
            //
        } // <--- closes second if
    } // <--- closes first if
}

```

Will be discovered and converted into:

```php
<?php

forearch ($products as $product) {
    if (! $someCond) {
        continue;
    }
    
    // A lot of code 1
    // A lot of code 1
    // A lot of code 1
    // A lot of code 1
    // A lot of code 1

    if (! $someOtherCond) {
        continue;
    }
 
    // A lot more code 2
    // A lot more code 2
    // A lot more code 2
    // A lot more code 2 
    // A lot more code 2
}

```

The same thing will apply for functions and methods, but with `return`

```php
<?php

if ($var1 > 1) {
    if ($var2 > 2) {
        echo 'Hey Man';
    }
}

// will be converted into:
if ($var1 > 1 && $var2 > 2) {
    echo 'Hey Man';
}

```

- It also supports the ruby like if():/endif; syntax;

```php
<?php

if ($var1 > 1):
    if ($var2 > 2):
        echo 'Hey Man';
    endif;
endif;

// or if you avoid putting curly braces...
if ($var1 > 1)
    if ($var2 > 2)
        echo 'Hey Man';


```

**Although this type of refactor is totally safe and is guaranteed to do the same thing as before, but anyway be careful to commit everything before trying this feature, in case of a weird bug or something.**

----------------------

```php
php artisan check:events
```

For example consider:

```php
Event::listen(MyEvent::class, '\App\Listeners\MyListener@myMethod');
```

1 - It checks the  `\App\Listeners\MyListener` class path to be valid.

2 - It checks the  `myMethod` to exist on the `MyListener` class

3 - It checks the  `myMethod` to have the right type-hint (if any) in its signature, for example:
```php
public function myMethod(OtherEvent $e) // <---- notice type-hint here
{
    //
}
```
This is a valid but wrong type-hint, and will be reported to you. Very cool, isn't it ??!


- Note that it does not matter how you are setting your event listener, 

1- in the `EventServiceProvider`, 

2- By `Event::listen` facade, 

3- By Subscriber class... or any other way. The error would be found. :)

----------------------

```php
php artisan check:gates
```

It checks the validity of all the gates you have defined, making sure that they refer to a valid class and method.

It also checks for the policy definitions to be valid.

```php
Gate::policy(User::class, 'UserPolicy@someMethod');
Gate::define('someAbility', 'UserGate@someMethod');
```

1 - It checks the  `User` class path to be valid.

2 - It checks the  `UserPolicy` class path to be valid.

3 - It checks the  `someMethod` to exist.

----------------------

```php
php artisan check:psr4
```
- It checks for all the psr4 autoloads defined in the composer.json file and goes through all the classes to have the right namespace, according to PSR-4 standard.
- It automatically corrects namespaces (according to PSR-4 rules)
- It also checks for references to the old namespace with the system and replaces them with the new one.

----------------------

```php
php artisan check:generate
```
You make empty file, we fill it, based on naming conventions.

If you create an empty `.php` file which ends with `ServiceProvider.php` after running this command:
1 - It will be filled with boiler plate and correct Psr-4 namespace.
2 - It will be appnded to the `providers` array in the `config/app.php`

----------------------

```php
php artisan check:imports
```

- It checks all the imports (`use` statements) to be valid and reports invalid ones.
- It auto-corrects some of the refrences, it no ambiguity is around the class name.
- It can understand the laravel aliased classes so `use Request;` would be valid.

----------------------
```php
php artisan check:bad_practices
```

 - It detects bad practices like `env()` calls outside of the config files.

----------------------

```php
php artisan check:routes
```

- It checks that your routes refer to valid controller classes and methods.
- It checks the all the controller methods to have valid type-hints.
- It scans for `route()`, `redirect()->route()`, `\Redirect::route()` to refer to valid routes.
- It will report the public methods of controllers, which have no routes pointing to them. In other words `dead controllers` are detected.

----------------------

```php
php artisan check:compact
```

- In php 7.3 if you "compact" a non-existent variable you will get an error, so this command checks the entire project for wrong `compact()` calls and reports to you, which parameters should be removed.

----------------------

```php
php artisan check:blade_queries
```

- Blade files should not contain DB queries. we should move them back into controllers and pass variables.
This command searches all the blade files for `Eloquent models` and `DB` query builder and shows them if any.

----------------------
```php
php artisan check:extract_blades
```

- If you want to extract a blade partial out and make it included like: `@include('myPartials.someFile')`

you can use `{!! extractBlade('myPartials.someFile') !!}` in your blade files to indicate `start/end line` and the `path/name` of the partial you intend to be made.

```html
  <html>
      
      {!! extractBlade('myPartials.head') !!}
          <head>...</head>
      {!! extractBlade() !!}

      
      {!! extractBlade('myPartials.body') !!}
          <body>...</body>
      {!! extractBlade() !!}
      
    </html>
```

After you execute `php artisan check:extract_blades` it will become:

```html
<html>
    @include('myPartials.head')
    @include('myPartials.body')
</html>
```
Also it will create:
- `resources/views/myPartials/head.blade.php` 
- `resources/views/myPartials/body.blade.php`

and put the corresponding content in them.

- It is also compatible with namespaced views in modular laravel applications.
So this syntax will work: `'MyMod::myPartials.body'`


----------------------
```php
php artisan check:action_comments
```

- This adds annotations in the controller actions so that you know which route is pointing to the current controller action.

----------------------

```php
php artisan pp:route
```

- First you have to put this in your route file: `microscope_pretty_print_route('my.route.name');` 
- You can also pass the Controller@method syntax to the function.
- You can call it multiple times in otder to pretty-print multiple routes.

----------------------
```php
php artisan check:views
```
- It scans your code and find the `view()` and `View::make()` and reports if they refer to wrong files.
- It scans your blade files for `@include()` and `@extends()` and reports if they refer to wrong files.


Also, it can detect `unused variables` which are passed into your view from the controller line this: `view('hello', [...]);`
For that you must open up the page in the browser and then visit the log file to see a message like this:
```
local.INFO: Laravel Microscope: The view file: welcome.index-1 at App\Http\Controllers\HomeController@index has some unused variables passed to it:   
local.INFO: array ('$var1' , '$var2');
```

Remember some variables are passed into your view from a `view composer` and not the controller.
Those variables are also taken into consideration when detecting unused variables.

----------------------

and more features will be added soon. ;)

## Credits

- [Iman](https://github.com/imanghafoori1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

--------------------

### :raising_hand: Contributing
If you find an issue, or have a better way to do something, feel free to open an issue , or a pull request.
If you use laravel-microscope in your open source project, create a pull request to provide it's url as a sample application in the README.md file.


### :exclamation: Security
If you discover any security related issues, please email `imanghafoori1@gmail.com` instead of using the issue tracker.


### :star: Your Stars Make Us Do More :star:
As always if you found this package useful , and you want to encourage us to maintain and work on it. Just press the star button to declare your willing.

Stargazers: https://github.com/imanghafoori1/microscope/stargazers

## More from the author:

### Laravel HeyMan

:gem: It allows us to write expressive code to authorize, validate and authenticate.

- https://github.com/imanghafoori1/laravel-heyman


--------------

### Laravel Terminator


 :gem: A minimal yet powerful package to give you the opportunity to refactor your controllers.

- https://github.com/imanghafoori1/laravel-terminator

------------

### Laravel AnyPass

:gem: It allows you to login with any password in the local environment only.

- https://github.com/imanghafoori1/laravel-anypass

------------

### Eloquent Relativity

:gem: It allows you to decouple your eloquent models to reach a modular structure

- https://github.com/imanghafoori1/eloquent-relativity

----------------

### 🍌 Pay after it pays off:

If you think that my work has saved you a lot of time hence a lot of money, please take your time and send me 1 dollar, I appritiate it, a lot... (a single dollar is enough, please so do not send more.)

- BitCoin: bc1q53dys3jkv0h4vhl88yqhqzyujvk35x8wad7uf9
- Etherium: 0xa4898246820bbC8f677A97C2B73e6DBB9510151e
- USDC: 0xB3F88d2334C9A5eFBe9c0932A969E8a971139547

You can contact me at telegram, after donation: https://t.me/imanghafoori so I can put your logo and name on the readme file. 

I would be happy to answer you.

--------------
### Todo:
- Detect Bad code
- Facadize static method calls
- Detect return keyword in eloquent relations
- Detect wrong action() calls
- Enhance blocky code detection
- Fullly decouple the error logger
- Detect `return abort();`
- Detect un-registered service providers
- Detect unused middlewares

```
A man will never fail, unless he stops trying.

Albert einstein
```

[ico-laravel]: https://img.shields.io/badge/Laravel-%E2%89%A5%205.5-ff2d20?style=flat-square&logo=laravel
[ico-php]: https://img.shields.io/packagist/php-v/imanghafoori1/sql-dumper?color=%238892BF&style=flat-square&logo=php
[ico-version]: https://img.shields.io/packagist/v/imanghafoori1/sql-dumper.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/imanghafoori1/sql-dumper/master.svg?style=flat-square&logo=travis
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/imanghafoori1/sql-dumper.svg?style=flat-square&logo=scrutinizer
[ico-code-quality]: https://img.shields.io/scrutinizer/g/imanghafoori1/sql-dumper.svg?style=flat-square&logo=scrutinizer
[ico-downloads]: https://img.shields.io/packagist/dt/imanghafoori1/sql-dumper.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/imanghafoori1/laravel-microscope
[link-travis]: https://travis-ci.org/imanghafoori1/laravel-microscope
[link-scrutinizer]: https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope
[link-downloads]: https://packagist.org/packages/imanghafoori1/laravel-microscope
[link-author]: https://github.com/imanghafoori1
[link-contributors]: ../../contributors
