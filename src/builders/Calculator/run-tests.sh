#!/bin/bash

# Go to Calculator directory
cd "$(dirname "$0")"

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit --testdox 