#!/usr/bin/env bash

name=spaethtech/php-$1
url=https://github.com/spaethtech/php-$1
path=lib/php-$1

git reset
git submodule add --name "$name" "$url" "$path"
git add .gitmodules "$path"
git commit -m "Added $name submodule"
