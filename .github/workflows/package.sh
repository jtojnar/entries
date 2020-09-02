#!/usr/bin/env bash

set -o errexit

if (( $# == 1 )); then
	export ENTRIES_ZIPBALL="$1"
else
	export SHORT_COMMIT="$(git rev-parse --short HEAD)"
	export ENTRIES_VERSION="$SHORT_COMMIT"
	export ENTRIES_ZIPBALL="entries-$ENTRIES_VERSION.zip"
fi

git clean -f

composer install --no-dev --optimize-autoloader --prefer-dist
yarn install
yarn run build
zip -r "$ENTRIES_ZIPBALL" app log temp vendor www install.sql readme.md .htaccess -x 'www/assets' '*/.git*' '*/tests*' '*/tools*' '*/examples*' '*/license*' '*/LICENSE*' '*/CODE_OF_CONDUCT.md' '*/readme.md' '*/contributing.md' '*/.travis.yml' '*/.appveyor.yml' '*/composer.json' '*/composer.lock' '*/package.json' '*/bower.json' '*/yarn.lock'
