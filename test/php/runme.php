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



// Test 6
$input = [ 'a' => null, 'b' => 1, 'c' => 1 ]; // null not allowed
$code = "";
$code.= "string ta,tb,tc\n";
$code.= "ta = typeof( a )\n";
$code.= "tb = typeof( b )\n";
$code.= "tc = typeof( c )\n";
require $execute;
do_assert();
require $clean;



//
// Variable definition
//



// Test 7
$input = [];
$code = "";
$code.= "int a\n";
$code.= "b = a\n"; // `b` not defined
require $execute;
do_assert();
require $clean;



// Test 8
$input = [];
$code = "";
$code.= "int a\n";
$code.= "b = a + 1\n"; // `b` not defined
require $execute;
do_assert();
require $clean;



// Test 9
$input = [];
$code = "";
$code.= "int a\n";
$code.= "a = b + 1\n"; // `b` not defined
require $execute;
do_assert();
require $clean;



// Test 10
$input = [];
$code = "";
$code.= "int a\n";
$code.= "a = len( b )\n"; // `b` not defined
require $execute;
do_assert();
require $clean;



// Test 11
$input = [];
$code = "";
$code.= "string a\n";
$code.= "int b\n";
$code.= "b = len( a )\n"; // `a` defined but not set
require $execute;
do_assert();
require $clean;



// Test 12
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "float f\n";
$code.= "string ts,ti,tf\n";
$code.= "ts = typeof( s )\n"; // variables are not set
$code.= "ti = typeof( i )\n"; // but type is defined
$code.= "tf = typeof( f )\n";
require $execute;
do_assert( @$ts === 'string' && @$ti === 'int' && @$tf === 'float' );
require $clean;



// Test 13
$input = [ 'a' => 1 ];
$code = "";
$code.= "int a\n"; // `a` already defined and set
require $execute;
do_assert();
require $clean;



//
// Type casting
//



// Test 14
$input = [ 'a' => 10 ];
$code = "";
$code.= "a = float( a )\n"; // allowed
require $execute;
do_assert( @$a === 10.0 );
require $clean;



// Test 15
$input = [ 'a' => 12.3 ];
$code = "";
$code.= "a = int( a )\n"; // not allowed
require $execute;
do_assert();
require $clean;



// Test 16
$input = [ 'a' => 1 ];
$code = "";
$code.= "string b";
$code.= "b = \"foo\"\n";
$code.= "a = b\n"; // type change not allowed
require $execute;
do_assert();
require $clean;



// Test 17
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



// Test 18
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



// Test 19
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



// Test 20
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



// Test 21
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



// Test 22
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

// Test 23
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"\\t\\t\\r  \\n \\n foo \\n\\n  \\n\" )";
require $execute;
do_assert( @$s === 'foo' );
require $clean;



// Test 24
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"  \\n \\n foo bar\\n\\n  \\n\" )\n";
require $execute;
do_assert( @$s === 'foo bar' );
require $clean;



// Test 25
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"foo bar\" )";
require $execute;
do_assert( @$s === 'foo bar' );
require $clean;



// Test 26
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( \"\" )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 27
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = trim( 100 )";
require $execute;
do_assert();
require $clean;



// Test 28
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



// Test 29
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, 4 )";
require $execute;
do_assert( @$s === 'some' );
require $clean;



// Test 30
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 5, 4 )";
require $execute;
do_assert( @$s === 'text' );
require $clean;



// Test 31
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 5, 1000 )";
require $execute;
do_assert( @$s === 'text' );
require $clean;



// Test 32
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 20, 30 )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 33
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", -4, 4 )";
require $execute;
do_assert( @$s === 'text' );
require $clean;



// Test 34
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 5, -2 )";
require $execute;
do_assert( @$s === 'te');
require $clean;



// Test 35
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( 100, 0, 1 )";
require $execute;
do_assert();
require $clean;



// Test 36
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", \"0\", 4 )";
require $execute;
do_assert();
require $clean;



// Test 37
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, \"4\" )";
require $execute;
do_assert();
require $clean;



// Test 38
$input = [];
$code = "";
$code.= "int s\n";
$code.= "s = substring( \"some text\", 5, -2 )";
require $execute;
do_assert();
require $clean;



// Test 39
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 9, -5 )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 40
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, 0 )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 41
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, 1000 )";
require $execute;
do_assert( @$s === 'some text' );
require $clean;



// Test 42
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = substring( \"some text\", 0, -1000 )";
require $execute;
do_assert( @$s === '' );
require $clean;



//
// Between()
//



// Test 43
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"[[\", \"))\" )";
require $execute;
do_assert( @$s === 'bar' );
require $clean;



// Test 44
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar[[boo))   baz\", \"[[\", \"))\" )";
require $execute;
do_assert( @$s === 'boo' );
require $clean;



// Test 45
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar))   [[BOO)) baz\", \"[[\", \"))\" )";
require $execute;
do_assert( @$s === 'bar' );
require $clean;



