all: build

clean:
	rm -f .php_cs.cache
	rm -rf vendor
	make -C css clean

vendor:
	composer install

build: vendor
	make -C css

test: vendor
	php-cs-fixer fix --dry-run --diff-format udiff index.php
	php-cs-fixer fix --dry-run --diff-format udiff lib
	php-cs-fixer fix --dry-run --diff-format udiff bin/linksearch
	php-cs-fixer fix --dry-run --diff-format udiff bin/migratedb
	php-cs-fixer fix --dry-run --diff-format udiff bin/sendmails
	php-cs-fixer fix --dry-run --diff-format udiff bin/updatehnadata
	php-cs-fixer fix --dry-run --diff-format udiff bin/updatelinkdata
	php-cs-fixer fix --dry-run --diff-format udiff bin/olsrinfo
	phpstan analyse -l 5 -c phpstan.neon bin lib index.php

fix:
	php-cs-fixer fix index.php
	php-cs-fixer fix lib
	php-cs-fixer fix bin/linksearch
	php-cs-fixer fix bin/migratedb
	php-cs-fixer fix bin/sendmails
	php-cs-fixer fix bin/updatehnadata
	php-cs-fixer fix bin/updatelinkdata
	php-cs-fixer fix bin/olsrinfo

.PHONY: all clean build test fix
