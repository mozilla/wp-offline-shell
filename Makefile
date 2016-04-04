.PHONY: reinstall build npm_install test test-sw release version-changelog

PLUGIN_NAME = wp-offline-shell
WP_CLI = tools/wp-cli.phar
PHPUNIT = tools/phpunit.phar
COMPOSER = tools/composer.phar

reinstall: $(WP_CLI) build
	$(WP_CLI) plugin uninstall --deactivate $(PLUGIN_NAME) --path=$(WORDPRESS_PATH)
	$(WP_CLI) plugin install --activate $(PLUGIN_NAME).zip --path=$(WORDPRESS_PATH)

build: $(COMPOSER) npm_install
	$(COMPOSER) install  --prefer-source --no-interaction
	rm -rf build $(PLUGIN_NAME).zip
	cp -r $(PLUGIN_NAME)/ build/
	mkdir -p build/vendor/mozilla/wp-sw-manager
	cp vendor/mozilla/wp-sw-manager/*.php build/vendor/mozilla/wp-sw-manager
	cp -r vendor/mozilla/wp-sw-manager/lib build/vendor/mozilla/wp-sw-manager/
	cd build/ && zip $(PLUGIN_NAME).zip -r *
	mv build/$(PLUGIN_NAME).zip $(PLUGIN_NAME).zip

test: $(PHPUNIT) build
	$(PHPUNIT)

test-sw: npm_install
	node node_modules/karma/bin/karma start karma.conf

npm_install:
	npm install

version-changelog:
	./version-changelog.js $(PLUGIN_NAME)

release: build tools/wordpress-repo version-changelog build

tools/wordpress-repo:
	mkdir -p tools
	cd tools && svn checkout https://develop.svn.wordpress.org/trunk/ && mv trunk wordpress-repo

$(COMPOSER):
	mkdir -p tools
	wget -P tools -N https://getcomposer.org/composer.phar
	chmod +x $(COMPOSER)

$(WP_CLI):
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)

$(PHPUNIT):
	mkdir -p tools
	wget -P tools -N https://phar.phpunit.de/phpunit-old.phar
	mv tools/phpunit-old.phar $(PHPUNIT)
	chmod +x $(PHPUNIT)

