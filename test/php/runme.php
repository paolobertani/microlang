<?php
define( 'ROOT_PATH', __DIR__ );
define( 'SCRP_PATH', __FILE__ );

require_once ROOT_PATH . "/include/test.php";



//
// Empty code
//



// Test 1
$input = [];
$code = "";
require $execute;
do_assert( @$output === [] );
require $clean;



// Test 2
$input = [];
$code = "   \n\n  \n ";
require $execute;
do_assert( @$output === [] );
require $clean;



// Test 3
$input = [ 'a' => 1 ];
$code = "   \n\n  \n ";
require $execute;
do_assert( @$a === 1 );
require $clean;



//
// Type detection of input data
//



// Test 4
$input = [ 'a' => 'text', 'b' => 10, 'c' => 12.3 ];
$code = "";
$code.= "string ta,tb,tc\n";
$code.= "ta = typeof( a )\n";
$code.= "tb = typeof( b )\n";
$code.= "tc = typeof( c )\n";
require $execute;
do_assert( @$ta === 'string' && @$tb === 'int' && @$tc === 'float' );
require $clean;



// Test 5
$input = [ 'a' => true, 'b' => 1, 'c' => 1 ]; // boolean not allowed
$code = "";
$code.= "string ta,tb,tc\n";
$code.= "ta = typeof( a )\n";
$code.= "tb = typeof( b )\n";
$code.= "tc = typeof( c )\n";
require $execute;
do_assert();
require $clean;



//
// Type casting
//



// Test 6
$input = [ 'a' => 10 ];
$code = "";
$code.= "a = float( a )\n"; // allowed
require $execute;
do_assert( @$a === 10.0 );
require $clean;



// Test 7
$input = [ 'a' => 12.3 ];
$code = "";
$code.= "a = int( a )\n"; // not allowed
require $execute;
do_assert();
require $clean;



// Test 8
$input = [ 'a' => 1 ];
$code = "";
$code.= "string b";
$code.= "b = \"foo\"\n";
$code.= "a = b\n"; // type change not allowed
require $execute;
do_assert();
require $clean;



// Test 9
$input = [];
$code = "";
$code.= "int    stoi,itoi,ftoi\n";
$code.= "float  stof,itof,ftof\n";
$code.= "string stos,itos,ftos\n";
$code .= "\n";
$code.= "string s = \"10.8\"\n";
$code.= "stoi = int(    s )\n";
$code.= "stof = float(  s )\n";
$code.= "stos = string( s )\n";
$code.= "\n";
$code.= "int i = 10E2\n";
$code.= "itoi = int(    i )\n";
$code.= "itof = float(  i )\n";
$code.= "itos = string( i )\n";
$code.= "\n";
$code.= "float f = 10.0e-2\n";
$code.= "ftoi = int(    f )\n";
$code.= "ftof = float(  f )\n";
$code.= "ftos = string( f )\n";
require $execute;
do_assert( @$stoi === 10 && @$stof === 10.8 && @$stos === "10.8" && @$itoi === 1000 && @$itof === 1000.0 && @$itos === "1000" && @$ftoi === 0 && @$ftof === 0.1 && @$ftos === "0.1" );
require $clean;




//
// Numeric type detection
//



// Test 10
$input = [];
$code = "";
$code.= "int i\n";
$code.= "string ti\n";
$code.= "i = -10e2\n";
$code.= "ti= typeof( i )\n";
$code.= "\n";
$code.= "float f\n";
$code.= "string tf\n";
$code.= "f = .0\n";
$code.= "tf= typeof( f )\n";
require $execute;
do_assert( @$i === -1000 && @$ti === 'int' && @$f === 0.0 && @$tf === 'float' );
require $clean;



//
// String too long
//