// Test 46
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar))   ))baz\", \"\", \"))\" )";
require $execute;
do_assert( @$s === 'foo   [[bar' );
require $clean;



// Test 47
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar[[))   baz\", \"[[\", \"\" )";
require $execute;
do_assert( @$s === '))   baz' );
require $clean;



// Test 48
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"\", \"\" )";
require $execute;
do_assert( @$s === 'foo   [[bar))   baz' );
require $clean;



// Test 49
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"\", \"\", \"\" )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 50
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"**\", \"))\" )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 51
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"[[\", \"--\" )";
require $execute;
do_assert( @$s === '' );
require $clean;



// Test 52
$input = [];
$code = "";
$code.= "int s\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"[[\", \"))\" )";
require $execute;
do_assert();
require $clean;



// Test 53
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( \"foo   11bar))   baz\", 11, \"))\" )";
require $execute;
do_assert();
require $clean;



// Test 54
$input = [];
$code = "";
$code.= "string s\n";
$code.= "s = between( 10.4E2, \"[[\", \"))\" )";
require $execute;
do_assert();
require $clean;



// Test 55
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"[[\", \"))\", i )";
require $execute;
do_assert( @$s === 'bar' && @$i === 1 );
require $clean;



// Test 56
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar[[boo))   baz\", \"[[\", \"))\", i )";
require $execute;
do_assert( @$s === 'boo' && @$i === 1 );
require $clean;



// Test 57
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar))   [[BOO)) baz\", \"[[\", \"))\", i )";
require $execute;
do_assert( @$s === 'bar' && @$i === 1 );
require $clean;



// Test 58
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar))   ))baz\", \"\", \"))\", i )";
require $execute;
do_assert( @$s === 'foo   [[bar' && @$i === 1 );
require $clean;



// Test 59
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar[[))   baz\", \"[[\", \"\", i )";
require $execute;
do_assert( @$s === '))   baz' && @$i === 1 );
require $clean;



// Test 60
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"\", \"\", i )";
require $execute;
do_assert( @$s === 'foo   [[bar))   baz' && @$i === 1 );
require $clean;



// Test 61
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"\", \"\", \"\", i )";
require $execute;
do_assert( @$s === '' && @$i === 1 );
require $clean;



// Test 62
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"**\", \"))\", i )";
require $execute;
do_assert( @$s === '' && @$i === 0 );
require $clean;



// Test 63
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"[[\", \"--\", i )";
require $execute;
do_assert( @$s === '' && @$i === 0 );
require $clean;



// Test 64
$input = [];
$code = "";
$code.= "int s\n";
$code.= "s = between( \"foo   [[bar))   baz\", \"[[\", \"))\", i )";
require $execute;
do_assert();
require $clean;



// Test 65
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( \"foo   11bar))   baz\", 11, \"))\", i )";
require $execute;
do_assert();
require $clean;



// Test 66
$input = [];
$code = "";
$code.= "string s\n";
$code.= "int i\n";
$code.= "s = between( 10.4E2, \"[[\", \"))\", i )";
require $execute;
do_assert();
require $clean;


//
// Position()
//



// Test 67
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"    foo  \", \"foo\" )";
require $execute;
do_assert( @$i === 4 );
require $clean;



// Test 68
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"    foo  foo\", \"foo\" )";
require $execute;
do_assert( @$i === 4 );
require $clean;



// Test 69
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"    foo  \", \"fooo\" )";
require $execute;
do_assert( @$i === -1 );
require $clean;



// Test 70
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"    foo  \", \"\" )";
require $execute;
do_assert( @$i === -1 );
require $clean;



// Test 71
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"\", \"foo\" )";
require $execute;
do_assert( @$i === -1 );
require $clean;



// Test 72
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"\", \"\" )";
require $execute;
do_assert( @$i === -1 );
require $clean;



// Test 73
$input = [];
$code = "";
$code.= "string i\n";
$code.= "i = position( \"    foo  \", \"foo\" )";
require $execute;
do_assert();
require $clean;



// Test 74
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = position( \"    10  \", 10 )";
require $execute;
do_assert();
require $clean;


//
// Len()
//



// Test 75
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = len( \"    foo  \" )";
require $execute;
do_assert( @$i === 9 );
require $clean;



// Test 76
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = len( \"\" )";
require $execute;
do_assert( @$i === 0 );
require $clean;



// Test 77
$input = [];
$code = "";
$code.= "string i\n";
$code.= "i = len( \"    foo  \" )";
require $execute;
do_assert();
require $clean;



// Test 78
$input = [];
$code = "";
$code.= "int i\n";
$code.= "i = len( 10 )";
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
sleep( 1 );
echo "Testing JavaScript interpreter... ";
sleep( 1 );
exec( "osascript -e 'tell application \"Safari\" to open ( (\"$jstest\") as POSIX file)'" );
exec( "osascript -e 'set bounds of first window of application \"Safari\" to {50, 100, 800, 800}'" );
echo "done.\n";


