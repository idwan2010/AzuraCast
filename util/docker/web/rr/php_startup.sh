#!/bin/bash

# Wait for services to spin up.
php /var/azuracast/www/bin/uptime_wait || exit 1

# Initialize before running FPM
php /var/azuracast/www/console azuracast:setup:initialize || exit 1

# Run initial Acme check
php /var/azuracast/www/console azuracast:acme:get-certificate || true
