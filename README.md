# hook-logger-plugin

Easily debug WordPress action / filter hooks, finding where actions are called from and understanding the flow of execution.

This plugin does nothing on its own and requires the user to add additional code elsewhere.


the easist way to generate output is...

~~~php
$logger = new Hook_Logger_Plugin();
$logger->log_to_file = true;
~~~

This will create a new .log file for each and every request. By default logging to file does not happen.

### Additional Config

The most common usage is supplying include or exclude strings / regex.

When a string is passed it will match against the start of the action. eg, `option` will match `option_test` but not `test_option`.
When a regex is passed it must start with `/`. eg `/mail/` will match `wp_mail` and `sanitize_email`

~~~php
$logger = new Hook_Logger_Plugin();
$logger->include = ['mail'];
~~~

or 
~~~php
$logger = new Hook_Logger_Plugin();
$logger->exclude[] = '/plugin/';
~~~
**Note:** by default there are a number of helpful exclusions pre-loaded.

### Sample log file

~~~log
Date: Sun, 19 Dec 2021 23:32:11 +0000
URL: /wp-admin/edit.php
Method: URL: GET
POST:
array (
)

2021-12-19 23:32:11.446 plugin_loaded /var/www/wp-settings.php:418
2021-12-19 23:32:11.447 plugin_loaded /var/www/wp-settings.php:418
2021-12-19 23:32:11.448 pre_transient_jetpack_autoloader_plugin_paths /var/www/wp-includes/option.php:831
2021-12-19 23:32:11.448 transient_jetpack_autoloader_plugin_paths /var/www/wp-includes/option.php:871
2021-12-19 23:32:11.448 upload_dir /var/www/wp-includes/functions.php:2323
2021-12-19 23:32:11.449 woocommerce_helper_api_base /var/www/wp-content/plugins/woocommerce/includes/admin/helper/class-wc-helper-api.php:33
2021-12-19 23:32:11.449 woocommerce_helper_loaded /var/www/wp-content/plugins/woocommerce/includes/admin/helper/class-wc-helper.php:49
2021-12-19 23:32:11.449 template /var/www/wp-includes/theme.php:315
2021-12-19 23:32:11.450 woocommerce_data_stores /var/www/wp-content/plugins/woocommerce/includes/class-wc-data-store.php:82
2021-12-19 23:32:11.450 woocommerce_customer-download_data_store /var/www/wp-content/plugins/woocommerce/includes/class-wc-data-store.php:92
2021-12-19 23:32:11.450 plugin_loaded /var/www/wp-settings.php:418
2021-12-19 23:32:11.450 set_url_scheme /var/www/wp-includes/link-template.php:3803
~~~
