//
// FreeBSD 2-clause license
//
// microlang.js
//
// A javascript microlang interpreter
//
// microlang version 1.2
// js intepreter version 1.2.0
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

function microlang( code, vars, max_iterations )
{
    var keywords,
        max_str_len,
        labels,
        label,
        stack,
        lines,
        typs,
        key,
        value,
        i,
        n,
        idx,
        newlines,
        t,
        y,
        y1b,
        m,
        k,
        parts,
        tokens,
        p,
        lines_count,
        iter,
        done,
        tok,
        tn,
        t0s,
        err,
        error,
        l,
        i1,
        i2,
        rt;



    var is_string = function( x )
    {
        return ( typeof( x ) === 'string' );
    };



    var is_int = function( x )
    {
        return ( typeof( x ) === 'number' && Number.isInteger( x ) === true );
    };



    var is_float = function( x )
    {
        return ( typeof( x ) === 'number' && Number.isInteger( x ) === false );
    };



    var isset = function( x )
    {
        return ( typeof( x ) !== 'undefined' );
    };



    var microlang_parse = function( tokens, expected )
    {
        var i,n,t,s,e;

        n = tokens.length;

        if( n !== expected.length ) return false;

        for( i = 0; i < n; i++ )
        {
            t = tokens[i]['type'];
            s = tokens[i]['symbol'];
            e = expected[i];

            if( e === '@'  && t === 'variable' ) continue;
            if( e === ':'  && t === 'label'    ) continue;
            if( e === '$'  && t === 'value'    ) continue;
            if( e === 'K'  && t === 'keyword'  ) continue;
            if( e === '#' && ( t === 'value' || t === 'variable' ) ) continue;
            if( e === '~'  && ( s === '>' || s === '>' || s === '<' || s === '>=' || s === '<=' || s === '==' || s === '!=' ) ) continue;
            if( e === s  && t === 'keyword'  ) continue;

            return false;
        }

        return true;
    };



    var microlang_typecheck = function( tok, types )
    {
        var i,n,c,t,t0,t1,tokens;

        tokens = [];

        n = tok.length;

        for( i = 0; i < n; i++ )
        {
            if( tok[i]['type'] === 'variable' || tok[i]['type'] === 'value' )
            {
                tokens.push( tok[i] );
            }
        }

        n = types.length;
        t0 = tokens[0]['vtype'];
        t1 = tokens[1]['vtype'];

        for( i = 0; i < n; i++ )
        {
            c = types.charAt( i );
            t = tokens[i]['vtype'];

            if( t === null && c !== '?' ) return "undefined variable " + tokens[i]['symbol'] + ": ";

            if( c === '*' ) continue;

            if( c === 'S' && t !== 'string' ) return "parameter " + ( i + 1 ) + " must be string: ";
            if( c === 'I' && t !== 'int'    ) return "parameter " + ( i + 1 ) + " must be int: ";
            if( c === 'F' && t !== 'float'  ) return "parameter " + ( i + 1 ) + " must be float: ";
            if( c === 'N' && t === 'string' ) return "parameter " + ( i + 1 ) + " must be int or float: ";
            if( c === '1' && t !== t0       ) return "operands must be of the same type: ";
            if( c === '2' && t !== t1       ) return "operands must be of the same type: ";
        }

        return "";
    };



    var microlang_splitline = function( line, error )
    {
        var parts,
            i,
            n,
            s,
            part,
            p,
            c,
            c2,
            cn,
            cp;

        error['msg'] = '';

        parts = [];

        n = line.length;
        part = "";
        s = ' '; // currently parsing: ' ' nothing, 'o' operator, 's' string, 'n' number, 'y' symbol (keyword, variable, label)
        p = ' '; // number; currently parsing: ' ' not a number, 'i' integer part, 'd' decimal part, 'e' exponent

        for( i = 0; i < n; i++ )
        {
            c  = line.substring( i, i + 1 );
            c2 = line.substring( i, i + 2 );
            cn = line.substring( i + 1, i + 2 );
            cp = line.substring( i - 1, i );

            if( c === ' ' )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                    part = '';
                }

                p = ' ';
                s = ' ';
                continue;
            }

            if( "(,)".indexOf( c ) !== -1 )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                }

                part = c;
                parts.push( part );

                part = "";

                p = ' ';
                s = ' ';
                continue;
            }

            if( c === "\\" )
            {
                if( s !== 's' )
                {
                    error['msg'] = "unexpected escape character `" + c + "`: ";
                    return parts;
                }

                if( c2 === "\\\\" || c2 === "\\n" || c2 === "\\r" || c2 === "\\t" || c2 === "\\\"" )
                {
                    part += c2;
                    i++;
                    continue;
                }
                else
                {
                    error['msg'] = "unrecognized escape sequence `" + c2 + "`: ";
                }
                return parts;
            }

            if( c === '"' )
            {
                if( s === 's' )
                {
                    part += c;
                    parts.push( part );
                    part = '';
                    s = ' ';
                    p = ' ';
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                    part = "";
                }

                part += c;
                s = 's';
                p = ' ';
                continue;
            }

            if( "0123456789".indexOf( c ) !== -1 )
            {
                if( s === 's' || s === 'y' || s === 'n' )
                {
                    part += c;
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                    part = "";
                }

                part += c;
                s = 'n';
                p = 'i';
                continue;
            }

            if( c === '.' )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( s === 'n' )
                {
                    if( p === 'i' )
                    {
                        part += c;
                        p = 'd';
                        continue;
                    }
                    else
                    {
                        error['msg'] = "unexpected character `" + c + "`: ";
                        return parts;
                    }
                }

                if( s === 'o' || s === 'y' )
                {
                    if( part !== '' )
                    {
                        parts.push( part );
                        part = "";
                    }
                }

                if( "0123456789".indexOf( cn ) !== -1 )
                {
                    part += "0" + c;
                    s = 'n';
                    p = 'd';
                    continue;
                }

                error['msg'] = "unexpected character `" + c + "`: ";
                return parts;
            }

            if( c === '-' )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( s === ' ' || s === 'y' || s === 'o' )
                {
                    if( part !== '' )
                    {
                        parts.push( part );
                        part = "";
                    }

                    part += c;

                    if( "0123456789".indexOf( cn ) !== -1 )
                    {
                        s = 'n';
                        p = 'i';
                        continue;
                    }

                    if( cn === '.' )
                    {
                        s = 'n';
                        p = 'i';
                        continue;
                    }

                    parts.push( part );
                    part = "";
                    s = ' ';
                    p = ' ';
                    continue;
                }

                if( s === 'n' && p === 'e' && ( cp === 'e' || cp === 'E' ) )
                {
                    part += c;
                    continue;
                }
            }

            if( c === 'e' || c === 'E' )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( s === 'n' && ( p === 'i' || p === 'd' ) )
                {
                    part += c;
                    p = 'e';
                    continue;
                }

                if( s === 'y' )
                {
                    part += c;
                    p = 'e';
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                    part = "";
                }

                part += c;
                s = 'y';
                p = ' ';
                continue;
            }

            if( "=<>!+-*/%".indexOf( c ) !== -1 )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( s === 'o' )
                {
                    part += c;
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                    part = "";
                }

                part += c;
                s = 'o';
                p = ' ';
                continue;
            }

            if( c === ':' )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( s === 'y' && i === ( n - 1 ) )
                {
                    part += c;
                    continue;
                }

                error['msg'] = "unexpected character `" + c + "`: ";
                return parts;
            }

            if( "_$abcdefghijkilmnopqrstuvwxyzABCDEFGHIJKILMNOPQRSTUVXYZ".indexOf( c ) !== -1 )
            {
                if( s === 's' )
                {
                    part += c;
                    continue;
                }

                if( s === 'y' )
                {
                    part += c;
                    continue;
                }

                if( part !== '' )
                {
                    parts.push( part );
                    part = "";
                }

                part += c;
                s = 'y';
                p = ' ';
                continue;
            }

            if( s === 's' )
            {
                part += c;
                continue;
            }

            error['msg'] = "unexpected character `" + c + "`: ";
            return parts;
        }

        if( part !== '' )
        {
            parts.push( part );
        }

        return parts;
    };


    if( typeof( max_iterations ) === 'undefined' )
    {
        max_iterations = 1000;
    }



    keywords = [
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



    error = { 'msg': '' };

    max_str_len = 1024*1024;

    labels = {};
    stack = [];

    if( typeof( vars ) === 'undefined' )
    {
        return "i/o vars object is undefined";
    }

    if( typeof( vars ) !== 'object' )
    {
        return "input data provided is not object";
    }


    // check and store input types

    typs = {};

    for( key in vars )
    {
        if( vars.hasOwnProperty( key ) )
        {
            value = vars[ key ];

            if( is_int( value ) ) typs[ key ] = 'int';
            else if( is_string( value ) ) typs[ key ] = 'string';
            else if( is_float( value ) ) typs[ key ] = 'float';
            else return "unsupported variable type: " + key;
        }
    }


    // convert windows linefeeds to unix linefeeds if any

    code = code.split( "\r\n" ).join( "\n" );


    // explode code

    lines = code.split( "\n" );


    // remove comments

    n = lines.length;
    for( i = 0; i < n; i++ )
    {
        l = lines[i];
        idx = l.indexOf( '//' );
        if( idx !== -1 )
        {
            l = l.substring( 0, idx );
        }
        l = l.trim();
        lines[i] = l;
    }


    // tokenize

    newlines = lines.slice();
    lines = [];
    y = -1;
    y1b = y + 1;
    m = newlines.length;
    for( k = 0; k < m; k++)
    {
        l = newlines[ k ];

        y++;
        y1b = y + 1;

        parts = microlang_splitline( l, error );
        if( error['msg'] !== '' ) return error['msg'] + y1b;

        tokens = [];
        n = parts.length;
        for( i = 0; i < n; i++ )
        {
            p = parts[i];


            // Keywords

            if( keywords.indexOf( p ) !== -1 )
            {
                tokens.push( { 'type': 'keyword', 'symbol': p, 'value': null, 'vtype': null } );

                if( i < n - 1 && parts[ i + 1 ] === '=' ) return "keywords cannot be used for variable names (" + p + "):" + y1b;

                continue;
            }


            // Labels

            if( p.slice( -1 ) === ":" && i === 0 )
            {
                p = p.substring( 0, p.length - 1 );
                if( keywords.indexOf( p ) !== -1 ) return "keywords cannot be used for label names (" + p + "):" + y1b;
                tokens.push( { 'type': 'label', 'symbol': p, 'value': y, 'vtype': null } );
                if( typeof( labels[p] ) !== 'undefined' ) return "Label " + p + " duplicate: " + y1b;
                labels[p] = y;
                continue;
            }

            if( p.slice( -1 ) === ":" && i > 0 )
            {
                return "unexpected label: " + y1b;
            }


            // Strings

            if( p === '""' )
            {
                tokens.push( { 'type': 'value', 'symbol': null, 'value': "", 'vtype': 'string' } );
                continue;
            }

            if( p.substring( 0, 1 ) === '"' )
            {
                if( p.slice( -1 ) === '"' && p.length > 1 )
                {
                    tokens.push( { 'type': 'value', 'symbol': null, 'value': p.substring( 1, p.length - 1 ), 'vtype': 'string' } );
                    continue;
                }

                value = p.substring( 1, p.length - 1 );

                if( p.substring( p.length - 1, p.length ) !== '"' ) return "string not closed: " + y1b;

                tokens.push( { 'type': 'value', 'symbol': null, 'value': value, 'vtype': 'string' } );
                continue;
            }


            // Integers

            if( /^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( p ) )
            {
                if( parseFloat( p ) > 9223372036854775807 || parseFloat( p ) < -9223372036854775808 ) return "overflow: " + y1b;

                tokens.push( { 'type': 'value', 'symbol': null, 'value': parseInt( parseFloat(p) ), 'vtype': 'int' } );
                continue;
            }


            // Floats

            if( /^-?\d*\.\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( p ) )
            {
                tokens.push( { 'type': 'value', 'symbol': null, 'value': parseFloat(p), 'vtype': 'float' } );
                continue;
            }


            // Variable names

            tokens.push( { 'type': 'variable', 'symbol': p, 'value': null, 'vtype': null } );

        }
        lines.push( tokens );
    }


    // execute

    y = 0;
    lines_count = lines.length;
    iter = 0;
    vars['cast_failed'] = 0;

    while( true )
    {
        done = false;

        y1b = y + 1;

        if( iter > max_iterations && max_iterations !== 0 ) return "Max iterations exceeded: " + y1b;

        tok = lines[ y ];
        tn = tok.length;

        if( tn === 0 ) done = true;

        if( tn === 1 && tok[0]['type'] === 'label' ) done = true;

        tokens = [];

        for( i = 0; i < tn; i++ )
        {
            t = tok[ i ];

            if( t['type'] === 'label' || t['type'] === 'value' || t['type'] === 'keyword' )
            {
                tokens.push( t );
            }
            else if( t['type'] === 'variable' )
            {
                if( typeof( vars[t['symbol'] ] ) !== 'undefined' )
                {
                    t['value'] = vars[t['symbol']];
                    t['vtype'] = typs[t['symbol']];
                    tokens.push( t );
                }
                else if( typeof( labels[t['symbol']]) !== 'undefined' )
                {
                    t['type'] = 'label';
                    t['value'] = labels[t['symbol']];
                    tokens.push( t );
                }
                else
                {
                    t['value'] = null;
                    tokens.push( t );
                }
            }
        }


        t0s = done ? '' : tokens[0]['symbol'];


        // Goto

        if( ! done && microlang_parse( tokens, [ 'goto', ':' ] ) )
        {
            done = true;

            label = tokens[1]['symbol'];

            if( isset( labels[label] ) )
            {
                iter++;
                y = labels[label];
                continue;
            }
            else return "unknown label " + label + ": " + y1b;
        }


        // Gosub

        if( ! done && microlang_parse( tokens, [ 'gosub', ':' ] ) )
        {
            done = true;

            label = tokens[1]['symbol'];

            if( isset( labels[label] ) )
            {
                stack.push( y );
                iter++;
                y = labels[label];
                continue;
            }
            else return "unknown label " + label + ": " + y1b;
        }


        // Return

        if( ! done && microlang_parse( tokens, [ 'return' ] ) )
        {
            done = true;

            if( stack.length === 0 ) return "return without gosub: " + y1b;

            y = stack.pop();
        }


        // Exit

        if( ! done && microlang_parse( tokens, [ 'exit' ] ) )
        {
            done = true;

            break;
        }


        // Exit with error message

        if( ! done && microlang_parse( tokens, [ 'exit', '#' ] ) )
        {
            return tokens[1]['value'] + '';
        }


        // = Assignment

        if( ! done && microlang_parse( tokens, [ '@', '=', '#' ] ) )
        {
            if( t0s === 'cast_failed' ) return "`cast_failed` is a reserved variable name: " + y1b;

            if( tokens[2]['value'] === null ) return "undefined variable `" + tokens[2]['symbol'] + "`: " + y1b;

            if( isset( vars[t0s] ) )
            {
                if( typs[t0s] === tokens[2]['vtype'] )
                {
                    vars[t0s] = tokens[2]['value'];
                }
                else if( typs[t0s] === 'float' && tokens[2]['vtype'] === 'int' )
                {
                    vars[t0s] = parseFloat( tokens[2]['value'] );
                }
                else
                {
                    return "variable cannot change type: " + y1b;
                }
            }
            else
            {
                vars[t0s] = tokens[2]['value'];
                typs[t0s] = tokens[2]['vtype'];
            }

            done = true;
        }

        if( ! done && microlang_parse( tokens, [ 'K', '=', '#' ] ) )
        {
            return "keywords cannot be used for variable names (" + tokens[0]['symbol'] + "): " + y1b;
        }

        if( ! done && microlang_parse( tokens, [ '@', '=', 'K' ] ) )
        {
            return "keyword unexpected at right side of assignment (" + tokens[2]['symbol'] + "): " + y1b;
        }


        // Substring

        if( ! done && microlang_parse( tokens, [ '@', '=', 'substring', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?SII" ); if( err !== '' ) return err + y1b;

            if( tokens[6]['value'] < 0 || tokens[8]['value'] < 0 ) return "substring accepts only positive index and length: " + y1b;

            rt = 'string';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['value'].substring( tokens[6]['value'], tokens[6]['value'] + tokens[8]['value'] );

            done = true;
        }


        // Position

        if( ! done && microlang_parse( tokens, [ '@', '=', 'position', '(', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?SS" ); if( err !== '' ) return err + y1b;

            rt = 'int';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['value'].indexOf( tokens[6]['value'] );

            done = true;
        }


        // Replace

        if( ! done && microlang_parse( tokens, [ '@', '=', 'replace', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?SSS" ); if( err !== '' ) return err + y1b;

            rt = 'string';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['value'].split( tokens[6]['value'] ).join( tokens[8]['value'] );

            if( vars[t0s].length > max_str_len ) return "string too long: " . y1b;

            done = true;
        }


        // Between

        if( ! done && microlang_parse( tokens, [ '@', '=', 'between', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?SSS" ); if( err !== '' ) return err + y1b;

            rt = 'string';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            if( tokens[6]['value'] === '' ) { i1 = 0; } else { i1 = tokens[4]['value'].indexOf( tokens[6]['value'] ); }

            if( tokens[8]['value'] === '' ) { i2 = tokens[4]['value'].length; } else { i2 = tokens[4]['value'].indexOf( tokens[8]['value'] ); }

            if( i1 === -1 || i2 === -1 || i2 < i1 )
            {
                vars[t0s] = "";
            }
            else
            {
                vars[t0s] = tokens[4]['value'].substring( i1 + tokens[6]['value'].length, i2 );
            }

            done = true;
        }


        // Trim

        if( ! done && microlang_parse( tokens, [ '@', '=', 'trim', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?S" ); if( err !== '' ) return err + y1b;

            rt = 'string';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['value'].trim();

            done = true;
        }


        // Len

        if( ! done && microlang_parse( tokens, [ '@', '=', 'len', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?S" ); if( err !== '' ) return err + y1b;

            rt = 'int';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['value'].length;

            done = true;
        }


        // Typeof

        if( ! done && microlang_parse( tokens, [ '@', '=', 'typeof', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( tokens, "?*" ); if( err !== '' ) return err + y1b;

            rt = 'string';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['vtype'];

            done = true;
        }


        // Int

        if( ! done && microlang_parse( tokens, [ '@', '=', 'int', '(', '#', ')' ] ) )
        {
            rt = 'int';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            if( tokens[4]['vtype'] === 'string' && /^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( tokens[4]['value'] ) === false && /^-?\d*\.\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( tokens[4]['value'] ) === false)
            {
                vars[t0s] = 0;
                vars['cast_failed'] = 1;
            }
            else
            {
                if( parseFloat( tokens[4]['value'] ) > 9223372036854775807 || parseFloat( tokens[4]['value'] ) < -9223372036854775808 )
                {
                    vars[t0s] = 0;
                    vars['cast_failed'] = 1;
                }
                else
                {
                    vars[t0s] = parseInt( tokens[4]['value'] );
                    vars['cast_failed'] = 0;
                }
            }

            done = true;
        }


        // Float

        if( ! done && microlang_parse( tokens, [ '@', '=', 'float', '(', '#', ')' ] ) )
        {
            if( isset( typs[t0s] ) && typs[t0s] !== 'int' && typs[t0s] !== 'float' ) return "variable `" + t0s + "` must be int or float: " + y1b;
            typs[t0s] = 'float';

            if( tokens[4]['vtype'] === 'string' && /^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( tokens[4]['value'] ) === false && /^-?\d*\.\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( tokens[4]['value'] ) === false)
            {
                vars[t0s] = parseFloat(0);
                vars['cast_failed'] = 1;
            }
            else
            {
                vars[t0s] = parseFloat( tokens[4]['value'] );
                vars['cast_failed'] = 0;
            }

            done = true;
        }


        // String

        if( ! done && microlang_parse( tokens, [ '@', '=', 'string', '(', '#', ')' ] ) )
        {
            rt = 'string';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[4]['value'] + '';

            done = true;
        }


        // + Sum

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '+', '#' ] ) )
        {
            err = microlang_typecheck( tokens, "?*2" ); if( err !== '' ) return err + y1b;

            if( tokens[2]['vtype'] === 'string' )
            {
                rt = 'string';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] + tokens[4]['value'];

                if( vars[t0s].length > max_str_len ) return "string too long: " + y1b;
            }
            else if( tokens[2]['vtype'] === 'int' )
            {
                rt = 'int';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] + tokens[4]['value'];

                if( vars[t0s] > 9223372036854775807 || vars[t0s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( tokens[2]['vtype'] === 'float' )
            {
                rt = 'float';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] + tokens[4]['value'];
            }

            done = true;
        }


        // - Diff

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '-', '#' ] ) )
        {
            err = microlang_typecheck( tokens, "?N2" ); if( err !== '' ) return err + y1b;

            if( tokens[2]['vtype'] === 'int' )
            {
                rt = 'int';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] - tokens[4]['value'];

                if( vars[t0s] > 9223372036854775807 || vars[t0s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( tokens[5]['vtype'] === 'float' )
            {
                rt = 'float';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] - tokens[4]['value'];
            }

            done = true;
        }


        // * Mult

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '*', '#' ] ) )
        {
            err = microlang_typecheck( tokens, "?N2" ); if( err !== '' ) return err + y1b;

            if( tokens[2]['vtype'] === 'int' )
            {
                rt = 'int';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] * tokens[4]['value'];

                if( vars[t0s] > 9223372036854775807 || vars[t0s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( tokens[2]['vtype'] === 'float' )
            {
                rt = 'float';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] * tokens[4]['value'];
            }

            done = true;
        }


        // / Div

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '/', '#' ] ) )
        {
            err = microlang_typecheck( tokens, "?N2" ); if( err !== '' ) return err + y1b;

            if( tokens[4]['value'] === 0 ) return "division by zero: " + y1b;

            if( tokens[2]['vtype'] === 'int' )
            {
                rt = 'int';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = Math.floor( tokens[2]['value'] / tokens[4]['value'] );

                if( vars[t0s] > 9223372036854775807 || vars[t0s] < -9223372036854775808 ) return "overflow: " + y1b;

                vars[t0s] = parseInt( vars[t0s] );
            }
            else if( tokens[2]['vtype'] === 'float' )
            {
                rt = 'float';
                if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
                typs[t0s] = rt;

                vars[t0s] = tokens[2]['value'] / tokens[4]['value'];
            }

            done = true;
        }


        // % Modulo

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '%', '#' ] ) )
        {
            err = microlang_typecheck( tokens, "?II"  ); if( err !== '' ) return err + y1b;

            if( tokens[4]['value'] === 0 ) return "division by zero: " + y1b;

            rt = 'int';
            if( isset( typs[t0s] ) && typs[t0s] !== rt ) return "variable `" + t0s + "` must be "+ rt + ": " + y1b;
            typs[t0s] = rt;

            vars[t0s] = tokens[2]['value'] % tokens[4]['value'];

            done = true;
        }


        // If Then [Else]

        if( ! done && ( microlang_parse( tokens, [ 'if', '#', '~', '#', 'then', ':' ] ) || microlang_parse( tokens, [ 'if', '#', '~', '#', 'then', ':', 'else', ':' ] ) ) )
        {
            err = microlang_typecheck( tokens, "?1" ); if( err !== '' ) return err + y1b;

            if( tokens[5]['value'] === null ) return "undefined label " + tokens[5]['symbol'] + ": " + y1b;
            if( tn === 8 && tokens[7]['value'] === null ) return "undefined label " + tokens[7]['value'] + ": " + y1b;

            if( tokens[2]['symbol'] === '==' )
            {
                if( tokens[1]['value'] == tokens[3]['value'] )
                {
                    y = tokens[5]['value'];
                    iter++;
                    continue;
                }
                else
                {
                    if( tn === 8 && tokens[7]['value'] !== null )
                    {
                        y = tokens[7]['value'];
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( tokens[2]['symbol'] === '!=' )
            {
                if( tokens[1]['value'] != tokens[3]['value'] )
                {
                    y = tokens[5]['value'];
                    iter++;
                    continue;
                }
                else
                {
                    if( tn === 8 && tokens[7]['value'] !== null )
                    {
                        y = tokens[7]['value'];
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( tokens[2]['symbol'] === '>' )
            {
                if( tokens[1]['value'] > tokens[3]['value'] )
                {
                    y = tokens[5]['value'];
                    iter++;
                    continue;
                }
                else
                {
                    if( tn === 8 && tokens[7]['value'] !== null )
                    {
                        y = tokens[7]['value'];
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( tokens[2]['symbol'] === '<' )
            {
                if( tokens[1]['value'] < tokens[3]['value'] )
                {
                    y = tokens[5]['value'];
                    iter++;
                    continue;
                }
                else
                {
                    if( tn === 8 && tokens[7]['value'] !== null )
                    {
                        y = tokens[7]['value'];
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( tokens[2]['symbol'] === '<=' )
            {
                if( tokens[1]['value'] <= tokens[3]['value'] )
                {
                    y = tokens[5]['value'];
                    iter++;
                    continue;
                }
                else
                {
                    if( tn === 8 && tokens[7]['value'] !== null )
                    {
                        y = tokens[7]['value'];
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( tokens[2]['symbol'] === '>=' )
            {
                if( tokens[1]['value'] >= tokens[3]['value'] )
                {
                    y = tokens[5]['value'];
                    iter++;
                    continue;
                }
                else
                {
                    if( tn === 8 && tokens[7]['value'] !== null )
                    {
                        y = tokens[7]['value'];
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }
        }

        if( ! done )
        {
            return "syntax error: " + y1b;
        }

        y++;

        if( y >= lines_count )
        {
            break;
        }
    }

    delete vars[ 'cast_failed' ];

    return "";
}
