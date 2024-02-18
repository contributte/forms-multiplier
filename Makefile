.PHONY: install qa cs csf phpstan tests coverage

install:
	composer update

qa: phpstan cs

cs:
ifdef GITHUB_ACTION
	vendor/bin/phpcs --standard=ruleset.xml --encoding=utf-8 --extensions="php,phpt" --colors -nsp -q --report=checkstyle src tests | cs2pr
else
	vendor/bin/phpcs --standard=ruleset.xml --encoding=utf-8 --extensions="php,phpt" --colors -nsp src tests
endif

csf:
	vendor/bin/phpcbf --standard=ruleset.xml --encoding=utf-8 --colors -nsp src tests

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon

tests:
	# Fix compatibility with https://github.com/nette/application/commit/bb8f93c60f9e747530431eef75df8b0aa8ab9d5b
	-patch -p1 -N -d vendor/webchemistry/testing-helpers < tests/webchemistry-testing-helpers-nette-3.2.patch
	vendor/bin/codecept run

coverage:
	# Fix compatibility with https://github.com/nette/application/commit/bb8f93c60f9e747530431eef75df8b0aa8ab9d5b
	-patch -p1 -N -d vendor/webchemistry/testing-helpers < tests/webchemistry-testing-helpers-nette-3.2.patch
ifdef GITHUB_ACTION
	phpdbg -qrr vendor/bin/codecept run --coverage-xml
else
	phpdbg -qrr vendor/bin/codecept run --coverage-html
endif
