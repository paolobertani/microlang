<?php
$execute = ROOT_PATH . '/include/execute.php';
$clean   = ROOT_PATH . '/include/clean.php';

$microlang_test_number = 1;
$output = [];

require $clean;

require_once ROOT_PATH . '/../../microlang.php';

$microlang_test_count = enumerate_tests();

require_once ROOT_PATH . "/include/javascript.php";

genarate_javascript();

function do_assert( $assertion = null )
{
    global $microlang_test_number;
    global $microlang_test_count;
    global $input;
    global $output;
    global $code;
    global $error;
    global $stage;

    $trace = debug_backtrace();
    $line = $trace[0]['line'];

    if( $assertion === 'a' || $assertion === 'e' )
    {
        if( $assertion === 'a' )     $assertion_str = "an error must occurr when analyzing code";
        elseif( $assertion === 'e' ) $assertion_str = "an error must occurr when executing code";
    }
    else
    {
        $assertion_str = str_replace( "@$", "", mb_substr( explode( "\n", file_get_contents( SCRP_PATH ) )[$line - 1], 11, -3 ) );
    }


    if( $assertion === true && $error === '' )
    {
        $microlang_test_number++;
        return;
    }

    if( $assertion === false && $error === '' )
    {
        echo "\n\nTest $microlang_test_number:$microlang_test_count failed: assertion line = $line\n\n";
    }

    if( ( $assertion === true || $assertion === false ) && $error !== '')
    {
        echo "\n\nTest $microlang_test_number:$microlang_test_count FAILED WITH ERROR when $stage code: assertion line = $line\n\n";
    }

    if( $assertion === 'a' && $error === '' && $stage === 'analyzing' )
    {
        echo "\n\nTest $microlang_test_number:$microlang_test_count an error has NOT been detected when analyzing code: assertion line = $line\n\n";
        $error = "an error has not occurred";
    }

    if( $assertion === 'e' && $error === '' && $stage === 'executing' )
    {
        echo "\n\nTest $microlang_test_number:$microlang_test_count an error has NOT been detected when executing code: assertion line = $line\n\n";
        $error = "an error has not occurred";
    }

    if( $assertion === 'a' && $error !== '' && $stage === 'analyzing' )
    {
        $microlang_test_number++;
        return;
    }

    if( $assertion === 'e' && $error !== '' && $stage === 'executing' )
    {
        $microlang_test_number++;
        return;
    }

    if( $assertion === 'a' && $error !== '' && $stage === 'executing' )
    {
        echo "\n\nTest $microlang_test_number:$microlang_test_count an error has been detected when executing code but should have been detected when analyzing: assertion line = $line\n\n";
    }

    if( $assertion === 'e' && $error !== '' && $stage === 'analyzing' )
    {
        echo "\n\nTest $microlang_test_number:$microlang_test_count an error has been detected when analyzing code but should have been detected when executing: assertion line = $line\n\n";
    }



    $input_str = "";
    foreach( $input as $k => $v )
    {
        $q = is_string( $v ) ?  '"' : '';
        $input_str .= "$k = $q$v$q\n";
    }
    $input_str = trim( $input_str );
    if( $input_str === '' ) $input_str = "(none)";

    $output_str = "";
    foreach( $output as $k => $v )
    {
        $q = is_string( $v ) ? '"' : '';
        $output_str .= "$k = $q$v$q\n";
    }
    $output_str = trim( $output_str );
    if( $output_str === '' ) $output_str = "(none)";

    echo "Code:\n" . number_code( $code ) . "\n\n";
    echo "Error: \"$error\"\n\n";
    echo "Input:\n$input_str\n\n";
    echo "Output:\n$output_str\n\n";

    echo "Assertion: $assertion_str\n\n";

    exit;
}

function check_vars( $reserved, $io, $where )
{
    foreach( $io as $k => $v )
    {
        if( in_array( $k, $reserved ) || substr( $k, 0, 10 ) == 'microlang_' )
        {
            $trace = debug_backtrace();
            $line = $trace[1]['line'];
            echo "\n\nTest-Suite reserved variable name `$k` used into $where: line $line\n";
            exit;
        }
    }
}

function number_code( $code )
{
    $code = trim( $code );
    $code = explode( "\n", $code );
    $out = "";
    $i = 1;
    foreach( $code as $c )
    {
        $out .= "[ " . str_pad( $i++, 2, " ", STR_PAD_LEFT) . " ] $c\n";
    }
    $out = trim( $out );
    return $out;
}

function enumerate_tests()
{
    $index = 1;
    $code = file_get_contents( SCRP_PATH );
    $code = explode( "\n", $code );
    foreach( $code as &$line )
    {
        if( substr( $line, 0, 7 ) === '// Test')
        {
            $line = "// Test $index";
            $index++;
        }
    } unset( $line );
    $code = implode( "\n", $code );
    file_put_contents( SCRP_PATH, $code );
    return $index - 1;
}