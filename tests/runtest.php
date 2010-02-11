#!//usr/bin/php -q
<?php

$path    = dirname ( __FILE__ );
$phpunit = `which phpunit`; $phpunit = trim ( $phpunit );
$testname= $argv[1];

if ( !in_array ( 'no_comp', explode ( ',', $argv[2] ) ) )
{
	$completion = " --coverage-html {$path}/coverage ";
} else {
	$completion = '';
}

$exec = "{$phpunit} --colors --verbose {$completion} --log-tap ./log-{$testname}-tap.txt --testdox-html ./log-{$testname}.html --testdox --syntax-check ./{$testname}.php";
print $exec; exit;
$ret = array ();
exec ( $exec, $ret );

echo implode ( "\n", $ret );

if ( in_array ( 'verbose', explode ( ',', $argv[2] ) ) )
{
	echo "\n";
	echo file_get_contents ( './log-mvcha_functions-tap.txt' );
}
