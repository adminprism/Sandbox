#!/bin/bash

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit --testdox 