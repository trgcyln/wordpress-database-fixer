<?php
/*
Plugin Name: WP Database SQL index, Primary Keys Fixer
Plugin URI: https://github.com/trgcyln/wordpress-database-fixer
Description: @see https://github.com/trgcyln/wordpress-database-fixer
Author: Turgay Ceylan	
Version: 1.0
Author URI: https://www.turgay.io
*/

add_filter('http_request_args', function ($response, $url)
{
    if (0 === strpos($url, 'https://api.wordpress.org/plugins/update-check'))
    {
        $basename = plugin_basename(__FILE__);
        $plugins = json_decode($response['body']['plugins']);
        unset($plugins
            ->plugins
            ->$basename);
        unset($plugins->active[array_search($basename, $plugins->active) ]);
        $response['body']['plugins'] = json_encode($plugins);
    }
    return $response;
}
, 10, 2);

echo "View the source for the queries<br/>\n<br/>\n";
$ret = '';

require_once (ABSPATH . 'wp-admin/includes/schema.php');
$schema = wp_get_db_schema('all');

$schema_exploded = explode('CREATE TABLE ', $schema);
/**
 array with items (val) as:
 xp_users (
 ID bigint(20) unsigned NOT NULL auto_increment,
 user_login varchar(60) NOT NULL default '',
 user_pass varchar(255) NOT NULL default '',
 user_nicename varchar(50) NOT NULL default '',
 user_email varchar(100) NOT NULL default '',
 user_url varchar(100) NOT NULL default '',
 user_registered datetime NOT NULL default '0000-00-00 00:00:00',
 user_activation_key varchar(255) NOT NULL default '',
 user_status int(11) NOT NULL default '0',
 display_name varchar(250) NOT NULL default '',
 PRIMARY KEY  (ID),
 KEY user_login_key (user_login),
 KEY user_nicename (user_nicename),
 KEY user_email (user_email)
 ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
 */

$new_table_schemas = array();

// first is an empty item, remove
array_shift($schema_exploded);

foreach ($schema_exploded as $schema_table)
{
    $schema_single_exploded = explode(PHP_EOL, $schema_table); // include last empty line
    $i = 0;

    $line_count = count($schema_single_exploded);
    foreach ($schema_single_exploded as $schema_single_line)
    {

        // first line "xp_users (" = PREFIX_TABLE )
        if ($i == 0)
        {
            // All but spaces and "("
            preg_match('/[^ (]*/', $schema_single_line, $matches);
            $table = $matches[0];
            $new_table_schemas[$table] = array(
                'cols' => array() ,
                'keys' => array()
            );
            $in_keys = false;
        }
        else
        {
            if ($i < ($line_count - 1 - 1))
            { // minus one for array, minus one for last empty line
                $line = trim($schema_single_line, ','); // remove ending comma
                $line = trim($line, 'auto_increment'); // remove auto_increment, we will add it later on
                $line = trim($line); // remove spaces
                if (substr_count($line, 'PRIMARY KEY') == 1 || substr_count($line, 'KEY') == 1)
                {
                    $in_keys = true;
                }
                if (substr_count($line, 'PRIMARY KEY') == 1)
                {
                    // isolate PK from "PRIMARY KEY  (ID)" -> "ID"
                    preg_match('/[(](.*)[)]/', $line, $matches);
                    $new_table_schemas[$table]['pk'] = $matches[1];
                }

                if (!$in_keys)
                {
                    $new_table_schemas[$table]['cols'][] = $line;
                }
                else
                {
                    $new_table_schemas[$table]['keys'][] = $line;
                }
            }
        }
        $i++;
    }
}

// foreach( $new_table_schemas as $table => $schemas ) {
$tables = $wpdb->tables();

foreach ($tables as $void => $table)
{
    $sql = "show keys FROM $table";
    $check = $wpdb->get_row($sql);
    if (!$check)
    {
        echo "NO keys for " . $table;
        echo $sql;
        var_dump($check);
    }
}

foreach ($tables as $void => $table)
{

    $schemas = $new_table_schemas[$table];

    $ret .= "<strong>Starting with $table</strong><br/>\n<pre>";

    $amount_of_pk = 0;
    if (isset($schemas['pk']))
    {
        // fix for object_id,term_taxonomy_id
        $pk_exploded = explode(',', $schemas['pk']);
        $amount_of_pk = count($pk_exploded);
        foreach ($pk_exploded as $pk)
        {
            $ret .= 'DELETE FROM ' . $table . ' WHERE ' . $pk . ' = 0;' . "\n";
        }
    }
    if (count($schemas['keys']) > 0)
    {
        foreach ($schemas['keys'] as $col)
        {
            $ret .= 'ALTER TABLE ' . $table . ' ADD ' . $col . ';' . "\n";
        }
    }

    if ($amount_of_pk == 1)
    {
        // once keys are added, re-add primary key increment
        $ret .= 'ALTER TABLE ' . $table . ' MODIFY ' . $schemas['cols'][0] . ' auto_increment;' . "\n";

    }
    $ret .= '</pre>';
}

echo $ret;
die();
?>
