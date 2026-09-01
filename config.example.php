<?php
/**
 * Lead Ledger — connection settings.
 *
 * Copy this file to config.php and fill in the four values from the one.com
 * control panel (Web hosting -> MySQL/Database). config.php is gitignored.
 *
 *  - Running ON one.com (the normal case): db_host is "localhost". The database
 *    sits on the same machine as the site, so the external hostname the control
 *    panel shows is not what you want here.
 *  - Running from your Mac against one.com: switch the database to external
 *    access in the control panel first, then use the hostname it shows there
 *    (something like "xxxxxxx.mysql.service.one.com").
 *
 * On one.com the database name and the user name are usually the same string.
 */

return [
    'db_host' => 'localhost',
    'db_name' => 'CHANGEME',
    'db_user' => 'CHANGEME',
    'db_pass' => 'CHANGEME',

    // Every table this app touches carries this prefix.
    'db_prefix' => 'll_',

    // Shown in the browser tab.
    'site_name' => 'Lead Ledger',

    // true  — clean URLs (/leadledger/sign-in). Needs mod_rewrite and the
    //         bundled .htaccess to actually be read by Apache.
    // false — routes go through the front controller directly
    //         (/leadledger/index.php/sign-in). Works with no .htaccess at all,
    //         so nothing further up the tree — a WordPress install at the
    //         domain root, say — can intercept them.
    'pretty_urls' => true,

    // Turn on while setting up; turn off once live.
    'debug' => false,
];
