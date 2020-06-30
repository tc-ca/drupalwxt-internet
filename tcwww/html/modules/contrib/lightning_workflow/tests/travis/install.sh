#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

[[ -d "$ORCA_FIXTURE_DIR" ]] && composer require drupal/inline_entity_form --working-dir "$ORCA_FIXTURE_DIR"

[[ "$ORCA_JOB" = "DEPRECATED_CODE_SCAN" ]] && composer require drupal/panelizer --working-dir "$ORCA_FIXTURE_DIR"

# This is a temporary workaround for a change in BLT 11.x which causes
# mikey179/vfsstream to be absent from the fixture, which breaks all
# kernel tests.
if [ -d $ORCA_FIXTURE_DIR ]; then
  cd $ORCA_FIXTURE_DIR
  composer require --dev mikey179/vfsstream
fi
# End temporary workaround.

# Exit early if no DB fixture is specified.
[[ "$DB_FIXTURE" ]] || exit 0

cd "$ORCA_FIXTURE_DIR/docroot"

DB="$TRAVIS_BUILD_DIR/tests/fixtures/$DB_FIXTURE.php.gz"

php core/scripts/db-tools.php import ${DB}

drush php:script "$TRAVIS_BUILD_DIR/tests/update.php"

drush updatedb --yes
drush update:lightning --no-interaction --yes

orca fixture:enable-extensions

# Reinstall from exported configuration to prove that it's coherent.
drush config:export --yes
drush site:install --yes --existing-config

# Big Pipe interferes with non-JavaScript functional tests, so uninstall it now.
drush pm:uninstall big_pipe --yes

# Set the fixture state to reset to between tests.
orca fixture:backup --force
