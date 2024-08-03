<h2 align="center">
     Find Bugs Before They Bite
</h2>


<p align="center">
    <img width="300px" src="https://user-images.githubusercontent.com/6961695/78522127-920e9e80-77e1-11ea-869a-05a29466e6b0.png" alt="microscope_header"></img>
</p>

<h4 align="center">
Built with :heart: for lazy Laravel developers ;)
</h4>

<h3 align="center">
Why repeat the old errors, if there are so many new errors to commit?
</h3>
<h3 align="center">
(Bertrand Russel)
</h3>
<h5 align="center">
Give your eyes a rest, we will detect and fix them for you.
</h5>

![Packagist Stars](https://img.shields.io/packagist/stars/imanghafoori/laravel-microscope)
[![Required Laravel Version][ico-laravel]][link-packagist]
[![Required PHP Version][ico-php]][link-packagist]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
[![Today Downloads][ico-today-downloads]][link-downloads]
[![tests](https://github.com/imanghafoori1/laravel-microscope/actions/workflows/run-tests.yml/badge.svg?branch=master)](https://github.com/imanghafoori1/laravel-microscope/actions/workflows/run-tests.yml)
[![Imports](https://github.com/imanghafoori1/laravel-microscope/actions/workflows/imports.yml/badge.svg?branch=master)](https://github.com/imanghafoori1/laravel-microscope/actions/workflows/imports.yml)

<!--
<p align="center">
<a href="https://packagist.org/packages/imanghafoori/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/8d75e05f4b67de65b51e10772b054f506aa8cfab/68747470733a2f2f696d672e736869656c64732e696f2f7061636b61676973742f762f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70652e7376673f7374796c653d666c61742d737175617265" alt="Latest Version on Packagist" data-canonical-src="https://img.shields.io/packagist/v/imanghafoori/laravel-microscope.svg?style=round-square" style="max-width:100%;"></a>
<a href="https://travis-ci.org/imanghafoori1/laravel-self-test" rel="nofollow"><img src="https://camo.githubusercontent.com/63b18ae839896de4604ede21595326389fed0b1f/68747470733a2f2f696d672e736869656c64732e696f2f7472617669732f696d616e676861666f6f7269312f6c61726176656c2d73656c662d746573742f6d61737465722e7376673f7374796c653d666c61742d737175617265" alt="Build Status" data-canonical-src="https://img.shields.io/travis/imanghafoori1/laravel-self-test/master.svg?style=round-square" style="max-width:100%;"></a>
<a href="https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/ee6d1b9eee22268201b7e253867c6bb64838651e/68747470733a2f2f696d672e736869656c64732e696f2f7363727574696e697a65722f672f696d616e676861666f6f7269312f6c61726176656c2d6d6963726f73636f70652e7376673f7374796c653d666c61742d737175617265" alt="Quality Score" data-canonical-src="https://img.shields.io/scrutinizer/g/imanghafoori1/laravel-microscope.svg?style=round-square" style="max-width:100%;"></a>
<a href="https://packagist.org/packages/imanghafoori/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/5441e915afbdb81de92b808965f294b0c7d18c52/68747470733a2f2f706f7365722e707567782e6f72672f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70652f642f6461696c79" alt="Daily Downloads" data-canonical-src="https://poser.pugx.org/imanghafoori/laravel-microscope/d/daily" style="max-width:100%;"></a>
<a href="https://packagist.org/packages/imanghafoori/laravel-microscope" rel="nofollow"><img src="https://camo.githubusercontent.com/7f10826df8cf3fb52525fd2494554c0e587b8bb7/68747470733a2f2f696d672e736869656c64732e696f2f7061636b61676973742f64742f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70652e7376673f7374796c653d666c61742d737175617265" alt="Total Downloads" data-canonical-src="https://img.shields.io/packagist/dt/imanghafoori/laravel-microscope.svg?style=round-square" style="max-width:100%;"></a>
<a href="/imanghafoori1/laravel-microscope/blob/master/LICENSE.md"><img src="https://camo.githubusercontent.com/d885b3999bb863974fb67118174bb0402d089a89/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f6c6963656e73652d4d49542d626c75652e7376673f7374796c653d726f756e642d737175617265" alt="Software License" data-canonical-src="https://img.shields.io/badge/license-MIT-blue.svg?style=round-square" style="max-width:100%;"></a></p>
-->

- Table Of Contents
    - [Key Things To Know](#key-things-to-know)
    - [Installation](#installation)
    - [Usage](#usage)
        - [Useful Commands](#useful-commands)
        - [Fewer Use Commands](#less-use-commands)
        - [Global Helper Functions](#global-helper-functions)
    - [What The Commands Do?](#what-the-commands-do)

      <details>

        <summary>show commands</summary>

        1. [`php artisan search_replace`](#search_replace)
            - [Defining Patterns](#defining-patterns)
            - [Placeholders](#placeholders)
            - [Mutator](#mutator)
            - [Filters](#filters)
            - [Capturing Php "statements"](#capturing-php-statements)
            - [Capturing Global Function Calls](#capturing-global)
            - [Repeating Patterns](#repeating-patterns)

        1. [`php artisan check:early_returns`](#early_returns)
        1. [`php artisan check:psr4`](#psr4)
        1. [`php artisan check:generate`](#generate)
        1. [`php artisan check:imports`](#imports)
        1. [`php artisan check:bad_practices`](#bad_practices)
        1. [`php artisan check:routes`](#routes)
        1. [`php artisan check:compact`](#compact)
        1. [`php artisan check:blade_queries`](#blade_queries)
        1. [`php artisan check:extract_blades`](#extract_blades)
        1. [`php artisan check:action_comments`](#action_comments)
        1. [`php artisan pp:route`](#route)
        1. [`php artisan check:views`](#views)
        1. [`php artisan check:events`](#events)
        1. [`php artisan check:gates`](#gates)
        1. [`php artisan check:aliases`](#aliases)
        1. [`php artisan check:dead_controllers`](#dead_controllers)
        1. [`php artisan check:generic_docblocks`](#generic_docblocks)
        1. [`php artisan check:migrations`](#migrations)
        1. [`php artisan check:empty_comment`](#empty_comment)
        1. [`php artisan enforce:helper_functions`](#helper_functions)
        1. [`php artisan list:models`](#models)
    
     </details>

    - [Credits](#credits)
    - [License](#license)
    - [Contributing](#contributing)
    - [More From The Author](#more-from-author)
    - [Contributors](#contributors)

<a name="key-things-to-know"></a>
## Key things to know:

- It is created to be **smarter than phpstorm** and other IDEs in finding errors.
- It is created to **understand laravel run-time** and magic.
- It does **not show you stupid false errors**, all the reported cases are really errors.
- Even If you have written a lot of tests for your app, **you may still need this**.
- **It can refactor your code**, by applying `early returns` automatically.
- It is written from scratch to yield the **maximum performance** possible.

### :film_strip: Video tutorial [here](https://youtu.be/aEkiE30wNKk)

### :star: Your Stars Make Us Do More
>If you found this package useful, and you want to encourage the maintainer to work on it, just press the star button to declare your willingness.

<a href="https://github.com/imanghafoori1/microscope/stargazers">Stargazers</a>

<a name="installation"></a>
## <g-emoji class="g-emoji" alias="arrow_down" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/2b07.png">⬇️</g-emoji> Installation

You can **install** the package via Composer:

```bash
composer require imanghafoori/laravel-microscope --dev
```

You may also **publish** config file:
```
php artisan vendor:publish --provider="Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider"
```

<a name="usage"></a>
## <g-emoji class="g-emoji" alias="gem" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f48e.png">💎</g-emoji> Usage:

### Useful Commands:


>You can run :point_down:

|#|Artisan Command|
|---|---|
|1|`php artisan search_replace`|
|2|`php artisan check:early_returns`|
|3|`php artisan check:all`|


<a name="less-use-commands"></a>
### Less Used Commands:

|#|Artisan Command|
|---|---|
|1|`php artisan check:views`|
|2|`php artisan check:routes`|
|3|`php artisan check:psr4 {-s\|--nofix} `|
|4|`php artisan check:imports {-s\|--nofix} {--wrong} {--extra}`|
|5|`php artisan check:stringy_classes`|
|6|`php artisan check:dd`|
|7|`php artisan check:bad_practices`|
|8|`php artisan check:compact`|
|9|`php artisan check:blade_queries`|
|10|`php artisan check:action_comments`|
|11|`php artisan check:extract_blades`|
|12|`php artisan pp:route`|
|13|`php artisan check:generate`|
|14|`php artisan check:endif`|
|15|`php artisan check:events`|
|16|`php artisan check:gates`|
|17|`php artisan check:dynamic_where`|
|18|`php artisan check:aliases`|
|19|`php artisan check:dead_controllers`|
|20|`php artisan check:generic_docblocks`|
|21|`php artisan enforce:helper_functions`|
|22|`php artisan list:models`|

<a name="global-helper-functions"></a>
## Global Helper Functions:
>Also, You will have access to some global helper functions

```php 
microscope_dd_listeners($event);
 ```
In case you wonder what the listeners are and where they are,
you can call `microscope_dd_listeners(MyEvent::class);` within either the `boot` or `register` methods.
It works like a normal `dd(...);` meaning that the program stops running at that point.

<a name="what-the-commands-do"></a>
## <g-emoji class="g-emoji" alias="book" fallback-src="https://github.githubassets.com/images/icons/emoji/unicode/1f4d6.png">📖</g-emoji> What do the Commands do?

Let's start with the:

<a name="search_replace"></a>
### `php artisan search_replace {--name=pattern_name} {--tag=some_tag} {--file=partial_file_name} {--folder=partial_folder_name}`

This is a smart and very powerful search/replace functionality that can be a real "time saver" for you.

<a name="defining-patterns" ></a>
#### :one:		Defining patterns:

>If you run the command `artisan search_replace` for the first time, it will create a `search_replace.php` file in the project's root.
Then, you can define your patterns, within that file.


**Examples:**

Let's define a pattern to replace the `optional()` global helper with the `?->` php 8 null safe operator:

```php
return [
    'optional_to_nullsafe' => [
        'search' => '"<global_func_call:optional>"("<in_between>")->',
        'replace' => '"<2>"?->',
        // 'tag' => 'php8,refactor',
        // 'predicate' => function($matches, $tokens) {...},
        // 'mutator' => function($matches) {...},
        // 'post_replace' => [...],
        // 'avoid_result_in' => [...],
        // 'avoid_syntax_errors' => false,
        // 'filters' => [...],
    ]
];
```
- Here the key `optional_to_nullsafe` is the "unique name" of your pattern. (You can target your pattern by running ```php artisan search_replace --name=optional_to_nullsafe```)
- The search pattern has a `"<in_between>"` placeholder which captures everything in between the pair of parenthesis.
- In the `replace` block we substitute what we have captured by the first placeholder with the `"<1>"`.
  If we have more placeholders, we could have had `"<2>"` etc.
- In the tag block we can mention some tags as an array of strings or a string separated by commas
  and target them by `--tag` flag: ```php artisan search_replace --tag=php8```

<a name="placeholders" ></a>
:two: **Placeholders:**

Here is a comprehensive list of placeholders you can use:

|#|Placeholders|Description|
|---|---|---|
|1|`<var>` or `<variable>`|for variables like: `$user`|
|2|`<str>` or `<string>`|for hard coded strings: `'hello'` or "hello"|
|3|`<class_ref>`|for class references:  `\App\User::where(...` , `User::where`|
|4|`<full_class_ref>`|only for full references:  `\App\User::`|
|5|`<until>`|to capture all the code until you reach a certain character.|
|6|`<comment>`|for comments (it does not capture doc-blocks beginning with: /** )|
|7|`<doc_block>`|for php doc-blocks|
|8|`<statement>`|to capture a whole php statement.|
|9|`<name:nam1,nam2>` or `<name>`|for method or function names. `->where` or `::where`|
|10|`<white_space>`|for whitespace blocks|
|11|`<bool>` or `<boolean>`|for true or false (acts case-insensitive)|
|12|`<number>`|for numeric values|
|13|`<cast>`|for type-casts like: `(array) $a;`|
|14|`<int>` or `"<integer>"`|for integer values|
|15|`<visibility>`|for public, protected, private|
|16|`<float>`|for floating point number|
|17|`"<global_func_call:func1,func2>"`|to detect global function calls|
|18|`<in_between>`|to capture code within a pair of  `{...}` or `(...)` or `[...]`|
|19|`<any>`|captures any token.|

>You can also define your own keywords if needed!
>
>You just define a class for your new keyword and append the classpath to the end of the `Finder::$keywords[] = MyKeyword::class` property.
Just like the default keywords.

**Example:**

:one:  Let's say you want to find only the "comments" that contain the word "todo:" in them.
```php
 'todo_comments' => [
        'search' => '<comment>',
        'predicate' => function($matches) {    //   <====  here we check comment has "todo:"
            $comment = $matches[0]; // first placeholder value
            $content = $comment[1]; // get its content
            
            return Str::contains($content, 'todo:') ? true : false;
        },
]

```

_Note_ If you do not mention the `'replace'` key it only searches and reports them to you.

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

<a name="mutator" ></a>
:three: **Mutator:**
In mutators, you are free to manipulate the `$matched` values as much as you need to before replacing them in the results.
You can also mention a static method instead of a function, like this: `[MyClass::class, 'myStaticMethod']`


:three: Let's say you want to put the optional comma for the Lets  elements in the arrays if they are missing.
```php
    'enforce_optional_comma' => [
        'search' => '<white_space>?]',
        'replace' => ',"<1>"]',
        'avoid_syntax_errors' => true,
        'avoid_result_in' => [
           ',,]',
           '[,]',
           '<var>[,]'
       ],
    ]
```
In this case, our pattern is not very accurate and in some cases, it may result in syntax errors.
Because of  that, we turn on the php syntax validator to check the result, but that costs us a performance penalty!!!
To exclude the usage of PHP, to validate the results we have mentioned the `avoid_result_in` so that if they happen in the result it skips.

- **Note**: The `?` in the "<white_space>?" notes this is an `optional` placeholder.

If you are curious to see a better pattern that does not need any syntax checking, try this:

```
'enforce_optional_comma' => [
       'search' => '<1:any><2:white_space>?[<3:until_match>]',
       'replace' => '<1><2>[<3>,]',
       'avoid_result_in' => [
           ',,]',
           '[,]'
       ],
       'predicate' => function ($matches) {
           $type = $matches['values'][0][0];

           return $type !== T_VARIABLE && $type !== ']';
       },
       'post_replace' => [
           '<1:white_space>,]' => ',<1>]'
       ]
],

```
This is more complex but works much faster. (since it does not need the php syntax validator)

- Here `'post_replace'` is a pattern that is applied only and only on the resulting code to refine it, and NOT on the entire file.

- You can optionally comment your placeholders (as above `<1:any>`) with numbers so that you know which one corresponds to which when replaced.

<a name="filters" ></a>
:four: **Filters:**

Currently, the microscope offers only two built-in filters: `is_sub_class_of` and `in_array`

Can you guess what the heck this pattern is doing?!
```php
 'mention_query' => [
      'search' => '<1:class_ref>::<2:name>'
      'replace' => '<1>::query()-><2>',
      'filters' => [
          1 => [
              'is_sub_class_of' => \Illuminate\Database\Eloquent\Model::class
          ],
          2 => [
              'in_array' => 'where,count,find,findOrFail,findOrNew'
          ]
      ]
  ]
```


It converts these:
```php
User::where(...)->get();

\App\Models\User::find(...);
```

Into these:
```php
User::query()->where(...)->get();

\App\Models\User::query()->find(...);
```

- The filters here ensure that the captured class reference is a Laravel Model and the method name is one of the names mentioned in the list.

So it does not tamper with something like this:
```php
User::all();            // The `all` method can not be preceded by `query`

UserRepo::where(...);   /// UserRepo is not a model
```

- This is something that you can never do by regex.

<a name="capturing-php-statements" ></a>
:five: **Capturing php "statements":**

Let's say we want to opt into PHP v7.4 arrow functions:

```php
'fn' => [
    'search' => 'function (<in_between>)<until>{ return <statement>; }',
    'replace' => 'fn (<1>) => <3>',
    'tags' => 'php74,refactor',
]

```

In this example, we have mentioned one single "statement" in the body of the function.
So if it encounters a function with two or more statements it will ignore that.

```php
$closure = function ($a) use ($b) {
    return $a + $b;
};

// will become:
$closure = fn($a) => $a + $hello;
```

But this is not captured:
```php
$closure = function ($a) {
    $a++;
    return $a + $b;
};
```

:six: **Difference between `<statement>` and `<until>;`**

They seem to be very similar but there is an important case in which you can not use `<until>;` to cover it properly!

```php
$first = $a + $b;

$second = function ($a) {
    $a++;

    return $a;
};
```

If we define our pattern like this:

```php
return [
    'pattern_name' => [
        'search' => '<var> = <until>;',   
    ]
];
```
For `$c = $a + $b;` they act the same way, but for the second one `"<until>";` will not capture the whole closure and will stop as soon as it reaches `$a++;` and that is a problem.

But if you define your pattern as: `'<var> = <statement>'` it would be smart enough to capture the correct semicolon at the end of the closure definition and the whole close would be captured.

<a name="capturing-global" ></a>
:seven: **Capturing global function calls:**

Let's say you want to eliminate all the `dd(...)` or `dump(...)` before pushing to production.
```php
return [
    'remove_dd' => [
        'search' =>  "'<global_func_call:dd,dump>'('<in_between>');", 
        'replace' => ''
    ]
];
```

This will NOT capture cases like below:
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

Let's say we want to refactor:
```php
User:where('name', 'John')->where('family', 'Dou')->where('age', 20)->get();
```

into:
```php
User:where([
    'name' => 'John',
    'family' => 'Dou',
    'age'=> 20,
])->get();
```

Ok, how the pattern would look like then?!

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

foreach ($products as $product) {
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

- It also supports the ruby-like if():/endif; syntax;

```php
<?php

if ($var1 > 1):
    if ($var2 > 2):
        echo 'Hey Man';
    endif;
endif;

// Or if you avoid putting curly braces...
if ($var1 > 1)
    if ($var2 > 2)
        echo 'Hey Man';

```

**Although this type of refactoring is safe and is guaranteed to do the same thing as before, be careful to commit everything before trying this feature, in case of a weird bug or something.**


<a name="psr4"></a>
### `php artisan check:psr4`

- It checks for all the psr4 autoload defined in the composer.json file and goes through all the classes to have the right namespace, according to PSR-4 standard.
- It automatically corrects namespaces (according to PSR-4 rules)
- It also checks for references to the old namespace with the system and replaces them with the new one.


<a name="generate"></a>

### `php artisan check:generate`

You make an empty file, and we fill it, based on naming conventions.

If you create an empty `.php` file which ends with `ServiceProvider.php` after running this command:
1 - It will be filled with a boilerplate and correct Psr-4 namespace.
2 - It will be appended to the `providers` array in the `config/app.php`


<a name="imports"></a>

### `php artisan check:imports`


- It checks all the imports (`use` statements) to be valid and reports invalid ones.
- It autocorrects some references, no ambiguity is around the class name.
- It can understand the laravel aliased classes so `use Request;` would be valid.


<a name="bad_practices"></a>

### `php artisan check:bad_practices`


- It detects bad practices like `env()` calls outside the config files.

<a name="routes"></a>

### `php artisan check:routes`


- It checks that your routes refer to valid controller classes and methods.
- It checks all the controller methods to have valid type-hints.
- It scans for `route()`, `redirect()->route()`, `\Redirect::route()` to refer to valid routes.
- It will report the public methods of controllers, which have no routes pointing to them. In other words `dead controllers` are detected.

<a name="compact"></a>

### `php artisan check:compact`


- In php 7.3 if you "compact" a non-existent variable you will get an error, so this command checks the entire project for wrong `compact()` calls and reports to you, which parameters should be removed.

<a name="blade_queries"></a>

### `php artisan check:blade_queries`

- Blade files should not contain DB queries. We should move them back into controllers and pass variables.
This command searches all the blade files for the `Eloquent models` and `DB` query builder and shows them if any.

<a name="extract_blades"></a>
### `php artisan check:extract_blades`


- If you want to extract a blade partial out and make it included like: `@include('myPartials.someFile')`

You can use `{!! extractBlade('myPartials.someFile') !!}` in your blade files to indicate `start/end line` and the `path/name` of the partial you intend to be made.

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
Also, it will create:
- `resources/views/myPartials/head.blade.php`
- `resources/views/myPartials/body.blade.php`

And put the corresponding content in them.

- It is also compatible with namespaced views in modular Laravel applications.
So this syntax will work: `'MyMod::myPartials.body'`

<a name="action_comments"></a>
### `php artisan check:action_comments {--file=SomeFile.php}`


- This adds annotations in the controller actions so that you know which route is pointing to the current controller action.
- You can use the `--file=` option to narrow down the scanned files.

<a name="route"></a>
### `php artisan pp:route`


- First, you have to put this in your route file: `microscope_pretty_print_route('my.route.name');` 
- You can also pass the Controller@method syntax to the function.
- You can call it multiple times to pretty-print multiple routes.

<a name="views"></a>
### `php artisan check:views`


- It scans your code and finds the `view()` and `View::make()` and reports if they refer to the wrong files.
- It scans your blade files for `@include()` and `@extends()` and reports if they refer to the wrong files.


Also, it can detect `unused variables` which are passed into your view from the controller like this: `view('hello', [...]);`
For that you must open up the page in the browser and then visit the log file to see a message like this:
```
local.INFO: Laravel Microscope: The view file: welcome.index-1 at App\Http\Controllers\HomeController@index has some unused variables passed to it:   
local.INFO: array ('$var1' , '$var2');
```

Remember some variables are passed into your view from a `view composer` and not the controller.
Those variables are also taken into consideration when detecting unused variables.

<a name="events"></a>
### `php artisan check:events`


For example, consider:

```php
Event::listen(MyEvent::class, '\App\Listeners\MyListener@myMethod');
```

1 - It checks the `\App\Listeners\MyListener` classpath to be valid.

2 - It checks the `myMethod` method to exist on the `MyListener` class

3 - It checks the `myMethod` method to have the right type-hint (if any) in its signature, for example:
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


<a name="gates"></a>
### `php artisan check:gates`


It checks the validity of all the gates you have defined, making sure that they refer to a valid class and method.

It also checks for the policy definitions to be valid.

```php
Gate::policy(User::class, 'UserPolicy@someMethod');
Gate::define('someAbility', 'UserGate@someMethod');
```

1 - It checks the `User` classpath to be valid.

2 - It checks the `UserPolicy` classpath to be valid.

3 - It checks the `someMethod` method to exist.

<a name="dynamic_where"></a>

### `php artisan check:dynamic_where`


- It looks for "dynamic where" methods like `whereFamilyName('...')` with `where('family_name', '...')`.

<a name="enforce:query"></a>

### `php artisan enforce:query`


- It calls the static `query` method on your eloquent query chains so that IDEs can understand eloquent.

- For example, converts: `User::where(...` to `User::query()->where(...`

<a name="dead_controllers"></a>

### `php artisan check:dead_controllers`


- We can find the controllers that don't have any routes.

<a name="generic_docblocks"></a>

### `php artisan check:generic_docblocks {--folder=app/Models} {--file=SomeFile.php}`


- Removes Laravel's DocBlocks.
- You can use `--folder=` or `--file=` option to narrow down the scanned folders.

<a name="helper_functions"></a>

### `php artisan enforce:helper_functions {--folder=app/Models} {--file=SomeFile.php}`               


- Converting Laravel facade into helper functions.
- You can use `--folder=` or `--file=` option to narrow down the scanned folders.

<a name="models"></a>

### `php artisan list:models {--folder=app/Models}`               


- It searches the project and lists the model classes.
- You can use `--folder=` option to narrow down the scanned folders.


And more features will be added soon. ;)

<a name="credits"></a>
## Credits

- [Iman](https://github.com/imanghafoori1)
- [All Contributors](../../contributors)

<a name="license"></a>
## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


<a name="contributing"></a>

### :raising_hand: Contributing
If you find an issue or have a better way to do something, feel free to open an issue or a pull request.
If you use laravel-microscope in your open source project, create a pull request to provide its URL as a sample application in the README.md file.

<a name="security"></a>
### :exclamation: Security
If you discover any security-related issues, please email `imanghafoori1@gmail.com` instead of using the issue tracker.

<a name="more-from-author"></a>
## More from the author:

### Laravel HeyMan

:gem: It allows us to write expressive code to authorize, validate, and authenticate.

- https://github.com/imanghafoori1/laravel-heyman


--------------

### Laravel Terminator


 :gem: A minimal yet powerful package which allows you to refactor your controllers.

- https://github.com/imanghafoori1/laravel-terminator



### Laravel AnyPass

:gem: It allows you to login with any password in the local environment only.

- https://github.com/imanghafoori1/laravel-anypass



```
A man will never fail unless he stops trying.

Albert Einstein
```

[ico-laravel]: https://img.shields.io/badge/Laravel-%E2%89%A5%205.6-ff2d20?style=flat-square&logo=laravel
[ico-php]: https://img.shields.io/packagist/php-v/imanghafoori/laravel-microscope?color=%238892BF&style=flat-square&logo=php
[ico-version]: https://img.shields.io/packagist/v/imanghafoori/laravel-microscope.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/imanghafoori1/laravel-self-test/master.svg?style=flat-square&logo=travis
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/imanghafoori1/laravel-microscope.svg?style=flat-square&logo=scrutinizer
[ico-code-quality]: https://img.shields.io/scrutinizer/g/imanghafoori1/laravel-microscope.svg?style=flat-square&logo=scrutinizer
[ico-downloads]: https://img.shields.io/packagist/dt/imanghafoori/laravel-microscope.svg?style=flat-square
[ico-today-downloads]: https://img.shields.io/packagist/dd/imanghafoori/laravel-microscope.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/imanghafoori/laravel-microscope
[link-travis]: https://travis-ci.org/imanghafoori1/laravel-self-test
[link-scrutinizer]: https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/imanghafoori1/laravel-microscope
[link-downloads]: https://packagist.org/packages/imanghafoori/laravel-microscope/stats
[link-author]: https://github.com/imanghafoori1
[link-contributors]: ../../contributors


<a name="contributors"></a>
## ❤️ Contributors

This project exists thanks to all the people who contribute. [[Contributors](https://github.com/imanghafoori1/laravel-microscope/graphs/contributors)].
<a href="https://github.com/imanghafoori1/laravel-microscope/graphs/contributors"><img src="https://opencollective.com/laravel-microscope/contributors.svg?width=890&button=false" /></a>

## ⭐ Star History

[![Star History Chart](https://api.star-history.com/svg?repos=imanghafoori1/laravel-microscope&type=Date)](https://star-history.com/#imanghafoori1/laravel-microscope&Date)
