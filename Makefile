all: build

clean:
	rm -f .php_cs.cache phive.xml
	rm -rf vendor
	rm -rf tools

tools:
	phive --no-progress install --trust-gpg-keys E82B2FB314E9906E php-cs-fixer
	phive --no-progress install --trust-gpg-keys 8E730BA25823D8B5 phpstan

vendor:
	composer install

build: vendor

test: tools vendor
	tools/php-cs-fixer fix --dry-run --diff-format udiff index.php
	tools/php-cs-fixer fix --dry-run --diff-format udiff lib
	tools/phpstan analyse -l 5 -c phpstan.neon lib index.php

fix: tools
	tools/php-cs-fixer fix index.php
	tools/php-cs-fixer fix lib

.PHONY: all clean build test fix
