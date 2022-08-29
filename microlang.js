//
// FreeBSD 2-clause license
//
// microlang.js
//
// A javascript microlang interpreter
//
// microlang version 1.3
// js intepreter version 1.3.3
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

function microlang( code, vars, options )
{
    var keywords,
        execute,
        max_str_len,
        max_iterations,
        labels,
        label,
        stack,
        lines,
        typs,
        key,
        value,
        ch,
        sl,
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
        rt,
        j,
        tcn,
        btw;



    //
    // implementation of php functions to ease
    // porting the php interpreter to js
    //

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



    var count = function( x )
    {
        return x.length;
    };



    var strlen = function( x )
    {
        x = '' + x;
        return x.length;
    };



    var substr8 = function( x, s, n )
    {
        var e,l;
        x = '' + x;
        l = x.length;
        if( s < 0 ) s = ( l + s ) < 0 ? 0 : l + s;
        if( typeof( n ) === 'undefined' )
        {
            e = l;
        }
        else
        {
            e = n >= 0 ? s + n : l + n;
        }
        if( e <= s ) return "";
        return x.substring( s, e );
    };



    // TODO: handle UTF-8 multibyte characters

    var mb_substr8 = function( x, s, n )
    {
        var e,l;
        x = '' + x;
        l = x.length;
        if( s < 0 ) s = ( l + s ) < 0 ? 0 : l + s;
        if( typeof( n ) === 'undefined' )
        {
            e = l;
        }
        else
        {
            e = n >= 0 ? s + n : l + n;
        }
        if( e <= s ) return "";
        return x.substring( s, e );
    };



    // TODO: handle UTF-8 multibyte characters

    var mb_strpos = function( t, s )
    {
        var i;

        t = '' + t;
        s = '' + s;

        i = t.indexOf( s );

        if( i === -1 )
        {
            return false;
        }

        return i;
    };



    // TODO: handle UTF-8 multibyte characters

    var mb_strlen = function( x )
    {
        x = '' + x;
        return x.length;
    };



    var strpos = function( t, s )
    {
        var i;

        t = '' + t;
        s = '' + s;

        i = t.indexOf( s );

        if( i === -1 )
        {
            return false;
        }

        return i;
    };



    var str_replace = function( s, r, t )
    {
        s = '' + s;
        r = '' + r;
        t = '' + t;

        return t.split( s ).join( r );
    };



    var microlang_totext = function( t )
    {
        var val;

        if( t[ 'symbol' ] !== null )
        {
            return "`" + t[ 'symbol' ] + "`";
        }

        if( t[ 'vtype' ] === 'string' )
        {
            return "`\"" + t[ 'value' ] + "\"`";
        }
        else if( t[ 'vtype' ] === 'int' )
        {
            return "`\"" + t[ 'value' ] + "\"`";
        }
        else
        {
            val = t[ 'value' ] + '';
            val = val.toLowerCase( );
            val = val.split( "e" );
            if( val[ 0 ].indexOf( "." ) === -1 )
            {
                val[ 0 ] += ".0";
            }
            val = val.join( "e" );
            val = "`" + val + "`";
            return val;
        }
    };



    var microlang_parse = function( tokens, expected )
    {
        var i,n,t,s,e;

        n = count( tokens );

        if( n !== count( expected ) ) return false;

        for( i = 0; i < n; i++ )
        {
            t = tokens[ i ][ 'type' ];
            s = tokens[ i ][ 'symbol' ];
            e = expected[ i ];

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



    var microlang_typecheck = function( exe, tok, types )
    {
        var i,n,c,t,v,y,isvar,t0,t1,tokens;

        tokens = [];

        n = count( tok );

        for( i = 0; i < n; i++ )
        {
            if( tok[ i ][ 'type' ] === 'variable' || tok[ i ][ 'type' ] === 'value' )
            {
                tokens.push( tok[ i ] );
            }
        }

        n = count( types );
        t0 = tokens[ 0 ][ 'vtype' ];
        t1 = tokens[ 1 ][ 'vtype' ];

        for( i = 0; i < n; i++ )
        {
            c = types.charAt( i );
            t = tokens[ i ][ 'vtype' ];
            v = tokens[ i ][ 'value' ];
            y = tokens[ i ][ 'symbol' ];

            isvar = ( tokens[ i ][ 'type' ] === 'variable' );

            if( c === 'U' ) // a variable not yet defined
            {
                if( ! isvar ) return "variable expected: ";
                if( t !== null ) return "variable `" + y + "` is already defined: ";
                continue;
            }

            if( isvar && t === null ) return "variable `" + y + "` is not defined: ";

            if( c === '*' ) // a value of any type or a variable of any type with a value set
            {
                if( isvar && t === null ) return "variable `" + y + "` is not defined: ";
                continue;
            }

            if( c === 's' || c === 'i' || c === 'f' || c === 'n' || c === '+' ) // a variable
            {
                if( ! isvar ) return "variable expected: ";

                if( c === 's' && t !== 'string' ) return "variable `" + y + "` must be string: ";
                if( c === 'f' && t !== 'float'  ) return "variable `" + y + "` must be int: ";
                if( c === 'i' && t !== 'int'    ) return "variable `" + y + "` must be float: ";
                if( c === 'n' && t === 'string' ) return "variable `" + y + "` must be int or float: ";

                continue;
            }

            if( isvar && v === null && exe ) return "variable `" + y + "` has undefined value: ";

            // a value or a variable with defined value

            if( c === 'S' && t !== 'string' ) return "parameter `" + ( i + 1 ) + "` must be string: ";
            if( c === 'I' && t !== 'int'    ) return "parameter `" + ( i + 1 ) + "` must be int: ";
            if( c === 'F' && t !== 'float'  ) return "parameter `" + ( i + 1 ) + "` must be float: ";
            if( c === 'N' && t === 'string' ) return "parameter `" + ( i + 1 ) + "` must be int or float: ";
            if( c === '1' && t !== t0       ) return i === 1 ? "variable `" + tokens[ 0 ][ 'symbol' ] + "` must be `" + t + "`: " : "operands must be of the same type: ";
            if( c === '2' && t !== t1       ) return "operands must be of the same type: ";
        }

        return "";
    };



    var microlang_varcheck = function( t )
    {
        if( t[ 'type' ] === 'variable' )
        {
            if( t[ 'vtype' ] === null ) return "variable `" + t[ 'symbol' ] + "` is not defined: ";
            if( t[ 'value' ] === null ) return "variable `" + t[ 'symbol' ] + "` has undefined value: ";
        }
        else
        {
            return "expected variable: ";
        }
    };



    var microlang_trim = function( txt )
    {
        var c,n;

        while( true )
        {
            if( txt === '' ) break;

            c = txt.charAt( 0 );
            if( c === '\n' || c === ' ' || c === '\r' || c === '\t' )
            {
                txt = txt.substring( 1 );
            }
            else
            {
                break;
            }
        }

        while( true )
        {
            if( txt === '' ) break;

            n = strlen( txt );

            c = txt.charAt( n - 1 );

            if( c === '\n' || c === ' ' || c === '\r' || c === '\t' )
            {
                txt = txt.substring( 0, n - 1 );
            }
            else
            {
                break;
            }
        }

        return txt;
    };



    var microlang_between = function( str, sm, em )
    {
        var i1,i2,sml,eml;

        i1 = 0;
        i2 = 0;
        sml = mb_strlen( sm );
        eml = mb_strlen( em );

        if( sml === 0 && eml === 0 )
        {
            return str;
        }

        if( sml === 0 )
        {
            i2 = mb_strpos( str, em );
            if( i2 === false )
            {
                return false;
            }

            return mb_substr8( str, 0, i2 );
        }

        if( eml === 0 )
        {
            i1 = mb_strpos( str, sm );

            if( i1 === false ) return false;

            str = mb_substr8( str, i1 + sml );

            while( true )
            {
                i1 = mb_strpos( str, sm );
                if( i1 === false )
                {
                    break;
                }
                else
                {
                    str = mb_substr8( str, i1 + sml );
                }
            }

            return str;
        }

        i1 = mb_strpos( str, sm );

        if( i1 === false ) return false;

        i2 = mb_strpos( str, em, i1 + sml );

        if( i2 === false ) return false;

        str = mb_substr8( str, i1 + sml, i2 - i1 - sml );

        while( true )
        {
            i1 = mb_strpos( str, sm );
            if( i1 === false ) break;
            str = mb_substr8( str, i1 + sml );
        }

        return str;
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

        error[ 'msg' ] = '';

        parts = [];

        n = line.length;
        part = "";
        s = ' '; // currently parsing: ' ' nothing, 'o' operator, 's' string, 'n' number, 'y' symbol ( keyword, variable, label )
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

            if( "( , )".indexOf( c ) !== -1 )
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
                    error = "unexpected escape character `c`: ";
                    return parts;
                }

                if( c2 === "\\\\" )
                {
                    part += "\\";
                    i++;
                    continue;
                }
                else if( c2 === "\\n" )
                {
                    part += "\n";
                    i++;
                    continue;
                }
                else if( c2 === "\\r" )
                {
                    part += "\r";
                    i++;
                    continue;
                }
                else if( c2 === "\\t" )
                {
                    part += "\t";
                    i++;
                    continue;
                }
                else if( c2 === "\\\"" )
                {
                    part += "\"";
                    i++;
                    continue;
                }
                else if( c2 === "\\'" )
                {
                    part += "'";
                    i++;
                    continue;
                }
                else
                {
                    error = "unrecognized escape sequence `" + c2 + "`: ";
                    return parts;
                }
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
                        error[ 'msg' ] = "unexpected character `" + c + "`: ";
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
                    part = "0.";
                    s = 'n';
                    p = 'd';
                    continue;
                }

                error[ 'msg' ] = "unexpected character `" + c + "`: ";
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

                error[ 'msg' ] = "unexpected character `" + c + "`: ";
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

            error[ 'msg' ] = "unexpected character `" + c + "`: ";
            return parts;
        }

        if( part !== '' )
        {
            parts.push( part );
        }

        return parts;
    };



    // Begin



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



    if( typeof( options ) === 'undefined' || options === null )
    {
        options = {};
    }

    if( typeof( options ) !== 'object' )
    {
        return "optional `options` parameter must be object"
    }

    max_iterations = typeof( options[ 'max_iterations' ] ) !== 'undefined' ? options[ 'max_iterations' ] : 1000;
    if( typeof( max_iterations ) !== 'number' ) return "`max_iterations` options must be int";

    max_str_len = typeof( options[ 'max_str_len' ] ) !== 'undefined' ? options[ 'max_str_len' ] : 1048576;
    if( typeof( max_str_len ) !== 'number' ) return "`max_str_len` options must be int";

    if( typeof( options[ 'action' ] ) !== 'undefined' )
    {
        if( options[ 'action' ] === 'execute' ) execute = true;
        else if( options[ 'action' ] === 'analyze' ) execute = false;
        else return "`action` option must be either `execute` or `analyze`";
    }
    else
    {
        execute = true;
    }


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
        l = lines[ i ];
        idx = l.indexOf( '//' );
        if( idx !== -1 )
        {
            l = l.substring( 0, idx );
        }
        l = l.trim( );
        lines[ i ] = l;
    }


    // tokenize

    newlines = lines.slice( );
    lines = [];
    y = -1;
    y1b = y + 1;
    m = newlines.length;
    for( k = 0; k < m; k++ )
    {
        l = newlines[ k ];

        y++;
        y1b = y + 1;

        parts = microlang_splitline( l, error );
        if( error[ 'msg' ] !== '' ) return error[ 'msg' ] + y1b;

        tokens = [];
        n = parts.length;
        for( i = 0; i < n; i++ )
        {
            p = parts[ i ];


            // Keywords

            if( keywords.indexOf( p ) !== -1 )
            {
                tokens.push( { 'type': 'keyword', 'symbol': p, 'value': null, 'vtype': null } );

                if( i < n - 1 && parts[ i + 1 ] === '=' ) return "keywords cannot be used for variable names ( " + p + " ):" + y1b;

                continue;
            }


            // Labels

            if( p.slice( -1 ) === ":" && i === 0 )
            {
                p = p.substring( 0, p.length - 1 );
                if( keywords.indexOf( p ) !== -1 ) return "keywords cannot be used for label names ( " + p + " ):" + y1b;
                tokens.push( { 'type': 'label', 'symbol': p, 'value': y, 'vtype': null } );
                if( typeof( labels[ p ] ) !== 'undefined' ) return "Label " + p + " duplicate: " + y1b;
                labels[ p ] = y;
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

            if( /^-?\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/.test( p ) )
            {
                if( parseFloat( p ) > 9223372036854775807 || parseFloat( p ) < -9223372036854775808 ) return "overflow: " + y1b;

                tokens.push( { 'type': 'value', 'symbol': null, 'value': parseInt( parseFloat( p ) ), 'vtype': 'int' } );
                continue;
            }


            // Floats

            if( /^-?\d*\.\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/.test( p ) )
            {
                tokens.push( { 'type': 'value', 'symbol': null, 'value': parseFloat( p ), 'vtype': 'float' } );
                continue;
            }


            // Variable names

            tokens.push( { 'type': 'variable', 'symbol': p, 'value': null, 'vtype': null } );

        }
        lines.push( tokens );
    }


    // execute

    y = 0;
    lines_count = count( lines );
    iter = 0;
    vars[ 'cast_failed' ] = 0;

    while( true )
    {
        done = false;

        y1b = y + 1;

        if( iter > max_iterations && max_iterations !== 0 ) return "max iterations exceeded: " + y1b;

        tok = lines[ y ];
        tn = count( tok );

        if( tn === 0 ) done = true;

        if( tn === 1 && tok[ 0 ][ 'type' ] === 'label' ) done = true;

        tokens = [];

        for( i = 0; i < tn; i++ )
        {
            t = tok[ i ];

            if( t[ 'type' ] === 'label' || t[ 'type' ] === 'value' || t[ 'type' ] === 'keyword' )
            {
                tokens.push( t );
            }
            else if( t[ 'type' ] === 'variable' )
            {
                if( typeof( typs[ t[ 'symbol' ] ] ) !== 'undefined' && typs[ t[ 'symbol' ] ] !== null )
                {
                    t[ 'value' ] = vars[ t[ 'symbol' ] ];
                    t[ 'vtype' ] = typs[ t[ 'symbol' ] ];
                    tokens.push( t );
                }
                else if( typeof( labels[ t[ 'symbol' ] ] ) !== 'undefined' && labels[ t[ 'symbol' ] ] !== null )
                {
                    t[ 'type' ] = 'label';
                    t[ 'value' ] = labels[ t[ 'symbol' ] ];
                    tokens.push( t );
                }
                else
                {
                    t[ 'value' ] = null;
                    t[ 'vtype' ] = null;
                    tokens.push( t );
                }
            }
        }


        t0s = done ? '' : tokens[ 0 ][ 'symbol' ];
        tcn = done ?  0 : tokens.length;


        // Goto

        if( ! done && microlang_parse( tokens, [ 'goto', ':' ] ) )
        {
            done = true;

            label = tokens[ 1 ][ 'symbol' ];

            if( typeof( labels[ label ] ) !== 'undefined' && labels[ label ] !== null )
            {
                iter++;
                if( execute )
                {
                    y = labels[ label ];
                    continue;
                }
            }
            else return "unknown label `" + label + "`: " + y1b;
        }


        // Gosub

        if( ! done && microlang_parse( tokens, [ 'gosub', ':' ] ) )
        {
            done = true;

            label = tokens[ 1 ][ 'symbol' ];

            if( typeof( labels[ label ] ) !== 'undefined' && labels[ label ] !== null )
            {
                iter++;
                if( execute )
                {
                    stack.push( y );
                    y = labels[ label ];
                    continue;
                }
            }
            else return "unknown label `" + label + "`: " + y1b;
        }


        // Return

        if( ! done && microlang_parse( tokens, [ 'return' ] ) )
        {
            done = true;

            if( execute )
            {
                if( stack.length === 0 ) return "return without gosub: " + y1b;
                y = stack.pop();
                continue;
            }
        }


        // Exit

        if( ! done && microlang_parse( tokens, [ 'exit' ] ) )
        {
            done = true;

            if( execute )
            {
                break;
            }
        }


        // Exit with error message

        if( ! done && microlang_parse( tokens, [ 'exit', '#' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "*" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                return tokens[ 1 ][ 'value' ] + '';
            }
        }


        // Int, Float, String declaration with first assignment

        if( ! done && ( microlang_parse( tokens, [ 'int', '@', "=", "#" ] ) || microlang_parse( tokens, [ 'float', '@', "=", "#" ] ) || microlang_parse( tokens, [ 'string', '@', "=", "#" ] ) ) )
        {
            err = microlang_typecheck( execute, tokens, "U" + tokens[0]['symbol'].charAt(0).toUpperCase() ); if( err !== '' ) return err + y1b;

            typs[ tokens[ 1 ][ 'symbol' ] ] = tokens[ 0 ][ 'symbol' ];
            vars[ tokens[ 1 ][ 'symbol' ] ] = tokens[ 3 ][ 'value' ];

            done = true;
        }


        // Int, Float, String declaration

        if( ! done && ( t0s === 'int' || t0s === 'float' || t0s === 'string' ) && tcn >= 2 )
        {
            j = 1;

            while( true )
            {
                if( tokens[ j ][ 'type' ] === 'variable' )
                {
                    if( typeof( typs[ tokens[ j ][ 'symbol' ] ] ) !== 'undefined' && typs[ tokens[ j ][ 'symbol' ] ] !== null ) return "variable `" + tokens[ j ][ 'symbol' ] + "` already defined: " + y1b;

                    typs[ tokens[ j ][ 'symbol' ] ] = t0s;

                    vars[ tokens[ j ][ 'symbol' ] ] = null;
                }
                else
                {
                    return "variable expected: " + y1b;
                }

                if( j >= tcn - 1 ) break;

                if( j < tcn - 2 )
                {
                    j++;
                    if( tokens[ j ][ 'symbol' ] !== ',' ) return "`,` expected: " + y1b;
                    j++;
                }
            }

            done = true;
        }


        // = Assignment

        if( ! done && microlang_parse( tokens, [ '@', '=', '#' ] ) )
        {
            if( t0s === 'cast_failed' ) return "`cast_failed` is a reserved variable name: " + y1b;

            err = microlang_typecheck( execute, tokens, "+1" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = tokens[ 2 ][ 'value' ];
            }

            done = true;
        }

        if( ! done && microlang_parse( tokens, [ 'K', '=', '#' ] ) )
        {
            return "keywords cannot be used for variable names ( " + tokens[ 0 ][ 'symbol' ] + " ): " + y1b;
        }

        if( ! done && microlang_parse( tokens, [ '@', '=', 'K' ] ) )
        {
            return "keyword unexpected at right side of assignment ( " + tokens[ 2 ][ 'symbol' ] + " ): " + y1b;
        }


        // Substring

        if( ! done && microlang_parse( tokens, [ '@', '=', 'substring', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "sSII" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = mb_substr8( tokens[ 4 ][ 'value' ], tokens[ 6 ][ 'value' ], tokens[ 8 ][ 'value' ] );
            }

            done = true;
        }


        // Position

        if( ! done && microlang_parse( tokens, [ '@', '=', 'position', '(', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "iSS" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 4 ][ 'value' ] === '' || tokens[ 6 ][ 'value' ] === '' )
                {
                    vars[ t0s ] = -1;
                }
                else
                {
                    vars[ t0s ] = mb_strpos( tokens[ 4 ][ 'value' ], tokens[ 6 ][ 'value' ] );

                    if( vars[ t0s ] === false ) vars[ t0s ] = -1;
                }
            }

            done = true;
        }


        // Replace

        if( ! done && microlang_parse( tokens, [ '@', '=', 'replace', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "sSSS" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = str_replace( tokens[ 6 ][ 'value' ], tokens[ 8 ][ 'value' ], tokens[ 4 ][ 'value' ] );
                if( mb_strlen( vars[ t0s ] ) > max_str_len ) return "string too long: y1b";
            }

            done = true;
        }


        // Between

        if( ! done && microlang_parse( tokens, [ '@', '=', 'between', '(', '#', ',', '#', ',', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "sSSS" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = microlang_between( tokens[ 4 ][ 'value' ], tokens[ 6 ][ 'value' ], tokens[ 8 ][ 'value' ] );
                if( vars[ t0s ] === false ) vars[ t0s ] = "";
            }

            done = true;
        }

        if( ! done && microlang_parse( tokens, [ '@', '=', 'between', '(', '#', ',', '#', ',', '#', ',', '@', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "sSSSi" ); if( err !== '' ) return err . y1b;

            if( execute )
            {
                vars[ t0s ] = microlang_between( tokens[ 4 ][ 'value' ], tokens[ 6 ][ 'value' ], tokens[ 8 ][ 'value' ] );
                if( vars[ t0s ] === false )
                {
                    vars[ t0s ] = "";
                    vars[ tokens[ 10 ][ 'symbol' ] ] = 0;
                }
                else
                {
                    vars[ tokens[ 10 ][ 'symbol' ] ] = 1;
                }
            }

            done = true;
        }


        // Trim

        if( ! done && microlang_parse( tokens, [ '@', '=', 'trim', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "sS" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = microlang_trim( tokens[ 4 ][ 'value' ] );
            }

            done = true;
        }


        // Len

        if( ! done && microlang_parse( tokens, [ '@', '=', 'len', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "iS" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = mb_strlen( tokens[ 4 ][ 'value' ] );
            }

            done = true;
        }


        // Typeof

        if( ! done && microlang_parse( tokens, [ '@', '=', 'typeof', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "s+" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = tokens[ 4 ][ 'vtype' ];
            }

            done = true;
        }


        // Int

        if( ! done && microlang_parse( tokens, [ '@', '=', 'int', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "i*" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 4 ][ 'vtype' ] === 'string' && /^-?\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/.test( tokens[ 4 ][ 'value' ] ) === false && /^-?\d*\.\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/.test( tokens[ 4 ][ 'value' ] ) === false )
                {
                    vars[ t0s ] = 0;
                    vars[ 'cast_failed' ] = 1;
                }
                else
                {
                    if( parseFloat( tokens[ 4 ][ 'value' ] ) > 9223372036854775807 || parseFloat( tokens[ 4 ][ 'value' ] ) < -9223372036854775808 )
                    {
                        vars[ t0s ] = 0;
                        vars[ 'cast_failed' ] = 1;
                    }
                    else
                    {
                        vars[ t0s ] = parseInt( tokens[ 4 ][ 'value' ] );
                        vars[ 'cast_failed' ] = 0;
                    }
                }
            }

            done = true;
        }


        // Float

        if( ! done && microlang_parse( tokens, [ '@', '=', 'float', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "n*" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 4 ][ 'vtype' ] === 'string' && /^-?\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/.test( tokens[ 4 ][ 'value' ] ) === false && /^-?\d*\.\d+(e?\d+|E?\d+|(e-)?\d+|(E-)?\d+)?$/.test( tokens[ 4 ][ 'value' ] ) === false )
                {
                    vars[ t0s ] = parseFloat( 0 );
                    vars[ 'cast_failed' ] = 1;
                }
                else
                {
                    vars[ t0s ] = parseFloat( tokens[ 4 ][ 'value' ] );
                    vars[ 'cast_failed' ] = 0;
                    typs[ t0s ] = 'float';
                }
            }

            done = true;
        }


        // String

        if( ! done && microlang_parse( tokens, [ '@', '=', 'string', '(', '#', ')' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "s*" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                vars[ t0s ] = '' + tokens[ 4 ][ 'value' ];
            }

            done = true;
        }


        // + Sum

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '+', '#' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "+12" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 2 ][ 'vtype' ] === 'string' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] + tokens[ 4 ][ 'value' ];

                    if( mb_strlen( vars[ t0s ] ) > max_str_len  ) return "string too long: " + y1b;
                }
                else if( tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] + tokens[ 4 ][ 'value' ];

                    if( vars[ t0s ] > 9223372036854775807 || vars[ t0s ] < -9223372036854775808 ) return "overflow: " + y1b;
                }
                else if( tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] + tokens[ 4 ][ 'value' ];
                }
            }

            done = true;
        }


        // - Diff

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '-', '#' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "n12" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] - tokens[ 4 ][ 'value' ];

                    if( vars[ t0s ] > 9223372036854775807 || vars[ t0s ] < -9223372036854775808 ) return "overflow: " + y1b;
                }
                else if( tokens[ 5 ][ 'vtype' ] === 'float' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] - tokens[ 4 ][ 'value' ];
                }
            }

            done = true;
        }


        // * Mult

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '*', '#' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "n12" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] * tokens[ 4 ][ 'value' ];

                    if( vars[ t0s ] > 9223372036854775807 || vars[ t0s ] < -9223372036854775808 ) return "overflow: " + y1b;
                }
                else if( tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] * tokens[ 4 ][ 'value' ];
                }
            }

            done = true;
        }


        // / Div

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '/', '#' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "n12" ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 4 ][ 'value' ] === 0 ) return "division by zero: " + y1b;
                if( tokens[ 2 ][ 'vtype' ] === 'int' )
                {
                    vars[ t0s ] = Math.floor( tokens[ 2 ][ 'value' ] / tokens[ 4 ][ 'value' ] );

                    if( vars[ t0s ] > 9223372036854775807 || vars[ t0s ] < -9223372036854775808 ) return "overflow: " + y1b;

                    vars[ t0s ] = parseInt( vars[ t0s ] );
                }
                else if( tokens[ 2 ][ 'vtype' ] === 'float' )
                {
                    vars[ t0s ] = tokens[ 2 ][ 'value' ] / tokens[ 4 ][ 'value' ];
                }
            }

            done = true;
        }


        // % Modulo

        if( ! done && microlang_parse( tokens, [ '@', '=', '#', '%', '#' ] ) )
        {
            err = microlang_typecheck( execute, tokens, "iII"  ); if( err !== '' ) return err + y1b;

            if( execute )
            {
                if( tokens[ 4 ][ 'value' ] === 0 ) return "division by zero: " + y1b;
                vars[ t0s ] = tokens[ 2 ][ 'value' ] % tokens[ 4 ][ 'value' ];
            }

            done = true;
        }


        // If Then [ Else ]

        if( ! done && ( microlang_parse( tokens, [ 'if', '#', '~', '#', 'then', ':' ] ) || microlang_parse( tokens, [ 'if', '#', '~', '#', 'then', ':', 'else', ':' ] ) ) )
        {
            err = microlang_typecheck( execute, tokens, "*1" ); if( err !== '' ) return err + y1b;

            if( tokens[ 5 ][ 'value' ] === null ) return "undefined label `" + tokens[ 5 ][ 'symbol' ] + "`: " + y1b;
            if( tn === 8 && tokens[ 7 ][ 'value' ] === null ) return "undefined label `" + tokens[ 7 ][ 'value' ] + "`: " + y1b;

            if( execute )
            {
                if( tokens[ 2 ][ 'symbol' ] === '==' )
                {
                    if( tokens[ 1 ][ 'value' ] == tokens[ 3 ][ 'value' ] )
                    {
                        y = tokens[ 5 ][ 'value' ];
                        iter++;
                        continue;
                    }
                    else
                    {
                        if( tn === 8 && tokens[ 7 ][ 'value' ] !== null )
                        {
                            y = tokens[ 7 ][ 'value' ];
                            iter++;
                            continue;
                        }
                        else
                        {
                            done = true;
                        }
                    }
                }

                if( tokens[ 2 ][ 'symbol' ] === '!=' )
                {
                    if( tokens[ 1 ][ 'value' ] != tokens[ 3 ][ 'value' ] )
                    {
                        y = tokens[ 5 ][ 'value' ];
                        iter++;
                        continue;
                    }
                    else
                    {
                        if( tn === 8 && tokens[ 7 ][ 'value' ] !== null )
                        {
                            y = tokens[ 7 ][ 'value' ];
                            iter++;
                            continue;
                        }
                        else
                        {
                            done = true;
                        }
                    }
                }

                if( tokens[ 2 ][ 'symbol' ] === '>' )
                {
                    if( tokens[ 1 ][ 'value' ] > tokens[ 3 ][ 'value' ] )
                    {
                        y = tokens[ 5 ][ 'value' ];
                        iter++;
                        continue;
                    }
                    else
                    {
                        if( tn === 8 && tokens[ 7 ][ 'value' ] !== null )
                        {
                            y = tokens[ 7 ][ 'value' ];
                            iter++;
                            continue;
                        }
                        else
                        {
                            done = true;
                        }
                    }
                }

                if( tokens[ 2 ][ 'symbol' ] === '<' )
                {
                    if( tokens[ 1 ][ 'value' ] < tokens[ 3 ][ 'value' ] )
                    {
                        y = tokens[ 5 ][ 'value' ];
                        iter++;
                        continue;
                    }
                    else
                    {
                        if( tn === 8 && tokens[ 7 ][ 'value' ] !== null )
                        {
                            y = tokens[ 7 ][ 'value' ];
                            iter++;
                            continue;
                        }
                        else
                        {
                            done = true;
                        }
                    }
                }

                if( tokens[ 2 ][ 'symbol' ] === '<=' )
                {
                    if( tokens[ 1 ][ 'value' ] <= tokens[ 3 ][ 'value' ] )
                    {
                        y = tokens[ 5 ][ 'value' ];
                        iter++;
                        continue;
                    }
                    else
                    {
                        if( tn === 8 && tokens[ 7 ][ 'value' ] !== null )
                        {
                            y = tokens[ 7 ][ 'value' ];
                            iter++;
                            continue;
                        }
                        else
                        {
                            done = true;
                        }
                    }
                }

                if( tokens[ 2 ][ 'symbol' ] === '>=' )
                {
                    if( tokens[ 1 ][ 'value' ] >= tokens[ 3 ][ 'value' ] )
                    {
                        y = tokens[ 5 ][ 'value' ];
                        iter++;
                        continue;
                    }
                    else
                    {
                        if( tn === 8 && tokens[ 7 ][ 'value' ] !== null )
                        {
                            y = tokens[ 7 ][ 'value' ];
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
            else
            {
                done = true;
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
