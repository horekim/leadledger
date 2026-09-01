<?php
/**
 * Lead Ledger — connection settings.
 *
 * Copy this file to config.php and fill in the four values from the one.com
 * control panel (Web hosting -> MySQL/Database). config.php is gitignored.
 *
 *  - Running ON one.com (the normal case): DB_HOST is the internal hostname
 *    one.com shows next to the database, usually something like
 *    "xxxxxxx.mysql.service.one.com". Some plans use "localhost".
 *  - Running from your Mac against one.com: you must first switch the database
 *    to "external access" in the control panel, then use the external hostname.
 */

return [
    'db_host' => 'CHANGEME.mysql.service.one.com',
    'db_name' => 'CHANGEME',
    'db_user' => 'CHANGEME',
    'db_pass' => 'CHANGEME',

    // Every table this app touches carries this prefix.
    'db_prefix' => 'll_',

    // Shown in the browser tab.
    'site_name' => 'Lead Ledger',

    // Turn on while setting up; turn off once live.
    'debug' => false,
];
