#!/usr/bin/env sh
set -eu

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

if [ ! -x "$project_dir/vendor/bin/phpunit" ] || [ ! -x "$project_dir/vendor/bin/phpcs" ]; then
  echo "Development dependencies are missing. Run: composer install" >&2
  exit 1
fi

find "$project_dir/searchstocklast" "$project_dir/tests" -type f -name '*.php' -print0 \
  | xargs -0 -n1 php -l

cd "$project_dir"
composer test
