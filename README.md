Log Unmet Dependencies
======================

A WordPress plugin that logs a PHP Notice to your error logs when a script
or style is not output duing a page load because one or more of its
dependencies does not exist.

It probably mostly works :) Almost completely untested.

No configuration.

Example
-------

```php
wp_enqueue_script( 'foo', 'foo.js', array( 'jquery', 'bar' ) );
wp_enqueue_script( 'bar', 'bar.js', array( 'lol' ) );
```

```
PHP Notice:  Script 'foo' was not printed because of an unmet dependency. It looks like 'bar' and 'lol' are missing. in .../log-unmet-dependencies.php on line 77
PHP Notice:  Script 'bar' was not printed because of an unmet dependency. It looks like 'lol' is missing. in .../log-unmet-dependencies.php on line 77
```
