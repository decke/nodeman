all: build

clean:
	rm -f .php_cs.cache phive.xml
	rm -rf vendor
	rm -rf tools
	make -C css clean

tools:
	phive --no-progress install php-cs-fixer
	phive --no-progress install phpstan

vendor:
	composer install

build: vendor
	make -C css

test: tools vendor
	tools/php-cs-fixer fix --dry-run --diff-format udiff index.php
	tools/php-cs-fixer fix --dry-run --diff-format udiff lib
	tools/phpstan analyse -l 5 -c phpstan.neon lib index.php

fix: tools
	tools/php-cs-fixer fix index.php
	tools/php-cs-fixer fix lib

.PHONY: all clean build test fix
