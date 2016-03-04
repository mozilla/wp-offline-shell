.PHONY: reinstall test l10n

WP_CLI = tools/wp-cli.phar
PHPUNIT = tools/phpunit.phar

reinstall: $(WP_CLI)
	$(WP_CLI) plugin uninstall --deactivate wp-sw-cache --path=$(WORDPRESS_PATH)
	rm -f wp-sw-cache.zip
	zip wp-sw-cache.zip -r wp-sw-cache/
	$(WP_CLI) plugin install --activate wp-sw-cache.zip --path=$(WORDPRESS_PATH)

test: $(PHPUNIT)
	$(PHPUNIT)

test-sw: node_modules
	$(NODE) node_modules/karma/bin/karma start karma.conf

node_modules:
	npm install

l10n: tools/wordpress-repo
	php tools/wordpress-repo/tools/i18n/makepot.php wp-plugin wp-sw-cache
	mv wp-sw-cache.pot wp-sw-cache/lang/service-worker-cache.pot

tools/wordpress-repo:
	mkdir -p tools
	cd tools && svn checkout https://develop.svn.wordpress.org/trunk/ && mv trunk wordpress-repo

tools/wp-cli.phar:
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)

tools/phpunit.phar:
	mkdir -p tools
	wget -P tools -N https://phar.phpunit.de/phpunit-old.phar
	mv tools/phpunit-old.phar tools/phpunit.phar
	chmod +x $(PHPUNIT)
