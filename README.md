# Auto-test your laravel application

## Give your eyes a rest, this will check it for you.
### This package is created understand laravel magic and be smarter than IDEs or other static analyzers.


[![Latest Version on Packagist](https://img.shields.io/packagist/v/imanghafoori/laravel-self-test.svg?style=flat-square)](https://packagist.org/packages/imanghafoori1/laravel-self-test)
[![Build Status](https://img.shields.io/travis/imanghafoori1/laravel-self-test/master.svg?style=flat-square)](https://travis-ci.org/imanghafoori1/laravel-self-test)
[![Quality Score](https://img.shields.io/scrutinizer/g/imanghafoori1/laravel-self-test.svg?style=flat-square)](https://scrutinizer-ci.com/g/imanghafoori1/laravel-self-test)
[![Total Downloads](https://img.shields.io/packagist/dt/imanghafoori/laravel-self-test.svg?style=flat-square)](https://packagist.org/packages/imanghafoori1/laravel-self-test)

This package provides a way to find errors without writing any tests.

## Key things to know:

- It is created to be smarter than phpstorm and other IDEs in finding errors.
- It is created to understand laravel run-time and magic.
- It does not show you stupid false errors, all the errors are really errors.


## Installation

You can install the package via composer:

```bash
composer require imanghafoori/laravel-self-test
```

## Usage

You can run:
- php artisan check:event 
- php artisan check:gate   
- php artisan check:route  `(checks controller class and method also the blade files path to be correct)`
- php artisan check:psr4   `(auto-corrects namespaces, reports wrong imports)`

## What the Commands do?

Lets start with:
```
php artisan check:event 
```

For example consider:

```php
Event::listen(MyEvent::class, '\App\Listeners\MyListener@myMethod');
```

1 - It checks the  `MyEvent` class path to be valid.

2 - It checks the  `MyListener` class path to be valid.

3 - It checks the  `myMethod` to exist.

4 - It checks the  `myMethod` to have the right type-hint (if any) in its signature, for example:
```php
public function myMethod(NotExistsEvent $e) // <---- notice type-hint here
{
    //
}
```
This is a wrong type-hint and will be reported to you. very cool, isn't it ??!


- Note that it does not matter how you are setting your event listener, 1- in the `EventServiceProvider`, 2- by `Event::listen` facade,  3- by Subscriber class... or any other way. The error would be found. :)

``` 
php artisan check:gate
```

It check the validity of all the gates you have defined, making sure that they refer to a valid class and method.

It also checks for the policy definitions to be valid. 

```php
Gate::policy(User::class, 'UserPolicy@someMethod'); 
Gate::define('someAbility', 'UserGate@someMethod'); 
```

1 - It checks the  `User` class path to be valid.

2 - It checks the  `UserPolicy` class path to be valid.

3 - It checks the  `someMethod` to exist.

``` php
php artisan check:psr4
```
- It checks for all the psr4 autoloads defined in the composer.json file and goes through all the classes to have the right namespace, according to PSR-4 standard. 
- It check all the imports (`use` statements) to be valid. (It can understand the laravel aliased classes like: `use Request;`)


It also suggests the right namespace for the file.

``` php
php artisan check:route
```

- It check that your routes refer to valid controller classes and methods.
- It checks the all the controller methods to have valid type-hints.
- It scans your controller code and find the `view()` and `View::make()` and reports if they refer to wrong files.
- It scans your blade files for `@include()` and `@extends()` and reports if they refer to wrong files.

![image](https://user-images.githubusercontent.com/6961695/77560076-929e5f80-6eda-11ea-8482-9ccb9cafb1ed.png)

and more features will be added soon. ;)

### Security

If you discover any security related issues, please email imanghafoori1@gmail.com instead of using the issue tracker.

## Credits

- [Iman](https://github.com/imanghafoori1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

--------------------

### :raising_hand: Contributing 
If you find an issue, or have a better way to do something, feel free to open an issue or a pull request.
If you use laravel-widgetize in your open source project, create a pull request to provide it's url as a sample application in the README.md file. 


### :exclamation: Security
If you discover any security related issues, please email `imanghafoori1@gmail.com` instead of using the issue tracker.


### :star: Your Stars Make Us Do More :star:
As always if you found this package useful and you want to encourage us to maintain and work on it. Just press the star button to declare your willing.



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

:gem: It allows you login with any password in the local environment only.

- https://github.com/imanghafoori1/laravel-anypass

------------

### Eloquent Relativity

:gem: It allows you to decouple your eloquent models to reach a modular structure

- https://github.com/imanghafoori1/eloquent-relativity


----------------

### 🍌 Reward me a banana 🍌

Send me as much as a banana costs in your region:

- Dodge Coin: DJEZr6GJ4Vx37LGF3zSng711AFZzmJTouN
- LiteCoin: ltc1q82gnjkend684c5hvprg95fnja0ktjdfrhcu4c4
- BitCoin: bc1q53dys3jkv0h4vhl88yqhqzyujvk35x8wad7uf9
- Ripple: rJwrb2v1TR6rAHRWwcYvNZxjDN2bYpYXhZ
- Etherium: 0xa4898246820bbC8f677A97C2B73e6DBB9510151e

--------------
