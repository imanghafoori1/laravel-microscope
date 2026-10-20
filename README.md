<h2 align="center">Find Bugs Before They Bite</h2>

<p align="center">
    <img width="300px" src="https://user-images.githubusercontent.com/6961695/78522127-920e9e80-77e1-11ea-869a-05a29466e6b0.png" alt="microscope_header">
</p>

<h4 align="center">Built with ❤️ for lazy Laravel developers ;)</h4>

<h3 align="center">Check the vibe-coded project to make sure it passes health checks.</h3>
<h3 align="center">AI hallucinations are never going to end.</h3>

![Packagist Stars](https://img.shields.io/packagist/stars/imanghafoori/laravel-microscope)
[![Required Laravel Version][ico-laravel]][link-packagist]
[![Required PHP Version][ico-php]][link-packagist]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
[![Today Downloads][ico-today-downloads]][link-downloads]
[![Coverage Status](https://coveralls.io/repos/github/imanghafoori1/laravel-microscope/badge.svg?branch=master)](https://coveralls.io/github/imanghafoori1/laravel-microscope?branch=master)
[![Test Package with Laravel 12](https://github.com/imanghafoori1/laravel-microscope/actions/workflows/app_test.yml/badge.svg)](https://github.com/imanghafoori1/laravel-microscope/actions/workflows/app_test.yml)

## Table of Contents

- [Key Things To Know](#key-things-to-know)
- [Installation](#installation)
- [Usage](#usage)
    - [Useful Commands](#useful-commands)
    - [Less Used Commands](#less-used-commands)
- [What The Commands Do](#what-the-commands-do)
    - [`search_replace`](#search_replace)
    - [`check:early_returns`](#early_returns)
    - [`check:psr4`](#psr4)
    - [`check:imports`](#imports)
    - [`check:bad_practices`](#bad_practices)
    - [`check:routes`](#routes)
    - [`check:compact`](#compact)
    - [`check:blade_queries`](#blade_queries)
    - [`check:extract_blades`](#extract_blades)
    - [`check:action_comments`](#action_comments)
    - [`check:views`](#views)
    - [`check:gates`](#gates)
    - [`check:aliases`](#aliases)
    - [`check:dead_controllers`](#dead_controllers)
    - [`check:generic_docblocks`](#generic_docblocks)
    - [`check:abort_if`](#check_abort_if)
    - [`check:dynamic_where`](#dynamic_where)
    - [`check:extra_fqcn`](#extra_fqcn)
    - [`check:extra_imports`](#extra_imports)
    - [`enforce:imports`](#enforce_imports)
    - [`enforce:helper_functions`](#helper_functions)
    - [`enforce:query`](#enforce_query)
    - [`list:models`](#models)
- [Credits](#credits)
- [License](#license)
- [Contributing](#contributing)
- [More From The Author](#more-from-author)
- [Contributors](#contributors)

<a name="key-things-to-know"></a>
## Key Things To Know

- It is created to be **smarter than PhpStorm** and other IDEs in finding errors.
- It is created to **understand Laravel runtime** and magic.
- It does **not show you stupid false errors** — every reported case is really an error.
- Even if you have written a lot of tests for your app, **you may still need this**.
- **It can refactor your code**, by applying early returns automatically.
- It is written from scratch to yield the **maximum possible performance**.

### ⭐ Your Stars Make Us Do More

> If you found this package useful and you want to encourage the maintainer to work on it, just press the star button to declare your willingness.

[Stargazers](https://github.com/imanghafoori1/microscope/stargazers)

<a name="installation"></a>
## ⬇️ Installation

Install the package via Composer:

```bash
composer require imanghafoori/laravel-microscope --dev
```

Optionally publish the config file:

```bash
php artisan vendor:publish --provider="Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider"
```

<a name="usage"></a>
## 💎 Usage

### Useful Commands

> You can run 👇

| # | Artisan Command |
|---|---|
| 1 | `php artisan search_replace` |
| 2 | `php artisan check:early_returns` |
| 3 | `php artisan check:all` |

<a name="less-used-commands"></a>
### Less Used Commands

| # | Artisan Command |
|---|---|
| 1 | `php artisan check:views` |
| 2 | `php artisan check:routes` |
| 3 | `php artisan check:psr4 {-s\|--nofix}` |
| 4 | `php artisan check:imports {-s\|--nofix}` |
| 5 | `php artisan check:stringy_classes` |
| 6 | `php artisan check:dd` |
| 7 | `php artisan check:bad_practices` |
| 8 | `php artisan check:compact` |
| 9 | `php artisan check:blade_queries` |
| 10 | `php artisan check:action_comments` |
| 11 | `php artisan check:extract_blades` |
| 12 | `php artisan check:abort_if` |
| 13 | `php artisan list:models` |
| 14 | `php artisan check:endif` |
| 15 | `php artisan check:gates` |
| 16 | `php artisan check:dynamic_where` |
| 17 | `php artisan check:aliases` |
| 18 | `php artisan check:dead_controllers` |
| 19 | `php artisan check:generic_docblocks` |
| 20 | `php artisan enforce:helper_functions` |
| 21 | `php artisan enforce:imports` |
| 22 | `php artisan check:fqcn` |

<a name="what-the-commands-do"></a>
## 📖 What Do The Commands Do?

Let's start with:

<a name="search_replace"></a>
### `php artisan search_replace`

```bash
php artisan search_replace
    {--name=pattern_name}
    {--tag=some_tag}
    {--file=partial_file_name}
    {--folder=partial_folder_name}
    {--except-folder=}
    {--except-file=}
```

A smart and very powerful search/replace functionality that can be a real **time saver**.

<a name="defining-patterns"></a>
#### 1️⃣ Defining Patterns

> If you run `php artisan search_replace` for the first time, it will create a `search_replace.php` file in the project's root. Then you can define your patterns within that file.

**Example:** replace the `optional()` global helper with the PHP 8 `?->` null-safe operator.

```php
return [
    'optional_to_nullsafe' => [
        'search'  => '"<global_func_call:optional>"("<in_between>")->',
        'replace' => '"<2>"?->',
        // 'tag' => 'php8,refactor',
        // 'predicate' => function ($matches, $tokens) {...},
        // 'mutator' => function ($matches) {...},
        // 'post_replace' => [...],
        // 'avoid_result_in' => [...],
        // 'avoid_syntax_errors' => false,
        // 'filters' => [...],
    ],
];
```

- The key `optional_to_nullsafe` is the **unique name** of your pattern. Target it with:
  ```bash
  php artisan search_replace --name=optional_to_nullsafe
  ```
- `<in_between>` captures everything between a pair of parentheses.
- In `replace`, `<1>` substitutes the first placeholder, `<2>` the second, and so on.
- `tag` can be a string (comma-separated) or an array. Target it with:
  ```bash
  php artisan search_replace --tag=php8
  ```

<a name="placeholders"></a>
#### 2️⃣ Placeholders

| # | Placeholder | Description |
|---|---|---|
| 1 | `<var>` or `<variable>` | Variables like `$user` |
| 2 | `<str>` or `<string>` | Hard-coded strings: `'hello'` or `"hello"` |
| 3 | `<class_ref>` | Class references: `\App\User::where(...)`, `User::where` |
| 4 | `<full_class_ref>` | Full references only: `\App\User::` |
| 5 | `<until>` | Capture all code until a certain character |
| 6 | `<comment>` | Comments (does not capture doc-blocks beginning with `/**`) |
| 7 | `<doc_block>` | PHP doc-blocks |
| 8 | `<statement>` | A whole PHP statement |
| 9 | `<name:nam1,nam2>` or `<name>` | Method or function names: `->where` or `::where` |
| 10 | `<white_space>` | Whitespace blocks |
| 11 | `<bool>` or `<boolean>` | `true` or `false` (case-insensitive) |
| 12 | `<number>` | Numeric values |
| 13 | `<cast>` | Type-casts like `(array) $a;` |
| 14 | `<int>` or `<integer>` | Integer values |
| 15 | `<visibility>` | `public`, `protected`, `private` |
| 16 | `<float>` | Floating-point numbers |
| 17 | `"<global_func_call:func1,func2>"` | Detect global function calls |
| 18 | `<in_between>` | Capture code within a pair of `{...}`, `(...)`, or `[...]` |
| 19 | `<any>` | Capture any token |

> You can also define your own keywords. Create a class for your keyword and append its classpath to `Finder::$keywords[] = MyKeyword::class`, just like the defaults.

**Example 1:** find only comments containing the word `todo:`.

```php
'todo_comments' => [
    'search' => '<comment>',
    'predicate' => function ($matches) {
        $comment = $matches[0];    // first placeholder value
        $content = $comment[1];    // its content

        return Str::contains($content, 'todo:') ? true : false;
    },
],
```

_Note_ If you do not mention the `'replace'` key, it only searches and reports them to you.

:two: Ok, now let's say you want to remove the "todo:" word from your comments:

```php
 'remove_todo_comments' => [
    'search' => '<comment>',      //   <=== we capture any comment
    'replace' => '<1>',

    'predicate' => function($matches) {
        $comment = $matches[0]; // first matched placeholder
        $content = $comment[1];

        return Str::contains($content, 'todo:') ? true : false;
    },

    'mutator' => function ($matches) {       //  <=== here we remove "todo:"
        $matches[0][1] = str_replace('todo:', '', $matches[0][1]);

        return $matches;
    }
]

```
Converts: ``` // todo: refactor code```
Into: ``` // refactor code```

<a name="mutator"></a>
#### 3️⃣ Mutator

In mutators, you are free to manipulate the `$matched` values as much as you need before they get replaced. You can also use a static method instead of a closure: `[MyClass::class, 'myStaticMethod']`.

**Example:** enforce an optional trailing comma in arrays.

```php
'enforce_optional_comma' => [
    'search' => '<white_space>?]',
    'replace' => ',"<1>"]',
    'avoid_syntax_errors' => true,
    'avoid_result_in' => [
        ',,]',
        '[,]',
        '<var>[,]',
    ],
],
```

This pattern isn't perfectly accurate, so we enable the PHP syntax validator (a performance cost) and list `avoid_result_in` strings so the validator is skipped when those appear in the result.

> The `?` in `<white_space>?` marks the placeholder as optional.

A faster, syntax-check-free alternative:

```php
'enforce_optional_comma' => [
    'search'  => '<1:any><2:white_space>?[<3:until_match>]',
    'replace' => '<1><2>[<3>,]',
    'avoid_result_in' => [
        ',,]',
        '[,]',
    ],
    'predicate' => function ($matches) {
        $type = $matches['values'][0][0];
        return $type !== T_VARIABLE && $type !== ']';
    },
    'post_replace' => [
        '<1:white_space>,]' => ',<1>]',
    ],
],
```

- `post_replace` is applied **only** to the resulting code — not the whole file.
- You can annotate placeholders with numbers (`<1:any>`) to know which one maps to which.

<a name="filters"></a>
#### 4️⃣ Filters

Two built-in filters: `is_sub_class_of` and `in_array`.

```php
'mention_query' => [
    'search'  => '<1:class_ref>::<2:name>',
    'replace' => '<1>::query()-><2>',
    'filters' => [
        1 => [
            'is_sub_class_of' => \Illuminate\Database\Eloquent\Model::class,
        ],
        2 => [
            'in_array' => 'where,count,find,findOrFail,findOrNew',
        ],
    ],
],
```

Converts:

```php
User::where(...)->get();

\App\Models\User::find(...);
```

into:

```php
User::query()->where(...)->get();

\App\Models\User::query()->find(...);
```

Filters ensure the captured class is an Eloquent model and the method name is in the list, so it leaves alone:

```php
User::all();          // `all` cannot be preceded by `query`
UserRepo::where(...); // UserRepo is not a model
```

This is something you can never do with regex alone.

<a name="capturing-php-statements"></a>
#### 5️⃣ Capturing PHP Statements

Opt into PHP 7.4 arrow functions:

```php
'fn' => [
    'search'  => 'function (<in_between>)<until>{ return <statement>; }',
    'replace' => 'fn (<1>) => <3>',
    'tags'    => 'php74,refactor',
],
```

Because we specified exactly one `<statement>` in the body, only single-statement closures are captured:

```php
$closure = function ($a) use ($b) {
    return $a + $b;
};
// becomes:
$closure = fn ($a) => $a + $b;
```

Multi-statement closures are ignored:

```php
$closure = function ($a) {
    $a++;
    return $a + $b;
};
```

<a name="difference-statement-until"></a>
#### 6️⃣ `<statement>` vs `<until>;`

They look similar, but there is an important case where `<until>;` fails:

```php
$first = $a + $b;

$second = function ($a) {
    $a++;

    return $a;
};
```

With:

```php
'pattern_name' => [
    'search' => '<var> = <until>;',
],
```

- For `$c = $a + $b;` both behave the same.
- For the second one, `<until>;` stops at `$a++;` — a problem.

Using `<var> = <statement>` is smart enough to capture the correct terminating semicolon of the closure definition.

<a name="capturing-global"></a>
#### 7️⃣ Capturing Global Function Calls

Remove `dd(...)` / `dump(...)` before pushing to production:

```php
return [
    'remove_dd' => [
        'search'  => "'<global_func_call:dd,dump>'('<in_between>');",
        'replace' => '',
    ],
];
```

This will **not** match:

```php
$this->  dd('hello');          // is technically a method call
User::   dd('I am static');    // is technically a static method call
new      dd('I am a class');  // here "dd" is the name of a class.
   
```

But will detect and remove real global `dd()` calls with whatever parameters they have received.

```
dd(                // <=== will be detected, even if the pattern above is written all in one line.
   auth('admin')
        ->user()->id   
);
    
    
\dd(1);
dd(1);
dump(1);
    
```
<a name="repeating-patterns" ></a>
:eight: **Repeating patterns:**

Refactor:

```php
User::where('name', 'John')->where('family', 'Dou')->where('age', 20)->get();
```

into:

```php
User::where([
    'name'   => 'John',
    'family' => 'Dou',
    'age'    => 20,
])->get();
```

Pattern:

```php
"group_wheres" => [
       
       'search' => '<1:class_ref>::where('<2:str>', '<3:str>')'<repeating:wheres>'->get();'
       
       'replace' => '<1>::where([
           <2> => <3>,
           "<repeating:1:key_values>"])->get();',

       'named_patterns' => [
           'wheres' => '->where(<str>, <str>)<white_space>?',
           'key_values' => '<1> => <2>,<3>',
       ]
   ]
```

Nice yeah??!

>Possibilities are endless and the sky is the limit...




<a name="early_returns"></a>
### `php artisan check:early_returns`


This will scan all your Psr-4 loaded classes and flattens your functions and loops by applying the early return rule.
For example:

```php
<?php

foreach ($products as $product) {
    if ($someCond) {
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

becomes:

```php
<?php

foreach ($products as $product) {
    if (! $someCond) {
        continue;
    }

    // A lot of code 1

    if (! $someOtherCond) {
        continue;
    }

    // A lot more code 2
}
```

The same applies to functions and methods, but with `return`. Nested `if`s get merged:

```php
<?php

if ($cond1) {
    if ($cond2) {
        ....       
    }
}

// we get merged into:

if ($cond1 && $cond2) { 
    ...  
}
```

It also supports the Ruby-like `if(): ... endif;` syntax and brace-less bodies.

<a name="psr4"></a>
### `php artisan check:psr4`

Checks (and optionally fixes) namespace declarations in your project to ensure they comply with [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading standards. The command scans your classes, compares each declared namespace against the expected namespace derived from its file path and `composer.json` autoload configuration, and can automatically correct mismatches — including references to renamed classes elsewhere in your codebase.

```bash
php artisan check:psr4 [options]
```

#### Options

| Option | Shortcut | Description |
|---|---|---|
| `--detailed` | `-d` | Show the classes being checked while the command runs. |
| `--force` | `-f` | Fix namespaces without asking for confirmation. |
| `--nofix` | `-s` | Skip fixing namespaces and only report them. |
| `--force-ref-fix` | `-o` | Fix references to renamed classes without asking for confirmation. |
| `--no-ref-fix` | `-r` | Skip searching for and fixing references to renamed classes. |
| `--watch` | `-w` | Re-run the command every 8 seconds (useful during development). |
| `--folder=` | | Filter the check to files in folders whose path contains the given partial name. |

#### Examples

```bash
# Basic interactive check
php artisan check:psr4

# Report only, never modify files
php artisan check:psr4 --nofix

# Fix namespaces automatically
php artisan check:psr4 --force

# Fully unattended fix (e.g., CI)
php artisan check:psr4 --force --force-ref-fix

# Limit to a folder
php artisan check:psr4 --folder=app/Models

```

<a name="imports"></a>
### `php artisan check:imports`


- Checks all imports (`use` statements) and reports invalid ones.
- Auto-corrects references when there is no ambiguity around the class name.
- Understands Laravel aliased classes, so `use Request;` is valid.

<a name="bad_practices"></a>
### `php artisan check:bad_practices`

Detects bad practices — e.g., `env()` calls outside config files.

<a name="routes"></a>
### `php artisan check:routes`

- Checks that routes refer to valid controller classes and methods.
- Checks that controller methods have valid type-hints.
- Scans `route()`, `redirect()->route()`, and `\Redirect::route()` for references to valid routes.
- Reports public controller methods with no route pointing to them (dead controllers).

<a name="compact"></a>
### `php artisan check:compact`

In PHP 7.3, `compact()` on a non-existent variable raises an error. This command scans the whole project for wrong `compact()` calls and reports which parameters to remove.

<a name="blade_queries"></a>

### `php artisan check:blade_queries`

Blade files should not contain DB queries — move them back into controllers and pass variables. This command scans all Blade files for Eloquent models and the DB query builder and reports them.

<a name="extract_blades"></a>
### `php artisan check:extract_blades`

Extract a Blade partial and include it. Use `{!! extractBlade('myPartials.someFile') !!}` to mark the start/end lines and path/name of the partial.

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

After `php artisan check:extract_blades`:

```html
<html>
    @include('myPartials.head')
    @include('myPartials.body')
</html>
```

It also creates:

- `resources/views/myPartials/head.blade.php`
- `resources/views/myPartials/body.blade.php`

with the corresponding content. Namespaced views (modular apps) are supported too: `'MyMod::myPartials.body'`.

<a name="action_comments"></a>
### `php artisan check:action_comments`

```bash
php artisan check:action_comments {--file=SomeFile.php}
```

Adds annotations in controller actions so you know which route points to the current action. Use `--file=` to narrow down the scanned files.

<a name="views"></a>
### `php artisan check:views`

- Scans your code for `view()` and `View::make()` and reports references to missing files.
- Scans Blade files for `@include()` and `@extends()` and reports references to missing files.
- Detects **unused variables** passed from a controller, e.g. `view('hello', [...])`.

To detect unused variables, open the page in the browser, then check the log file:

```
local.INFO: Laravel Microscope: The view file: welcome.index-1 at App\Http\Controllers\HomeController@index has some unused variables passed to it:
local.INFO: array ('$var1' , '$var2');
```

Variables passed via a view composer are also considered when detecting unused variables.

<a name="enforce_imports"></a>
### `php artisan enforce:imports`

Refactors your code by importing fully qualified class namespaces at the top as `use` statements. Use `--class=MyCls1,MyCls2` to limit which class names are imported.

<a name="check_abort_if"></a>
### `php artisan check:abort_if`

Refactors:

```php
<?php

if ($some === 'Condition') {
    abort(404, 'optional message.');
}
```

into:

```php
<?php

abort_if($some === 'Condition', 404, 'optional message.');
```

<a name="gates"></a>
### `php artisan check:gates`

Checks the validity of all gates, ensuring they refer to a valid class and method. Also checks policy definitions:

```php
Gate::policy(User::class, 'UserPolicy@someMethod');
Gate::define('someAbility', 'UserGate@someMethod');
```

1. Checks that the `User` classpath is valid.
2. Checks that the `UserPolicy` classpath is valid.
3. Checks that `someMethod` exists.

<a name="dynamic_where"></a>
### `php artisan check:dynamic_where`

Looks for "dynamic where" methods like `whereFamilyName('...')` and suggests `where('family_name', '...')`.

<a name="enforce_query"></a>
### `php artisan enforce:query`

Calls the static `query` method on your Eloquent query chains so IDEs can understand Eloquent. For example, converts `User::where(...` into `User::query()->where(...`.

<a name="dead_controllers"></a>
### `php artisan check:dead_controllers`

Finds controllers with no routes pointing to them.

<a name="generic_docblocks"></a>
### `php artisan check:generic_docblocks`

```bash
php artisan check:generic_docblocks {--folder=app/Models} {--file=SomeFile.php}
```

Removes Laravel's default doc-blocks. Use `--folder=`, `--file=`, `--except-file`, or `--except-folder` to narrow down the scanned folders.

<a name="helper_functions"></a>
### `php artisan enforce:helper_functions`

```bash
php artisan enforce:helper_functions {--folder=app/Models} {--file=SomeFile.php}
```

Converts Laravel facade calls into helper functions. Use `--folder=`, `--file=`, `--except-file`, or `--except-folder` to narrow down the scanned folders.

<a name="models"></a>
### `php artisan list:models`

```bash
php artisan list:models {--folder=app/Models}
```

Searches the project and lists the model classes. Use `--folder=` to narrow down the scanned folders.

<a name="extra_fqcn"></a>
### `php artisan check:extra_fqcn`

```bash
php artisan check:extra_fqcn {--folder=app/Models} --fix {--class=ClassRefsToBeFixed}
```

Searches for unnecessary fully qualified class names (FQCN) that are already imported at the top. Use `--class=` to only fix references to a specific class.

And more features will be added soon. ;)

<a name="credits"></a>
## Credits

- [Iman](https://github.com/imanghafoori1)
- [All Contributors](../../contributors)

<a name="license"></a>
## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.

<a name="contributing"></a>
## 🙋 Contributing

If you find an issue or have a better way to do something, feel free to open an issue or a pull request. If you use laravel-microscope in your open-source project, create a pull request to provide its URL as a sample application in the README.md file.

<a name="security"></a>
## ❗ Security

If you discover any security-related issues, please email `imanghafoori1@gmail.com` instead of using the issue tracker.

<a name="more-from-author"></a>
## More From The Author

### Laravel HeyMan

💎 It allows us to write expressive code to authorize, validate, and authenticate.

- https://github.com/imanghafoori1/laravel-heyman

---

> A man will never fail unless he stops trying.
>
> — Albert Einstein

[ico-laravel]: https://img.shields.io/badge/Laravel-%E2%89%A5%205.6-ff2d20?style=flat-square&logo=laravel
[ico-php]: https://img.shields.io/packagist/php-v/imanghafoori/laravel-microscope?color=%238892BF&style=flat-square&logo=php
[ico-version]: https://img.shields.io/packagist/v/imanghafoori/laravel-microscope.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/imanghafoori1/laravel-microscope.svg?style=flat-square&logo=scrutinizer
[ico-code-quality]: https://img.shields.io/scrutinizer/g/imanghafoori1/laravel-microscope.svg?style=flat-square&logo=scrutinizer
[ico-downloads]: https://img.shields.io/packagist/dt/imanghafoori/laravel-microscope.svg?style=flat-square
[ico-today-downloads]: https://img.shields.io/packagist/dd/imanghafoori/laravel-microscope.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/imanghafoori/laravel-microscope
[link-scrutinizer]: https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope
[link-downloads]: https://packagist.org/packages/imanghafoori/laravel-microscope/stats
[link-author]: https://github.com/imanghafoori1
[link-contributors]: ../../contributors

<a name="contributors"></a>
## ❤️ Contributors

This project exists thanks to all the people who contribute. [[Contributors](https://github.com/imanghafoori1/laravel-microscope/graphs/contributors)].

<a href="https://github.com/imanghafoori1/laravel-microscope/graphs/contributors">
    <img src="https://opencollective.com/laravel-microscope/contributors.svg?width=890&button=false" />
</a>

## ⭐ Star History

[![Star History Chart](https://star-history.dera.page/svg?repos=imanghafoori1/laravel-microscope&type=Date)](https://star-history.dera.page/#imanghafoori1/laravel-microscope&Date)