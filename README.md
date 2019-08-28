# nodeman
FunkFeuer Node Manager

## Setup

Nodeman is using [composer](https://getcomposer.org/) for the
PHP dependencies so it needs to be installed first.

There is a simple Makefile which will handle all of the
required steps for you but this means you need to have `make`
installed.

```
make
```

Then we also need to create the SQLite Database tables and
add some configuration.

```
sqlite share/nodeman.db
> .read share/schema.sql
> .q
```


## Development

For development you also need [phive](https://phar.io/) which
can download some CI tools (php-cs-fixer and phpstan).

There is a special make target which runs this CI tools.

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