// Test 11
$input = [];
$code = "";
$code.= "int a,b\n";
$code.= "a = 10\n";
$code.= "b = 1\n";
$code.= "loop:\n";
$code.= "  if b > 20 then finish\n";
$code.= "  a = a + a\n";
$code.= "  b = b + 1\n";
$code.= "goto loop\n";
$code.= "\n";
$code.= "finish:\n";
$code.= "exit\n";
require $execute;
do_assert( @$a === 10485760 );
require $clean;



// Test 12
$input = [];
$code = "";
$code.= "string a = \"xxxxxxxxxx\"\n";
$code.= "int b = 1\n";
$code.= "loop:\n";
$code.= "  if b > 20 then finish\n";
$code.= "  a = a + a\n";
$code.= "  b = b + 1\n";
$code.= "goto loop\n";
$code.= "\n";
$code.= "finish:\n";
$code.= "exit\n";
require $execute;
do_assert();
require $clean;



//
// Int overflow
//



// Test 13
$input = [];
$code = "";
$code.= "int a = 10\n";
$code.= "int b = 1\n";
$code.= "loop:\n";
$code.= "  if b > 100 then finish\n";
$code.= "  a = a + a\n";
$code.= "  b = b + 1\n";
$code.= "goto loop\n";
$code.= "\n";
$code.= "finish:\n";
$code.= "exit\n";
require $execute;
do_assert();
require $clean;



// Test 14
$input = [];
$code = "";
$code.= "int a = -10\n";
$code.= "int b = 1\n";
$code.= "loop:\n";
$code.= "  if b > 100 then finish\n";
$code.= "  a = a + a\n";
$code.= "  b = b + 1\n";
$code.= "goto loop\n";
$code.= "\n";
$code.= "finish:\n";
$code.= "exit\n";
require $execute;
do_assert();
require $clean;



//
// Trim()
//

// Test 15
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"\\t\\t\\r  \\n \\n foo \\n\\n  \\n\" )";
require $execute;
do_assert( @$s === 'foo' );
require $clean;



// Test 16
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"  \\n \\n foo bar\\n\\n  \\n\" )\n";
require $execute;
do_assert( @$s === 'foo bar' );
require $clean;



// Test 17
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"foo bar\" )";
require $execute;
do_assert( @$s === 'foo bar' );
require $clean;



// Test 18
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"\" )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 19
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( 100 )";
require $execute;
do_assert();
require $clean;



// Test 20
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"\" )";
require $execute;
do_assert( @$s === '' );
require $clean;




//
// Substring()
//



// Test 21
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, 4 )";
require $execute;
do_assert( @$s === 'some' );
require $clean;



// Test 22
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 5, 4 )";
require $execute;
do_assert( @$s === 'text' );
require $clean;



// Test 23
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 5, 1000 )";
require $execute;
do_assert( @$s === 'text' );
require $clean;



// Test 24
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 20, 30 )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 25
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", -4, 4 )";
require $execute;
do_assert( @$s === 'text' );
require $clean;



// Test 26
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 5, -2 )";
require $execute;
do_assert( @$s === 'te');
require $clean;



// Test 27
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( 100, 0, 1 )";
require $execute;
do_assert();
require $clean;



// Test 28
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", \"0\", 4 )";
require $execute;
do_assert();
require $clean;



// Test 29
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, \"4\" )";
require $execute;
do_assert();
require $clean;










//
//
//

echo chr(27)."[2K\rAll test passed!\n";



//
// on macOS attemp to open and run JavaScript tests with Safari
//

$output = [];
$result_code = 0;
$osascript = exec("which osascript", $output, $result_code );
if( $result_code !== 0 || $osascript === false || strpos( $osascript, "osascript" ) === false ) exit(0);

$jstest =  ROOT_PATH . "/../js/openme.html";
sleep( 2 );
echo "Testing JavaScript interpreter... ";
exec( "osascript -e 'tell application \"Safari\" to open ( (\"$jstest\") as POSIX file)'" );
exec( "osascript -e 'set bounds of first window of application \"Safari\" to {50, 100, 800, 800}'" );
echo "done.\n";


