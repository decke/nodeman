all: build

clean:
	rm -rf vendor
	make -C css clean

vendor:
	composer install

build: vendor
	make -C css

test: vendor
	php-cs-fixer list-files
	phpstan analyse -l max -c phpstan.neon bin/* lib index.php

fix:
	php-cs-fixer fix

.PHONY: all clean build test fix
