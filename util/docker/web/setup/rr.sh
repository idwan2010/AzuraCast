#!/bin/bash
set -e
set -x

cp /bd_build/web/rr/.rr.yaml.tmpl /var/azuracast/.rr.yaml.tmpl
cp /bd_build/web/rr/php_startup.sh /var/azuracast/php_startup.sh

chmod a+x /var/azuracast/php_startup.sh
