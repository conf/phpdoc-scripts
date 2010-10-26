<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
+----------------------------------------------------------------------+
| PHP Documentation Site Source Code                                   |
+----------------------------------------------------------------------+
| Copyright (c) 1997-2010 The PHP Group                                |
+----------------------------------------------------------------------+
| This source file is subject to version 3.01 of the PHP license,      |
| that is bundled with this package in the file LICENSE, and is        |
| available at through the world-wide-web at                           |
| http://www.php.net/license/3_01.txt.                                 |
| If you did not receive a copy of the PHP license and are unable to   |
| obtain it through the world-wide-web, please send a note to          |
| license@php.net so we can mail you a copy immediately.               |
+----------------------------------------------------------------------+
| Authors: Etienne Kneuss <colder@php.net>                             |
+----------------------------------------------------------------------+
$Id: script-skel.php 293138 2010-01-05 10:21:11Z rquadling $
*/

if (PHP_SAPI !== 'cli') {
    echo "This script is meant to be run under CLI\n";
    exit(1);
}

if ($_SERVER['argc'] == 2 &&
      in_array($_SERVER['argv'][1], array('--help', '-help', '-h', '-?')) 
      || 
      $_SERVER['argc'] < 2) {

    echo "<Description>\n\n";
    echo "Usage:      {$_SERVER['argv'][0]} <language code>\n";
    echo "            --help, -help, -h, -?      - to get this help\n";
    die;

}

$fullpath_dir = rtrim($_SERVER['argv'][1], '/');

if (!is_dir($fullpath_dir)) {
    echo "ERROR: ($fullpath_dir) is not a directory.\n";
    exit(1);
}

function get_modified($item) {
    if (!$item || strpos('MA', $item[0]) === false) {
        return false;
    }

    return trim(substr($item, 1));
}


$escaped_dir = escapeshellarg($fullpath_dir);
exec("svn st $escaped_dir", $svn_status);

$changed_files = array_values(array_filter(array_map('get_modified', $svn_status)));

if (!$changed_files) {
    echo 'NOTHING TO CHECK, EXITING.';
    exit(0);
}


require_once dirname(__FILE__) . '/ToolsError.php';
$tools = new ToolsError();

$found_errors = array();

foreach ($changed_files as $item) {

    $en_version = substr_replace($item, 'en', 0, strlen($fullpath_dir));

    $tools->clearErrors();

    $tools->setParams(file_get_contents($en_version), file_get_contents($item), $fullpath_dir);
    $tools->run();
    $errors = $tools->getErrors();

    foreach ($errors as $error) {
        $found_errors[$item][$error['type']][] = $error;
    }

    if (!$errors) {
        echo "CLEAN FILE: $item", PHP_EOL;
    }

}

$lang_upper = strtoupper($fullpath_dir);

foreach ($found_errors as $file => $types) {
    echo "FILE: $file", PHP_EOL;
    foreach ($types as $type => $errors) {
        echo "   TYPE: $type", PHP_EOL;
        foreach ($errors as $item) {
            echo "      EN: $item[value_en] $lang_upper: $item[value_lang]", PHP_EOL;
            if (isset($item['additional_en'])) {
                $max_length = max(count($item['additional_en']), count($item['additional_lang']));
                for ($i = 0; $i < $max_length; $i++) {
                    $add_en = isset($item['additional_en'][$i]) ? $item['additional_en'][$i] : '';
                    $add_lang = isset($item['additional_lang'][$i]) ? $item['additional_lang'][$i] : '';
                    $match = $add_en === $add_lang ? 'MATCH' : '';
                    echo "$match        ADDITIONAL_EN: $add_en  |  ADDITIONAL_$lang_upper: $add_lang", PHP_EOL;
                }
            }
        }
    }
}

exit(count($found_errors) ? 1 : 0);

