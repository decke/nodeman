![StyleCI Badge](https://styleci.io/repos/102716552/shield?branch=master)

# nodeman
FunkFeuer Node Manager

## Setup

Nodeman is using composer for the PHP dependencies so they
need to be downloaded and installed first.

```
composer install
```

Then we also need to create the SQLite Database tables and
add some configuration.

```
sqlite share/nodeman.db
> .read share/schema.sql
> .q
```


For testing and development you can use the builtin php
webserver but beware it's not safe because anybody could
access your sqlite databse.

```
php -S localhost:80
```

For a production system please use nginx and the example
config in `share/nodeman.conf`.


Then you can access nodeman via `http://localhost/` and login
as user `admin` with password `admin`.

