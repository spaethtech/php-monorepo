#!/usr/bin/env bash

name=spaethtech/php-$1
url=https://github.com/spaethtech/php-$1
path=lib/php-$1

git reset
git submodule deinit -f "$path"
rm -rf ".git/modules/$name"
git rm -rf "$path"
git commit -m "Removed $name submodule"
