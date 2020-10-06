all: build

clean:
	rm -rf vendor
	make -C css clean

vendor:
	composer install

build: vendor
	make -C css

test: vendor
	php-cs-fixer fix --dry-run --diff-format udiff
	phpstan analyse -l 5 -c phpstan.neon bin lib index.php

fix:
	php-cs-fixer fix

.PHONY: all clean build test fix
