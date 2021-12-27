# wp-utils
A collection of helper classes for Wordpress Development.  Intended to be used with Composer.
This is intended to be an internal organization package.  Feel free to file an issue
if you find a bug, but we are not supporting this for anyone outside of Casadega Development.

### Install
add the repo for this package to the `repositories` array in your `composer.json` file

```json
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:Casadega-Development/wp-utils.git"
  }
],
```
then run...

`composer require casadev/wp-utils`

### Usage
If it's not already there to handle other dependencies,  
at the top of your theme's `function.php` file or your plugin's main file...

```php
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}
```

Example usage...

```php
use CasaDev_WP_Utils\Logging;

$log = new Logging();

function some_function($some_data) {
  $log->print_log('some_name', $some_data);
}
```

...or...


```php
use CasaDev_WP_Utils\Logging;

function some_function($some_data) {
  Logging::print_log('some_name', $some_data);
}
```
