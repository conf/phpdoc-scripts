<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


function get_install_dir($current_dir) {
	global $argv;

	if (count($argv) < 2) {
		die("Usage: php install.php <phpdoc-dir>\n");
	}

	$install_dir = realpath($argv[1]);

	if (!is_dir($install_dir) || !is_writable($install_dir)) {
		die("Installation dir should be writable!\n");
	}
	
	if ($current_dir === $install_dir) {
		die("Installation dir should not be the same as current dir!\n");
	}

	return $install_dir;
}

$current_dir = realpath(dirname(__FILE__));
$install_dir = get_install_dir($current_dir);

$file_iterator = new RecursiveIteratorIterator(
	new RecursiveRegexIterator(
		new RecursiveDirectoryIterator($current_dir, RecursiveDirectoryIterator::SKIP_DOTS), 
		'/^((?!\.git|.idea|README).)+$/'
	)
);

foreach($file_iterator as $key => $item) {
	$destination = str_replace($current_dir, $install_dir, $item->getRealpath());
	echo "Creating symlink: ", $item->getRealpath(), " => " , $destination, PHP_EOL;
	symlink($item->getRealpath(), $destination);
}

