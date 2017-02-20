#!/bin/sh
export SHORT_COMMIT=$(git rev-parse --short HEAD)
export ENTRIES_VERSION=$SHORT_COMMIT
export ENTRIES_ZIPBALL=entries-$ENTRIES_VERSION.zip

git clean -f

composer install --no-dev --optimize-autoloader --prefer-dist
zip -r $ENTRIES_ZIPBALL app log temp vendor www install.sql readme.md -x '*/.git*' '*/tests*' '*/tools*' '*/examples*' '*/license.md' '*/readme.md' '*/contributing.md' '*/.travis.yml' '*/appveyor.yml' '*/composer.json' '*/bower.json'

sed -i "s/ENTRIES_VERSION/$ENTRIES_VERSION/g;s/ENTRIES_ZIPBALL/$ENTRIES_ZIPBALL/g" utils/bintray.json
