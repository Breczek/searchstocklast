#!/usr/bin/env sh
set -eu

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
dist_dir="$project_dir/dist"
archive="$dist_dir/searchstocklast.zip"

mkdir -p "$dist_dir"
rm -f "$archive"

cd "$project_dir"
zip -qr "$archive" searchstocklast
unzip -t "$archive"
