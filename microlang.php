<?php

//
// FreeBSD 2-clause license
//
// microlang.php
//
// A php microlang interpreter
//
// microlang version 1.1
// php interpreter version 1.1.1
//
// Copyright (c) 2022, Paolo Bertani - Kalei S.r.l.
//
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
//
// Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
// Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer
// in the documentation and/or other materials provided with the distribution.
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING,
// BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
// OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
// OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
//

function microlang( $code, &$vars, $max_iterations = 1000 )
{
    $keywords = [
        'goto',
        'gosub',
        'return',
        'exit',
        '=',
        'substring',
        'position',
        'between',
        'trim',
        'len',
        'int',
        'float',
        'string',
        'typeof',
        '+',
        '-',
        '*',
        '/',
        '%',
        'if', 'then', 'else', '==', '!=', '>', '<', '>=', '<=',
        ',',
        '(',
        ')'
    ];

    $max_str_len = 1024*1024;

    $labels = [];
    $stack = [];

    if( ! isset( $vars ) )
    {
        $vars = [];
    }

    if( ! is_array( $vars ) )
    {
        return "input data provided is not array";
    }


    // check and store input types

    $typs = [];

    foreach( $vars as $key => $value )
    {
        if( is_int( $value ) ) $typs[$key] = 'int';
        elseif( is_string( $value ) ) $typs[$key] = 'string';
        elseif( is_float( $value ) ) $typs[$key] = 'float';
        else return "unsupported variable type: $key";
    }


    // convert windows linefeeds to unix linefeeds if any

    $code = str_replace( "\r\n", "\n", $code );


    // explode code

    $lines = explode( "\n", $code );



    // remove comments

    $n = count( $lines );
    for( $i = 0; $i < $n; $i++)
    {
        $l = $lines[$i];
        $idx = mb_strpos( $l, '//' );
        if( $idx !== false )
        {
            $l = mb_substr( $l, 0, $idx );
        }
        $l = trim( $l );
        $lines[$i] = $l;
    }


    // tokenize

    $newlines = $lines;
    $lines = [];
    $y = -1;
    $y1b = $y + 1;
    $m = count( $newlines );
    for( $k = 0; $k < $m; $k++)
    {
        $l = $newlines[ $k ];

        $y++;
        $y1b = $y + 1;

        $parts = microlang_splitline( $l, $error );
        if( $error !== '' ) return "$error$y1b";

        $tokens = [];
        $n = count($parts);
        for( $i = 0; $i < $n; $i++ )
        {
            $p = $parts[$i];


            // Keywords

            if( in_array( $p, $keywords ) )
            {
                $tokens[] = ['type' => 'keyword', 'symbol' => $p, 'value' => null, 'vtype' => null ];

                if( $i < $n - 1 && $parts[ $i + 1 ] === '=' ) return "keywords cannot be used for variable names ($p): $y1b";

                continue;
            }


            // Labels

            if( substr( $p, -1, 1 ) === ":" && $i === 0 )
            {
                $p = mb_substr( $p, 0, -1 );
                if( in_array( $p, $keywords ) ) return "keywords cannot be used for label names ($p): $y1b";
                $tokens[] = ['type' => 'label', 'symbol' => $p, 'value' => $y, 'vtype' => null ];
                if( isset( $labels[$p] ) ) return "Label $p duplicate: $y1b";
                $labels[$p] = $y;
                continue;
            }

            if( substr( $p, -1, 1 ) === ":" && $i > 0 )
            {
                return "unexpected label: $y1b";
            }


            // Strings

            if( $p === '""' )
            {
                $tokens[] = ['type' => 'value', 'symbol' => null, 'value' => "", 'vtype' => 'string' ];
                continue;
            }

            if( substr( $p, 0, 1 ) === '"' )
            {
                if( substr( $p, -1, 1 ) === '"' && mb_strlen( $p ) > 1 )
                {
                    $tokens[] = ['type' => 'value', 'symbol' => null, 'value' => substr( $p, 1, -1 ), 'vtype' => 'string' ];
                    continue;
                }

                $value = substr( $p, 1, -1 );

                if( substr( $p, -1, 1 ) !== '"' ) return "string not closed: $y1b";

                $tokens[] = ['type' => 'value', 'symbol' => null, 'value' => $value, 'vtype' => 'string' ];
                continue;
            }


            // Integers

            if( preg_match( '/^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/', $p ) === 1 )
            {
                if( floatval( $p ) > PHP_INT_MAX || floatval( $p ) < PHP_INT_MIN ) return "overflow: $y1b";

                $tokens[] = ['type' => 'value', 'symbol' => null, 'value' => intval($p), 'vtype' => 'int' ];
                continue;
            }


            // Floats

            if( preg_match( '/^-?\d*\.\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/', $p ) === 1 )
            {
                $tokens[] = ['type' => 'value', 'symbol' => null, 'value' => floatval($p), 'vtype' => 'float' ];
                continue;
            }


            // Variable names

            $tokens[] = ['type' => 'variable', 'symbol' => $p, 'value' => null, 'vtype' => null ];

        }
        $lines[] = $tokens;
    }



    // execute

    $y = 0;
    $lines_count = count( $lines );
    $iter = 0;
    $vars['cast_failed'] = 0;

    while( true )
    {
        $done = false;

        $y1b = $y + 1;

        if( $iter > $max_iterations && $max_iterations !== 0 ) return "Max iterations exceeded: $y1b";

        $tok = $lines[ $y ];
        $tn = count( $tok );

        if( $tn === 0 ) $done = true;

        if( $tn === 1 && $tok[0]['type'] === 'label' ) $done = true;

        $tokens = [];

        for( $i = 0; $i < $tn; $i++ )
        {
            $t = $tok[ $i ];

            if( $t['type'] === 'label' || $t['type'] === 'value' || $t['type'] === 'keyword' )
            {
                $tokens[] = $t;
            }
            elseif( $t['type'] === 'variable' )
            {
                if( isset( $vars[$t['symbol'] ] ) )
                {
                    $t['value'] = $vars[$t['symbol']];
                    $t['vtype'] = $typs[$t['symbol']];
                    $tokens[] = $t;
                }
                elseif( isset( $labels[$t['symbol']]) )
                {
                    $t['type'] = 'label';
                    $t['value'] = $labels[$t['symbol']];
                    $tokens[] = $t;
                }
                else
                {
                    $t['value'] = null;
                    $tokens[] = $t;
                }
            }
        }


        $t0s = $done ? '' : $tokens[0]['symbol'];


        // Goto

        if( ! $done && microlang_parse( $tokens, [ 'goto', ':' ] ) )
        {
            $done = true;

            $label = $tokens[1]['symbol'];

            if( isset( $labels[$label] ) )
            {
                $iter++;
                $y = $labels[$label];
                continue;
            }
            else return "unknown label $label: $y1b";
        }


        // Gosub

        if( ! $done && microlang_parse( $tokens, [ 'gosub', ':' ] ) )
        {
            $done = true;

            $label = $tokens[1]['symbol'];

            if( isset( $labels[$label] ) )
            {
                $stack[] = $y;
                $iter++;
                $y = $labels[$label];
                continue;
            }
            else return "unknown label $label: $y1b";
        }


        // Return

        if( ! $done && microlang_parse( $tokens, [ 'return' ] ) )
        {
            $done = true;

            if( count( $stack ) === 0 ) return "return without gosub: $y1b";

            $y = array_pop( $stack );
        }


        // Exit

        if( ! $done && microlang_parse( $tokens, [ 'exit' ] ) )
        {
            $done = true;

            break;
        }


        // Exit with error message

        if( ! $done && microlang_parse( $tokens, [ 'exit', '#' ] ) )
        {
            return $tokens[1]['value'] . '';
        }


        // = Assignment

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#' ] ) )
        {
            if( $t0s === 'cast_failed' ) return "`cast_failed` is a reserved variable name: $y1b";

            if( $tokens[2]['value'] === null ) return "undefined variable `{$tokens[2]['symbol']}`: $y1b";

            if( isset( $vars[$t0s] ) )
            {
                if( $typs[$t0s] === $tokens[2]['vtype'] )
                {
                    $vars[$t0s] = $tokens[2]['value'];
                }
                elseif( $typs[$t0s] === 'float' && $tokens[2]['vtype'] === 'int' )
                {
                    $vars[$t0s] = floatval( $tokens[2]['value'] );
                }
                else
                {
                    return "variable cannot change type: $y1b";
                }
            }
            else
            {
                $vars[$t0s] = $tokens[2]['value'];
                $typs[$t0s] = $tokens[2]['vtype'];
            }

            $done = true;
        }

        if( ! $done && microlang_parse( $tokens, [ 'K', '=', '#' ] ) )
        {
            return "keywords cannot be used for variable names ({$tokens[0]['symbol']}): $y1b";
        }

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'K' ] ) )
        {
            return "keyword unexpected at right side of assignment ({$tokens[2]['symbol']}): $y1b";
        }


        // Substring

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'substring', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?SII" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[6]['value'] < 0 || $tokens[8]['value'] < 0 ) return "substring accepts only positive index and length: $y1b";

            $rt = 'string';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = mb_substr( $tokens[4]['value'], $tokens[6]['value'], $tokens[8]['value'] );

            $done = true;
        }


        // Position

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'position', '(', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?SS" ); if( $err !== '' ) return $err . $y1b;

            $rt = 'int';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = mb_strpos( $tokens[4]['value'], $tokens[6]['value'] );

            if( $vars[$t0s] === false ) $vars[$t0s] = -1;

            $done = true;
        }


        // Replace

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'replace', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?SSS" ); if( $err !== '' ) return $err . $y1b;

            $rt = 'string';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = str_replace( $tokens[6]['value'], $tokens[8]['value'], $tokens[4]['value'] );

            if( mb_strlen( $vars[$t0s] ) > $max_str_len ) return "string too long: $y1b";

            $done = true;
        }


        // Between

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'between', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?SSS" ); if( $err !== '' ) return $err . $y1b;

            $rt = 'string';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            if( $tokens[6]['value'] === '' ) { $i1 = 0; } else { $i1 = mb_strpos( $tokens[4]['value'], $tokens[6]['value'] ); }

            if( $tokens[8]['value'] === '' ) { $i2 = mb_strlen( $tokens[4]['value'] ); } else { $i2 = mb_strpos( $tokens[4]['value'], $tokens[8]['value'] ); }

            $i1 = mb_strpos( $tokens[4]['value'], $tokens[6]['value'] );
            $i2 = mb_strpos( $tokens[4]['value'], $tokens[8]['value'] );

            if( $i1 === false || $i2 === false || $i2 < $i1 )
            {
                $vars[$t0s] = "";
            }
            else
            {
                $vars[$t0s] = mb_substr( $tokens[4]['value'], $i1 + mb_strlen( $tokens[6]['value'] ), $i2 - $i1 - mb_strlen( $tokens[6]['value'] ) );
            }

            $done = true;
        }


        // Trim

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'trim', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?S" ); if( $err !== '' ) return $err . $y1b;

            $rt = 'string';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = trim( $tokens[4]['value'], " " );

            $done = true;
        }



        // Len

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'len', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?S" ); if( $err !== '' ) return $err . $y1b;

            $rt = 'int';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = mb_strlen( $tokens[4]['value'] );

            $done = true;
        }


        // Typeof

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'typeof', '(', '#', ')' ] ) )
        {
            $rt = 'string';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = $tokens[4]['vtype'];

            $done = true;
        }


        // Int

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'int', '(', '#', ')' ] ) )
        {
            $rt = 'int';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            if( $tokens[4]['vtype'] === 'string' && preg_match( '/^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/', $tokens[4]['value'] ) === 0 )
            {
                $vars[$t0s] = (int)0;
                $vars['cast_failed'] = 1;
            }
            else
            {
                if( floatval( $vars[$tokens[4]['value']] ) > PHP_INT_MAX || floatval( $vars[$tokens[4]['value']] ) < PHP_INT_MIN )
                {
                    $vars[$t0s] = (int)0;
                    $vars['cast_failed'] = 1;
                }
                else
                {
                    $vars[$t0s] = intval( $tokens[4]['value'] );
                    $vars['cast_failed'] = 0;
                }
            }

            $done = true;
        }


        // Float

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'float', '(', '#', ')' ] ) )
        {
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== 'int' && $typs[$t0s] !== 'float' ) return "variable `$t0s` must be int or float: $y1b";
            $typs[$t0s] = 'float';

            if( $tokens[4]['vtype'] === 'string' && preg_match( '/^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/', $tokens[4]['value'] ) === 0 && preg_match( '/^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/', $tokens[4]['value'] ) === 0 )
            {
                $vars[$t0s] = (float)0;
                $vars['cast_failed'] = 1;
            }
            else
            {
                $vars[$t0s] = floatval( $tokens[4]['value'] );
                $vars['cast_failed'] = 0;
            }

            $done = true;
        }


        // String

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'string', '(', '#', ')' ] ) )
        {
            $rt = 'string';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = (string)$tokens[4]['value'];

            $done = true;
        }


        // + Sum

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '+', '#' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?*2" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[2]['vtype'] === 'string' )
            {
                $rt = 'string';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] . $tokens[4]['value'];

                if( mb_strlen( $vars[$t0s] ) > $max_str_len ) return "string too long: $y1b";
            }
            elseif( $tokens[2]['vtype'] === 'int' )
            {
                $rt = 'int';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] + $tokens[4]['value'];

                if( $vars[$t0s] > PHP_INT_MAX || $vars[$t0s] < PHP_INT_MIN ) return "overflow: $y1b";
            }
            elseif( $tokens[2]['vtype'] === 'float' )
            {
                $rt = 'float';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] + $tokens[4]['value'];
            }

            $done = true;
        }


        // - Diff

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '-', '#' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?N2" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[2]['vtype'] === 'int' )
            {
                $rt = 'int';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] - $tokens[4]['value'];

                if( $vars[$t0s] > PHP_INT_MAX || $vars[$t0s] < PHP_INT_MIN ) return "overflow: $y1b";
            }
            elseif( $tokens[2]['vtype'] === 'float' )
            {
                $rt = 'float';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] - $tokens[4]['value'];
            }

            $done = true;
        }


        // * Mult

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '*', '#' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?N2" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[2]['vtype'] === 'int' )
            {
                $rt = 'int';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] * $tokens[4]['value'];

                if( $vars[$t0s] > PHP_INT_MAX || $vars[$t0s] < PHP_INT_MIN ) return "overflow: $y1b";
            }
            elseif( $tokens[2]['vtype'] === 'float' )
            {
                $rt = 'float';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] * $tokens[4]['value'];
            }

            $done = true;
        }


        // / Div

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '/', '#' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?N2" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[4]['value'] === 0 ) return "division by zero: $y1b";

            if( $tokens[2]['vtype'] === 'int' )
            {
                $rt = 'int';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] / $tokens[4]['value'];

                if( $vars[$t0s] > PHP_INT_MAX || $vars[$t0s] < PHP_INT_MIN ) return "overflow: $y1b";

                $vars[$t0s] = intdiv( $tokens[2]['value'], $tokens[4]['value'] );
            }
            elseif( $tokens[2]['vtype'] === 'float' )
            {
                $rt = 'float';
                if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                $typs[$t0s] = $rt;

                $vars[$t0s] = $tokens[2]['value'] / $tokens[4]['value'];
            }

            $done = true;
        }


        // % Modulo

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '%', '#' ] ) )
        {
            $err = microlang_typecheck( $tokens, "?II" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[4]['value'] === 0 ) return "division by zero: $y1b";

            $rt = 'int';
            if( isset( $typs[$t0s] ) && $typs[$t0s] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
            $typs[$t0s] = $rt;

            $vars[$t0s] = $tokens[2]['value'] % $tokens[4]['value'];

            $done = true;
        }


        // If Then [Else]

        if( ! $done && ( microlang_parse( $tokens, [ 'if', '#', '~', '#', 'then', ':' ] ) || microlang_parse( $tokens, [ 'if', '#', '~', '#', 'then', ':', 'else', ':' ] ) ) )
        {
            $err = microlang_typecheck( $tokens, "?1" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[5]['value'] === null ) return "undefined label `" . $tokens[5]['symbol'] ."`: $y5b";
            if( $tn === 8 && $tokens[7]['value'] === null ) return "undefined label `" . $tokens[7]['symbol'] . "`: $y5b";

            if( $tokens[2]['symbol'] === '==' )
            {
                if( $tokens[1]['value'] == $tokens[3]['value'] )
                {
                    $y = $tokens[5]['value'];
                    $iter++;
                    continue;
                }
                else
                {
                    if( $tn === 8 && $tokens[7]['value'] !== null )
                    {
                        $y = $tokens[7]['value'];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $tokens[2]['symbol'] === '!=' )
            {
                if( $tokens[1]['value'] != $tokens[3]['value'] )
                {
                    $y = $tokens[5]['value'];
                    $iter++;
                    continue;
                }
                else
                {
                    if( $tn === 8 && $tokens[7]['value'] !== null )
                    {
                        $y = $tokens[7]['value'];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $tokens[2]['symbol'] === '>' )
            {
                if( $tokens[1]['value'] > $tokens[3]['value'] )
                {
                    $y = $tokens[5]['value'];
                    $iter++;
                    continue;
                }
                else
                {
                    if( $tn === 8 && $tokens[7]['value'] !== null )
                    {
                        $y = $tokens[7]['value'];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $tokens[2]['symbol'] === '<' )
            {
                if( $tokens[1]['value'] < $tokens[3]['value'] )
                {
                    $y = $tokens[5]['value'];
                    $iter++;
                    continue;
                }
                else
                {
                    if( $tn === 8 && $tokens[7]['value'] !== null )
                    {
                        $y = $tokens[7]['value'];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $tokens[2]['symbol'] === '<=' )
            {
                if( $tokens[1]['value'] <= $tokens[3]['value'] )
                {
                    $y = $tokens[5]['value'];
                    $iter++;
                    continue;
                }
                else
                {
                    if( $tn === 8 && $tokens[7]['value'] !== null )
                    {
                        $y = $tokens[7]['value'];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $tokens[2]['symbol'] === '>=' )
            {
                if( $tokens[1]['value'] >= $tokens[3]['value'] )
                {
                    $y = $tokens[5]['value'];
                    $iter++;
                    continue;
                }
                else
                {
                    if( $tn === 8 && $tokens[7]['value'] !== null )
                    {
                        $y = $tokens[7]['value'];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }
        }

        if( ! $done )
        {
            return "syntax error: $y1b";
        }

        $y++;

        if( $y >= $lines_count )
        {
            break;
        }
    }

    unset( $vars['cast_failed'] );

    return "";
}



function microlang_parse( $tokens, $expected )
{
    $n = count( $tokens );

    if( $n !== count( $expected ) ) return false;

    for( $i = 0; $i < $n; $i++ )
    {
        $t = $tokens[$i]['type'];
        $s = $tokens[$i]['symbol'];
        $e = $expected[$i];

        if( $e === '@' && $t === 'variable' ) continue;
        if( $e === ':' && $t === 'label'    ) continue;
        if( $e === '$' && $t === 'value'    ) continue;
        if( $e === '#' && ( $t === 'value' || $t === 'variable' ) ) continue;
        if( $e === '~' && ( $s === '>' || $s === '>' || $s === '<' || $s === '>=' || $s === '<=' || $s === '==' || $s === '!=' ) ) continue;
        if( $e === $s  && $t === 'keyword'  ) continue;

        return false;
    }

    return true;
}



function microlang_typecheck( $tok, $typses )
{
    $tokens = [];

    $n = count( $tok );

    for( $i = 0; $i < $n; $i++ )
    {
        if( $tok[$i]['type'] === 'variable' || $tok[$i]['type'] === 'value' )
        {
            $tokens[] = $tok[$i];
        }
    }

    $n = strlen( $typses );
    $t0 = $tokens[0]['vtype'];
    $t1 = $tokens[1]['vtype'];

    for( $i = 0; $i < $n; $i++ )
    {
        $c = $typses[$i];
        $t = $tokens[$i]['vtype'];

        if( $t === null && $c !== '?' ) return "undefined variable " . $tokens[$i]['symbol'] . ": ";

        if( $c === '*' ) continue;

        if( $c === 'S' && $t !== 'string' ) return "parameter " . ( $i + 1 ) . " must be string: ";
        if( $c === 'I' && $t !== 'int'    ) return "parameter " . ( $i + 1 ) . " must be int: ";
        if( $c === 'F' && $t !== 'float'  ) return "parameter " . ( $i + 1 ) . " must be float: ";
        if( $c === 'N' && $t === 'string' ) return "parameter " . ( $i + 1 ) . " must be int or float: ";
        if( $c === '1' && $t !== $t0      ) return "operands must be of the same type: ";
        if( $c === '2' && $t !== $t1      ) return "operands must be of the same type: ";
    }

    return "";
}



function microlang_splitline( $line, &$error )
{
    $error = "";

    $parts = [];

    $n = mb_strlen( $line );
    $part = "";
    $s = ' '; // currently parsing: ' ' nothing, 'o' operator, 's' string, 'n' number, 'y' symbol (keyword, variable, label)
    $p = ' '; // number; currently parsing: ' ' not a number, 'i' integer part, 'd' decimal part, 'e' exponent

    for( $i = 0; $i < $n; $i++ )
    {
        $c  = mb_substr( $line, $i, 1 );
        $c2 = mb_substr( $line, $i, 2 );
        $cn = mb_substr( $line, $i + 1, 1 );
        $cp = $i > 0 ? mb_substr( $line, $i - 1, 1 ) : '';

        if( $c === ' ' )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
                $part = '';
            }

            $p = ' ';
            $s = ' ';
            continue;
        }

        if( strpos( "(,)", $c ) !== false )
        {
            if( $s === 's' )
            {
                $part += $c;
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
            }

            $part = $c;
            $parts[] = $part;

            $part = "";

            $p = ' ';
            $s = ' ';
            continue;
        }

        if( $c === "\\" )
        {
            if( $s !== 's' )
            {
                $error = "unexpected escape character `$c`: ";
                return $parts;
            }

            if( $c2 === "\\\\" || $c2 === "\\n" || $c2 === "\\r" || $c2 === "\\t" || $c2 === "\\\"" )
            {
                $part .= $c2;
                $i++;
                continue;
            }

            $error = "unrecognized escape sequence `$c2`: ";
            return $parts;
        }

        if( $c === '"' )
        {
            if( $s === 's' )
            {
                $part .= $c;
                $parts[] = $part;
                $part = '';
                $s = ' ';
                $p = ' ';
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
                $part = "";
            }

            $part .= $c;
            $s = 's';
            $p = ' ';
            continue;
        }

        if( strpos( "0123456789", $c ) !== false )
        {
            if( $s === 's' || $s === 'y' || $s === 'n' )
            {
                $part .= $c;
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
                $part = "";
            }

            $part .= $c;
            $s = 'n';
            $p = 'i';
            continue;
        }

        if( $c === '.' )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $s === 'n' )
            {
                if( $p === 'i' )
                {
                    $part .= c;
                    $p = 'd';
                    continue;
                }
                else
                {
                    $error['msg'] = "unexpected character `$c`: ";
                    return parts;
                }
            }

            if( $s === 'o' || $s === 'y' )
            {
                if( $part !== '' )
                {
                    $parts[] = $part;
                    $part = "";
                }
            }

            if( strpos( "0123456789", $cn ) !== false )
            {
                $part .= "0" + $c;
                $s = 'n';
                $p = 'd';
                continue;
            }

            $error['msg'] = "unexpected character `$c`: ";
            return parts;
        }

        if( $c === '-' )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $s === ' ' || $s === 'y' || $s === 'o' )
            {
                if( $part !== '' )
                {
                    $parts[] = $part;
                    $part = "";
                }

                $part .= $c;

                if( strpos( "0123456789", $cn ) !== false )
                {
                    $s = 'n';
                    $p = 'i';
                    continue;
                }

                if( $cn === '.' )
                {
                    $s = 'n';
                    $p = 'i';
                    continue;
                }

                $parts[] = $part;
                $part = "";
                $s = ' ';
                $p = ' ';
                continue;
            }

            if( $s === 'n' && $p === 'e' && ( $cp === 'e' || $cp === 'E' ) )
            {
                $part .= $c;
                continue;
            }
        }

        if( $c === 'e' || $c === 'E' )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $s === 'n' && ( $p === 'i' || $p === 'd' ) )
            {
                $part .= $c;
                $p = 'e';
                continue;
            }

            if( $s === 'y' )
            {
                $part .= $c;
                $p = 'e';
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
                $part = "";
            }

            $part .= $c;
            $s = 'y';
            $p = ' ';
            continue;
        }

        if( strpos( "=<>!+-*/%", $c ) !== false )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $s === 'o' )
            {
                $part .= $c;
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
                $part = "";
            }

            $part .= $c;
            $s = 'o';
            $p = ' ';
            continue;
        }

        if( $c === ':' )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $s === 'y' && $i === ( $n - 1 ) )
            {
                $part .= $c;
                continue;
            }

            $error['msg'] = "unexpected character `$c`: ";
            return $parts;
        }

        if( strpos( '_$abcdefghijkilmnopqrstuvwxyzABCDEFGHIJKILMNOPQRSTUVXYZ', $c ) !== false )
        {
            if( $s === 's' )
            {
                $part .= $c;
                continue;
            }

            if( $s === 'y' )
            {
                $part .= $c;
                continue;
            }

            if( $part !== '' )
            {
                $parts[] = $part;
                $part = "";
            }

            $part .= $c;
            $s = 'y';
            $p = ' ';
            continue;
        }

        if( $s === 's' )
        {
            $part .= $c;
            continue;
        }

        $error['msg'] = "unexpected character `$c`: ";
        return $parts;
    }

    if( $part !== '' )
    {
        $parts[] = $part;
    }

    return $parts;
}



