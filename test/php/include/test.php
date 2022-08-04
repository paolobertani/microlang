<?php
$execute = ROOT_PATH . '/include/execute.php';
$clean   = ROOT_PATH . '/include/clean.php';

$microlang_test_number = 1;
$output = [];
$microlang_test_count = substr_count( file_get_contents( SCRP_PATH ), 'require $execute;' );

require $clean;

require_once ROOT_PATH . '/../../microlang.php';

function do_assert( $assertion )
{
    global $microlang_test_number;
    global $microlang_test_count;
    global $input;
    global $output;
    global $code;
    global $error;


    // assertion is true

    $trace = debug_backtrace();
    $line = $trace[0]['line'];

    $assertion_str = str_replace( "@$", "", mb_substr( explode( "\n", file_get_contents( SCRP_PATH ) )[$line - 1], 11, -3 ) );

    if( $assertion )
    {
        if( mb_strpos( $assertion_str, 'error' ) === false && $error !== '' )
        {
            echo "\nTest $microlang_test_number:$microlang_test_count failed with microlang error: assertion line = $line\n\n";
        }
        else
        {
            $microlang_test_number++;
            return;
        }
    }

    $input_str = "";
    foreach( $input as $k => $v )
    {
        $q = is_string( $v ) ?  '"' : '';
        $input_str = "$k = $q$v$q\n";
    }
    $input_str = trim( $input_str );

    $output_str = "";
    foreach( $output as $k => $v )
    {
        $q = is_string( $v ) ? '"' : '';
        $output_str = "$k = $q$v$q\n";
    }
    $output_str = trim( $output_str );

    echo "\nTest $microlang_test_number:$microlang_test_count failed: assertion line = $line\n\n";
    echo "Code:\n$code\n\n";
    echo "Error: \"$error\"\n\n";
    echo "Input:\n$input_str\n\n";
    echo "Output:\n$output_str\n\n";

    echo "Assertion: $assertion_str\n\n";

    if( mb_strpos( $assertion, 'error' ) === false && $error !== '' )
    {
        echo "Unexpected error!\n\n";
    }

    exit;
}

function checkvars( $reserved, $io, $where )
{
    foreach( $io as $k => $v )
    {
        if( in_array( $k, $reserved ) || substr( $k, 0, 10 ) == 'microlang_' )
        {
            $trace = debug_backtrace();
            $line = $trace[1]['line'];
            echo "\nTest-Suite reserved variable name `$k` used into $where: line $line\n";
            exit;
        }
    }
}