all: build

clean:
	rm -f .php_cs.cache
	rm -rf vendor

vendor:
	composer install --dev

build: vendor

test: build
	php-cs-fixer fix --dry-run --diff-format udiff index.php
	php-cs-fixer fix --dry-run --diff-format udiff lib
	vendor/bin/phpstan analyse -l 5 -c phpstan.neon lib index.php

fix:
	php-cs-fixer fix index.php
	php-cs-fixer fix lib

.PHONY: all clean build test fix
