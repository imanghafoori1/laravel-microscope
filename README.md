# Auto-test your laravel application

### Smart enough to know about all the laravel's magic :)


[![Latest Version on Packagist](https://img.shields.io/packagist/v/imanghafoori/laravel-self-test.svg?style=flat-square)](https://packagist.org/packages/imanghafoori1/laravel-self-test)
[![Build Status](https://img.shields.io/travis/imanghafoori1/laravel-self-test/master.svg?style=flat-square)](https://travis-ci.org/imanghafoori1/laravel-self-test)
[![Quality Score](https://img.shields.io/scrutinizer/g/imanghafoori1/laravel-self-test.svg?style=flat-square)](https://scrutinizer-ci.com/g/imanghafoori1/laravel-self-test)
[![Total Downloads](https://img.shields.io/packagist/dt/imanghafoori/laravel-self-test.svg?style=flat-square)](https://packagist.org/packages/imanghafoori1/laravel-self-test)

This package provides a way to find errors without writing any tests. For example when you setup an event handler which does not exist at all, notifies you.

## Installation

You can install the package via composer:

```bash
composer require imanghafoori/laravel-self-test
```

## Usage

You can run:

``` php
php artisan check:event
```

- It does not matter how you are setting your event listener, in the `EventServiceProvider`, by `Event::listen` facade, by Subscriber class... or any other way. The error would be found . :)

``` php
php artisan check:gate
```

It check the validity of all the gates you have defined, making sure that they refer to a valid class and method.


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


 :gem: A minimal yet powerful package to give you opportunity to refactor your controllers.

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
