# nodeman
FunkFeuer Node Manager

## Setup

Nodeman needs some dependencies that are fairly common so they
need to be installed first.

* [composer](https://getcomposer.org/)
* [make(1)](https://de.wikipedia.org/wiki/Make)
* [sassc](https://github.com/sass/sassc)

There is a simple Makefile which will handle all of the
required steps to download the PHP dependencies, generate
the CSS files etc.

```
make
```

The only thing left to do is to create the SQLite Database tables
and add some configuration.

```
sqlite share/nodeman.db
> .read share/schema.sql
> .q
```


## Development

For development we use the CI tools php-cs-fixer and phpstan.

There is a special make target which runs them.

```
make test
```

For testing and development you can use the builtin php
webserver which is definitely not recommended for production
use.

```
php -S localhost:80 index.php
```

For a production system please use nginx and the example
config in `share/nodeman.conf`.


Then you can access nodeman via `http://localhost/` and login
as user `admin` with password `admin`.

