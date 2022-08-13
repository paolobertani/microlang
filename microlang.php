<?php

//
// FreeBSD 2-clause license
//
// microlang.php
//
// A php microlang interpreter
//
// microlang version 1.3
// php interpreter version 1.3.2
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
// OR CONSEQUENTIAL DAMAGES ( INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
// OR BUSINESS INTERRUPTION ) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// ( INCLUDING NEGLIGENCE OR OTHERWISE ) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
//

function microlang( $code, &$vars, $options = null )
{



    // Begin



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



    if( ! isset( $options ) )
    {
        $options = [];
    }

    $max_iterations = isset( $options[ 'max_iterations' ] ) ? $options[ 'max_iterations' ] : 1000;
    if( ! is_int( $max_iterations ) ) return "`max_iterations` options must be int";

    $max_str_len = isset( $options[ 'max_str_len' ] ) ? $options[ 'max_str_len' ] : 1048576;
    if( ! is_int( $max_str_len ) ) return "`max_strlen` options must be int";

    if( isset( $options[ 'action' ] ) )
    {
        if( $options[ 'action' ] === 'execute' ) $execute = true;
        else if( $options[ 'action' ] === 'analyze' ) $execute = false;
        else return "`action` option must be either `execute` or `analyze`";
    }
    else
    {
        $execute = true;
    }


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
        if( is_int( $value ) ) $typs[ $key ] = 'int';
        elseif( is_string( $value ) ) $typs[ $key ] = 'string';
        elseif( is_float( $value ) ) $typs[ $key ] = 'float';
        else return "unsupported variable type: $key";
    }


    // convert windows linefeeds to unix linefeeds if any

    $code = str_replace( "\r\n", "\n", $code );


    // explode code

    $lines = explode( "\n", $code );



    // remove comments

    $n = count( $lines );
    for( $i = 0; $i < $n; $i++ )
    {
        $l = $lines[ $i ];
        $idx = mb_strpos( $l, '//' );
        if( $idx !== false )
        {
            $l = mb_substr8( $l, 0, $idx );
        }
        $l = trim( $l );
        $lines[ $i ] = $l;
    }


    // tokenize

    $newlines = $lines;
    $lines = [];
    $y = -1;
    $y1b = $y + 1;
    $m = count( $newlines );
    for( $k = 0; $k < $m; $k++ )
    {
        $l = $newlines[ $k ];

        $y++;
        $y1b = $y + 1;

        $parts = microlang_splitline( $l, $error );
        if( $error !== '' ) return "$error$y1b";

        $tokens = [];
        $n = count( $parts );
        for( $i = 0; $i < $n; $i++ )
        {
            $p = $parts[ $i ];


            // Keywords

            if( in_array( $p, $keywords ) )
            {
                $tokens[] = [ 'type' => 'keyword', 'symbol' => $p, 'value' => null, 'vtype' => null ];

                if( $i < $n - 1 && $parts[ $i + 1 ] === '=' ) return "keywords cannot be used for variable names ( $p ): $y1b";

                continue;
            }


            // Labels

            if( substr8( $p, -1, 1 ) === ":" && $i === 0 )
            {
                $p = mb_substr8( $p, 0, -1 );
                if( in_array( $p, $keywords ) ) return "keywords cannot be used for label names ( $p ): $y1b";
                $tokens[] = [ 'type' => 'label', 'symbol' => $p, 'value' => $y, 'vtype' => null ];
                if( isset( $labels[ $p ] ) ) return "Label $p duplicate: $y1b";
                $labels[ $p ] = $y;
                continue;
            }

            if( substr8( $p, -1, 1 ) === ":" && $i > 0 )
            {
                return "unexpected label: $y1b";
            }


            // Strings

            if( $p === '""' )
            {
                $tokens[] = [ 'type' => 'value', 'symbol' => null, 'value' => "", 'vtype' => 'string' ];
                continue;
            }

            if( substr8( $p, 0, 1 ) === '"' )
            {
                if( substr8( $p, -1, 1 ) === '"' && mb_strlen( $p ) > 1 )
                {
                    $tokens[] = [ 'type' => 'value', 'symbol' => null, 'value' => substr8( $p, 1, -1 ), 'vtype' => 'string' ];
                    continue;
                }

                $value = substr8( $p, 1, -1 );

                if( substr8( $p, -1, 1 ) !== '"' ) return "string not closed: $y1b";

                $tokens[] = [ 'type' => 'value', 'symbol' => null, 'value' => $value, 'vtype' => 'string' ];
                continue;
            }


            // Integers

            if( preg_match( '/^-?\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/', $p ) === 1 )
            {
                if( floatval( $p ) > PHP_INT_MAX || floatval( $p ) < PHP_INT_MIN ) return "overflow: $y1b";

                $tokens[] = [ 'type' => 'value', 'symbol' => null, 'value' => intval( $p ), 'vtype' => 'int' ];
                continue;
            }


            // Floats

            if( preg_match( '/^-?\d*\.\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/', $p ) === 1 )
            {
                $tokens[] = [ 'type' => 'value', 'symbol' => null, 'value' => floatval( $p ), 'vtype' => 'float' ];
                continue;
            }


            // Variable names

            $tokens[] = [ 'type' => 'variable', 'symbol' => $p, 'value' => null, 'vtype' => null ];

        }
        $lines[] = $tokens;
    }



    // execute

    $y = 0;
    $lines_count = count( $lines );
    $iter = 0;
    $vars[ 'cast_failed' ] = 0;

    while( true )
    {
        $done = false;

        $y1b = $y + 1;

        if( $iter > $max_iterations && $max_iterations !== 0 ) return "max iterations exceeded: $y1b";

        $tok = $lines[ $y ];
        $tn = count( $tok );

        if( $tn === 0 ) $done = true;

        if( $tn === 1 && $tok[ 0 ][ 'type' ] === 'label' ) $done = true;

        $tokens = [];

        for( $i = 0; $i < $tn; $i++ )
        {
            $t = $tok[ $i ];

            if( $t[ 'type' ] === 'label' || $t[ 'type' ] === 'value' || $t[ 'type' ] === 'keyword' )
            {
                $tokens[] = $t;
            }
            elseif( $t[ 'type' ] === 'variable' )
            {
                if( isset( $typs[ $t[ 'symbol' ] ] ) )
                {
                    $t[ 'value' ] = $vars[ $t[ 'symbol' ] ];
                    $t[ 'vtype' ] = $typs[ $t[ 'symbol' ] ];
                    $tokens[] = $t;
                }
                elseif( isset( $labels[ $t[ 'symbol' ] ] ) )
                {
                    $t[ 'type' ] = 'label';
                    $t[ 'value' ] = $labels[ $t[ 'symbol' ] ];
                    $tokens[] = $t;
                }
                else
                {
                    $t[ 'value' ] = null;
                    $t[ 'vtype' ] = null;
                    $tokens[] = $t;
                }
            }
        }


        $t0s = $done ? '' : $tokens[ 0 ][ 'symbol' ];
        $tcn = $done ?  0 : count( $tokens );


        // Goto

        if( ! $done && microlang_parse( $tokens, [ 'goto', ':' ] ) )
        {
            $done = true;

            $label = $tokens[ 1 ][ 'symbol' ];

            if( isset( $labels[ $label ] ) )
            {
                $iter++;
                if( $execute )
                {
                    $y = $labels[ $label ];
                    continue;
                }
            }
            else return "unknown label `$label`: $y1b";
        }


        // Gosub

        if( ! $done && microlang_parse( $tokens, [ 'gosub', ':' ] ) )
        {
            $done = true;

            $label = $tokens[ 1 ][ 'symbol' ];

            if( isset( $labels[ $label ] ) )
            {
                $iter++;
                if( $execute )
                {
                    $stack[] = $y;
                    $y = $labels[ $label ];
                    continue;
                }
            }
            else return "unknown label `$label`: $y1b";
        }


        // Return

        if( ! $done && microlang_parse( $tokens, [ 'return' ] ) )
        {
            $done = true;

            if( $execute )
            {
                if( count( $stack ) === 0 ) return "return without gosub: $y1b";
                $y = array_pop( $stack );
                continue;
            }
        }


        // Exit

        if( ! $done && microlang_parse( $tokens, [ 'exit' ] ) )
        {
            $done = true;

            if( $execute )
            {
                break;
            }
        }


        // Exit with error message

        if( ! $done && microlang_parse( $tokens, [ 'exit', '#' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "*" ); if( $err !== '' ) return $err . $y1b;

            return $tokens[ 1 ][ 'value' ] . '';
        }


        // Int, Float, String declaration with first assignment

        if( ! $done && ( microlang_parse( $tokens, [ 'int', '@', '=', '#' ] ) || microlang_parse( $tokens, [ 'float', '@', '=', '#' ] ) || microlang_parse( $tokens, [ 'string', '@', '=', '#' ] ) ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "U" . strtoupper( $tokens[0]['symbol'][0] ) ); if( $err !== '' ) return $err . $y1b;

            $typs[ $tokens[ 1 ][ 'symbol' ] ] = $tokens[ 0 ][ 'symbol' ];
            $vars[ $tokens[ 1 ][ 'symbol' ] ] = $tokens[ 3 ][ 'value' ];

            $done = true;
        }


        // Int, Float, String declaration

        if( ! $done && ( $t0s === 'int' || $t0s === 'float' || $t0s === 'string' ) && $tcn >= 2 )
        {
            $j = 1;

            while( true )
            {
                if( $tokens[ $j ][ 'type' ] === 'variable' )
                {
                    if( isset( $typs[ $tokens[ $j ][ 'symbol' ] ] ) )
                    {
                        return "variable `{$tokens[ $j ][ 'symbol' ]}` already defined: $y1b";
                    }

                    $typs[ $tokens[ $j ][ 'symbol' ] ] = $t0s;

                    $vars[ $tokens[ $j ][ 'symbol' ] ] = null;
                }
                else
                {
                    return "variable expected: $y1b";
                }

                if( $j >= $tcn - 1 ) break;

                if( $j < $tcn - 2 )
                {
                    $j++;
                    if( $tokens[ $j ][ 'symbol' ] !== ',' ) return "`,` expected: $y1b";
                    $j++;
                }
            }

            $done = true;
        }


        // = Assignment

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#' ] ) )
        {
            if( $t0s === 'cast_failed' ) return "`cast_failed` is a reserved variable name: $y1b";

            $err = microlang_typecheck( $execute, $tokens, "+1" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = $tokens[ 2 ][ 'value' ];
            }

            $done = true;
        }

        if( ! $done && microlang_parse( $tokens, [ 'K', '=', '#' ] ) )
        {
            return "keywords cannot be used for variable names ( {$tokens[ 0 ][ 'symbol' ]} ): $y1b";
        }

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'K' ] ) )
        {
            return "keyword unexpected at right side of assignment ( {$tokens[ 2 ][ 'symbol' ]} ): $y1b";
        }


        // Substring

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'substring', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "sSII" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = mb_substr8( $tokens[ 4 ][ 'value' ], $tokens[ 6 ][ 'value' ], $tokens[ 8 ][ 'value' ] );
            }

            $done = true;
        }


        // Position

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'position', '(', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "iSS" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 4 ][ 'value' ] === '' || $tokens[ 6 ][ 'value' ] === '' )
                {
                    $vars[ $t0s ] = -1;
                }
                else
                {
                    $vars[ $t0s ] = mb_strpos( $tokens[ 4 ][ 'value' ], $tokens[ 6 ][ 'value' ] );

                    if( $vars[ $t0s ] === false ) $vars[ $t0s ] = -1;
                }
            }

            $done = true;
        }


        // Replace

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'replace', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "sSII" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = str_replace( $tokens[ 6 ][ 'value' ], $tokens[ 8 ][ 'value' ], $tokens[ 4 ][ 'value' ] );
                if( mb_strlen( $vars[ $t0s ] ) > $max_str_len ) return "string too long: $y1b";
            }

            $done = true;
        }


        // Between

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'between', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "sSSS" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = microlang_between( $tokens[ 4 ][ 'value' ], $tokens[ 6 ][ 'value' ], $tokens[ 8 ][ 'value' ] );
                if( $vars[ $t0s ] === false ) $vars[ $t0s ] = "";
            }

            $done = true;
        }

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'between', '(', '#', ',', '#', ',', '#', ',', '@', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "sSSSi" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = microlang_between( $tokens[ 4 ][ 'value' ], $tokens[ 6 ][ 'value' ], $tokens[ 8 ][ 'value' ] );
                if( $vars[ $t0s ] === false )
                {
                    $vars[ $t0s ] = "";
                    $vars[ $tokens[ 10 ][ 'symbol' ] ] = 0;
                }
                else
                {
                    $vars[ $tokens[ 10 ][ 'symbol' ] ] = 1;
                }
            }

            $done = true;
        }


        // Trim

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'trim', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "sS" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = trim( $tokens[ 4 ][ 'value' ], " \n\r\t" );
            }

            $done = true;
        }



        // Len

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'len', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "iS" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = mb_strlen( $tokens[ 4 ][ 'value' ] );
            }

            $done = true;
        }


        // Typeof

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'typeof', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "s+" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = $tokens[ 4 ][ 'vtype' ];
            }

            $done = true;
        }


        // Int

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'int', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "i*" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 4 ][ 'vtype' ] === 'string' && preg_match( '/^-?\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/', $tokens[ 4 ][ 'value' ] ) === 0 && preg_match( '/^-?\d*\.\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/', $tokens[ 4 ][ 'value' ] ) === 0 )
                {
                    $vars[ $t0s ] = ( int )0;
                    $vars[ 'cast_failed' ] = 1;
                }
                else
                {
                    if( floatval( $tokens[ 4 ][ 'value' ] ) > PHP_INT_MAX || floatval( $tokens[ 4 ][ 'value' ] ) < PHP_INT_MIN )
                    {
                        $vars[ $t0s ] = ( int )0;
                        $vars[ 'cast_failed' ] = 1;
                    }
                    else
                    {
                        $vars[ $t0s ] = intval( $tokens[ 4 ][ 'value' ] );
                        $vars[ 'cast_failed' ] = 0;
                    }
                }
            }

            $done = true;
        }


        // Float

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'float', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "n*" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 4 ][ 'vtype' ] === 'string' && preg_match( '/^-?\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/', $tokens[ 4 ][ 'value' ] ) === 0 && preg_match( '/^-?\d*\.\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/', $tokens[ 4 ][ 'value' ] ) === 0 )
                {
                    $vars[ $t0s ] = ( float )0;
                    $vars[ 'cast_failed' ] = 1;
                    $typs[ $t0s ] = 'float';
                }
                else
                {
                    $vars[ $t0s ] = floatval( $tokens[ 4 ][ 'value' ] );
                    $vars[ 'cast_failed' ] = 0;
                    $typs[ $t0s ] = 'float';
                }
            }

            $done = true;
        }


        // String

        if( ! $done && microlang_parse( $tokens, [ '@', '=', 'string', '(', '#', ')' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "s*" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                $vars[ $t0s ] = '' . $tokens[ 4 ][ 'value' ];
            }

            $done = true;
        }


        // + Sum

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '+', '#' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "+12" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 2 ][ 'vtype' ] === 'string' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] . $tokens[ 4 ][ 'value' ];

                    if( mb_strlen( $vars[ $t0s ] ) > $max_str_len ) return "string too long: $y1b";
                }
                elseif( $tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] + $tokens[ 4 ][ 'value' ];

                    if( $vars[ $t0s ] > PHP_INT_MAX || $vars[ $t0s ] < PHP_INT_MIN ) return "overflow: $y1b";
                }
                elseif( $tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] + $tokens[ 4 ][ 'value' ];
                }
            }

            $done = true;
        }


        // - Diff

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '-', '#' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "n12" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] - $tokens[ 4 ][ 'value' ];

                    if( $vars[ $t0s ] > PHP_INT_MAX || $vars[ $t0s ] < PHP_INT_MIN ) return "overflow: $y1b";
                }
                elseif( $tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] - $tokens[ 4 ][ 'value' ];
                }
            }

            $done = true;
        }


        // * Mult

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '*', '#' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "n12" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    $rt = 'int';
                    if( isset( $typs[ $t0s ] ) && $typs[ $t0s ] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                    $typs[ $t0s ] = $rt;

                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] * $tokens[ 4 ][ 'value' ];

                    if( $vars[ $t0s ] > PHP_INT_MAX || $vars[ $t0s ] < PHP_INT_MIN ) return "overflow: $y1b";
                }
                elseif( $tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    $rt = 'float';
                    if( isset( $typs[ $t0s ] ) && $typs[ $t0s ] !== $rt ) return "variable `$t0s` must be $rt: $y1b";
                    $typs[ $t0s ] = $rt;

                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] * $tokens[ 4 ][ 'value' ];
                }
            }

            $done = true;
        }


        // / Div

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '/', '#' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "n12" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 4 ][ 'value' ] === 0 ) return "division by zero: $y1b";
                if( $tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] / $tokens[ 4 ][ 'value' ];

                    if( $vars[ $t0s ] > PHP_INT_MAX || $vars[ $t0s ] < PHP_INT_MIN ) return "overflow: $y1b";

                    $vars[ $t0s ] = intdiv( $tokens[ 2 ][ 'value' ], $tokens[ 4 ][ 'value' ] );
                }
                elseif( $tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] / $tokens[ 4 ][ 'value' ];
                }
            }

            $done = true;
        }


        // % Modulo

        if( ! $done && microlang_parse( $tokens, [ '@', '=', '#', '%', '#' ] ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "iII" ); if( $err !== '' ) return $err . $y1b;

            if( $execute )
            {
                if( $tokens[ 4 ][ 'value' ] === 0 ) return "division by zero: $y1b";
                $vars[ $t0s ] = $tokens[ 2 ][ 'value' ] % $tokens[ 4 ][ 'value' ];
            }

            $done = true;
        }


        // If Then [ Else ]

        if( ! $done && ( microlang_parse( $tokens, [ 'if', '#', '~', '#', 'then', ':' ] ) || microlang_parse( $tokens, [ 'if', '#', '~', '#', 'then', ':', 'else', ':' ] ) ) )
        {
            $err = microlang_typecheck( $execute, $tokens, "*1" ); if( $err !== '' ) return $err . $y1b;

            if( $tokens[ 5 ][ 'value' ] === null ) return "undefined label `" . $tokens[ 5 ][ 'symbol' ] ."`: $y5b";
            if( $tn === 8 && $tokens[ 7 ][ 'value' ] === null ) return "undefined label `" . $tokens[ 7 ][ 'symbol' ] . "`: $y5b";

            if( $execute )
            {
                if( $tokens[ 2 ][ 'symbol' ] === '==' )
                {
                    if( $tokens[ 1 ][ 'value' ] == $tokens[ 3 ][ 'value' ] )
                    {
                        $y = $tokens[ 5 ][ 'value' ];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        if( $tn === 8 && $tokens[ 7 ][ 'value' ] !== null )
                        {
                            $y = $tokens[ 7 ][ 'value' ];
                            $iter++;
                            continue;
                        }
                        else
                        {
                            $done = true;
                        }
                    }
                }

                if( $tokens[ 2 ][ 'symbol' ] === '!=' )
                {
                    if( $tokens[ 1 ][ 'value' ] != $tokens[ 3 ][ 'value' ] )
                    {
                        $y = $tokens[ 5 ][ 'value' ];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        if( $tn === 8 && $tokens[ 7 ][ 'value' ] !== null )
                        {
                            $y = $tokens[ 7 ][ 'value' ];
                            $iter++;
                            continue;
                        }
                        else
                        {
                            $done = true;
                        }
                    }
                }

                if( $tokens[ 2 ][ 'symbol' ] === '>' )
                {
                    if( $tokens[ 1 ][ 'value' ] > $tokens[ 3 ][ 'value' ] )
                    {
                        $y = $tokens[ 5 ][ 'value' ];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        if( $tn === 8 && $tokens[ 7 ][ 'value' ] !== null )
                        {
                            $y = $tokens[ 7 ][ 'value' ];
                            $iter++;
                            continue;
                        }
                        else
                        {
                            $done = true;
                        }
                    }
                }

                if( $tokens[ 2 ][ 'symbol' ] === '<' )
                {
                    if( $tokens[ 1 ][ 'value' ] < $tokens[ 3 ][ 'value' ] )
                    {
                        $y = $tokens[ 5 ][ 'value' ];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        if( $tn === 8 && $tokens[ 7 ][ 'value' ] !== null )
                        {
                            $y = $tokens[ 7 ][ 'value' ];
                            $iter++;
                            continue;
                        }
                        else
                        {
                            $done = true;
                        }
                    }
                }

                if( $tokens[ 2 ][ 'symbol' ] === '<=' )
                {
                    if( $tokens[ 1 ][ 'value' ] <= $tokens[ 3 ][ 'value' ] )
                    {
                        $y = $tokens[ 5 ][ 'value' ];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        if( $tn === 8 && $tokens[ 7 ][ 'value' ] !== null )
                        {
                            $y = $tokens[ 7 ][ 'value' ];
                            $iter++;
                            continue;
                        }
                        else
                        {
                            $done = true;
                        }
                    }
                }

                if( $tokens[ 2 ][ 'symbol' ] === '>=' )
                {
                    if( $tokens[ 1 ][ 'value' ] >= $tokens[ 3 ][ 'value' ] )
                    {
                        $y = $tokens[ 5 ][ 'value' ];
                        $iter++;
                        continue;
                    }
                    else
                    {
                        if( $tn === 8 && $tokens[ 7 ][ 'value' ] !== null )
                        {
                            $y = $tokens[ 7 ][ 'value' ];
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
            else
            {
                $done = true;
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

    unset( $vars[ 'cast_failed' ] );

    return "";
}



function microlang_totext( $t )
{
    if( $t[ 'symbol' ] !== null )
    {
        return "`" . $t[ 'symbol' ] + "`";
    }

    if( $t[ 'vtype' ] === 'string' )
    {
        return "`\"" . $t[ 'value' ] + "\"`";
    }
    else if( $t[ 'vtype' ] === 'int' )
    {
        return "`\"" . $t[ 'value' ] + "\"`";
    }
    else
    {
        $val = $t[ 'value' ];
        $val = strtolower( $val );
        $val = explode( "e", $val );
        if( strpos( $val[ 0 ], "." ) === false )
        {
            $val[ 0 ] .= ".0";
        }
        $val = implode( "e", $val );
        $val = "`" . $val . "`";
        return $val;
    }
}



function microlang_parse( $tokens, $expected )
{
    $n = count( $tokens );

    if( $n !== count( $expected ) ) return false;

    for( $i = 0; $i < $n; $i++ )
    {
        $t = $tokens[ $i ][ 'type' ];
        $s = $tokens[ $i ][ 'symbol' ];
        $e = $expected[ $i ];

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



function microlang_typecheck( $exe, $tok, $types )
{
    $tokens = [];

    $n = count( $tok );

    for( $i = 0; $i < $n; $i++ )
    {
        if( $tok[ $i ][ 'type' ] === 'variable' || $tok[ $i ][ 'type' ] === 'value' )
        {
            $tokens[] = $tok[ $i ];
        }
    }

    $n = strlen( $types );
    $t0 = $tokens[ 0 ][ 'vtype' ];
    $t1 = $tokens[ 1 ][ 'vtype' ];

    for( $i = 0; $i < $n; $i++ )
    {
        $c = $types[ $i ];
        $t = $tokens[ $i ][ 'vtype' ];
        $v = $tokens[ $i ][ 'value' ];
        $y = $tokens[ $i ][ 'symbol' ];

        $isvar = ( $tokens[ $i ][ 'type' ] === 'variable' );

        if( $c === 'U' ) // a variable not yet defined
        {
            if( ! $isvar ) return "variable expected: ";
            if( $t !== null ) return "variable `$y` is already defined: ";
            continue;
        }

        if( $isvar && $t === null ) return "variable `$y` is not defined: ";

        if( $c === '*' ) // a value of any type or a variable of any type with a value set
        {
            if( $isvar && $t === null ) return "variable `$y` is not defined: ";
            continue;
        }

        if( $c === 's' || $c === 'i' || $c === 'f' || $c === 'n' || $c === '+' ) // a variable
        {
            if( ! $isvar ) return "variable expected: ";

            if( $c === 's' && $t !== 'string' ) return "variable `$y` must be string: ";
            if( $c === 'i' && $t !== 'int'    ) return "variable `$y` must be int: ";
            if( $c === 'f' && $t !== 'float'  ) return "variable `$y` must be float: ";
            if( $c === 'n' && $t === 'string' ) return "variable `$y` must be int or float: ";

            continue;
        }

        if( $isvar && $v === null && $exe ) return "variable `$y` has undefined value: ";

        // a value or a variable with defined value

        if( $c === 'S' && $t !== 'string' ) return "parameter " . ( $i + 1 ) . " must be string: ";
        if( $c === 'I' && $t !== 'int'    ) return "parameter " . ( $i + 1 ) . " must be int: ";
        if( $c === 'F' && $t !== 'float'  ) return "parameter " . ( $i + 1 ) . " must be float: ";
        if( $c === 'N' && $t === 'string' ) return "parameter " . ( $i + 1 ) . " must be int or float: ";
        if( $c === '1' && $t !== $t0      ) return $i === 1 ? "variable `{$tokens[ 0 ][ 'symbol' ]}` must be `$t`: " : "operands must be of the same type: ";
        if( $c === '2' && $t !== $t1      ) return "operands must be of the same type: ";
    }

    return "";
}



function microlang_varcheck( $t )
{
    if( $t[ 'type' ] === 'variable' )
    {
        if( $t[ 'vtype' ] === null ) return "variable `{$t[ 'symbol' ]}` is not defined: ";
        if( $t[ 'value' ] === null ) return "variable `{$t[ 'symbol' ]}` has undefined value: ";
    }
    else
    {
        return "expected variable: ";
    }
}



function microlang_splitline( $line, &$error )
{
    $error = "";

    $parts = [];

    $n = mb_strlen( $line );
    $part = "";
    $s = ' '; // currently parsing: ' ' nothing, 'o' operator, 's' string, 'n' number, 'y' symbol ( keyword, variable, label )
    $p = ' '; // number; currently parsing: ' ' not a number, 'i' integer part, 'd' decimal part, 'e' exponent

    for( $i = 0; $i < $n; $i++ )
    {
        $c  = mb_substr8( $line, $i, 1 );
        $c2 = mb_substr8( $line, $i, 2 );
        $cn = mb_substr8( $line, $i + 1, 1 );
        $cp = $i > 0 ? mb_substr8( $line, $i - 1, 1 ) : '';

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

        if( strpos( "( , )", $c ) !== false )
        {
            if( $s === 's' )
            {
                $part .= $c;
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

            if( $c2 === "\\\\" )
            {
                $part .= "\\";
                $i++;
                continue;
            }
            else if( $c2 === "\\n" )
            {
                $part .= "\n";
                $i++;
                continue;
            }
            else if( $c2 === "\\r" )
            {
                $part .= "\r";
                $i++;
                continue;
            }
            else if( $c2 === "\\t" )
            {
                $part .= "\t";
                $i++;
                continue;
            }
            else if( $c2 === "\\\"" )
            {
                $part .= "\"";
                $i++;
                continue;
            }
            else if( $c2 === "\\'" )
            {
                $part .= "'";
                $i++;
                continue;
            }
            else
            {
                $error = "unrecognized escape sequence `$c2`: ";
                return $parts;
            }
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
                    $part .= $c;
                    $p = 'd';
                    continue;
                }
                else
                {
                    $error[ 'msg' ] = "unexpected character `$c`: ";
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
                $part = "0.";
                $s = 'n';
                $p = 'd';
                continue;
            }

            $error[ 'msg' ] = "unexpected character `$c`: ";
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

            $error[ 'msg' ] = "unexpected character `$c`: ";
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

        $error[ 'msg' ] = "unexpected character `$c`: ";
        return $parts;
    }

    if( $part !== '' )
    {
        $parts[] = $part;
    }

    return $parts;
}



// between implementation

function microlang_between( $str, $sm, $em )
{
    $i1 = 0;
    $i2 = 0;
    $sml = mb_strlen( $sm );
    $eml = mb_strlen( $em );

    if( $sml === 0 && $eml === 0 )
    {
        return $str;
    }

    if( $sml === 0 )
    {
        $i2 = mb_strpos( $str, $em );
        if( $i2 === false )
        {
            return false;
        }

        return mb_substr8( $str, 0, $i2 );
    }

    if( $eml === 0 )
    {
        $i1 = mb_strpos( $str, $sm );

        if( $i1 === false ) return false;

        $str = mb_substr8( $str, $i1 + $sml );

        while( true )
        {
            $i1 = mb_strpos( $str, $sm );
            if( $i1 === false )
            {
                break;
            }
            else
            {
                $str = mb_substr8( $str, $i1 + $sml );
            }
        }

        return $str;
    }

    $i1 = mb_strpos( $str, $sm );

    if( $i1 === false ) return false;

    $i2 = mb_strpos( $str, $em, $i1 + $sml );

    if( $i2 === false ) return false;

    $str = mb_substr8( $str, $i1 + $sml, $i2 - $i1 - $sml );

    while( true )
    {
        $i1 = mb_strpos( $str, $sm );
        if( $i1 === false ) break;
        $str = mb_substr8( $str, $i1 + $sml );
    }

    return $str;
}


// php8 version of substr

function substr8( $txt, $s, $l = null )
{
    $txt = "$txt";
    $txt = $l === null ? substr( $txt, $s ) : substr( $txt, $s, $l );
    return $txt === false ? "" : $txt;
}



// php8 version of mb_substr

function mb_substr8( $txt, $s, $l = null )
{
    $txt = "$txt";
    $txt = $l === null ? mb_substr( $txt, $s ) : mb_substr( $txt, $s, $l );
    return $txt === false ? "" : $txt;
}



