This `$horthand` syntax
`$abs(@file().mtime - @file(@other).mtime) != 60`

Is equivalent to this cryptic construct:
```
{
    '$eval' : [
        {'$abs' : {
            '$eval' : [
                {'$eval' : ['@file', '.mtime']} // mtime of file
                {'$-' : 
                    {'$eval' : ['@other', '@file()', '.mtime']} // mtime of other file
                } // difference
            ] // time difference
        }}, // absolute difference
        {'$ne' : 60}
    ]
}
```


`$(. ~ foo || . ~ bar) && ! ~ hello`

`$1 + 1 = 2`

`$1 + 2 (2*4) > .`

`$! $between 1, 2`

`$between 1, 2 || $between 4, 5 || $between @otherBounds || $between @low, @high`




`$. between(1, 2) || $between(4, 5) || $between(@otherBounds) || $between(@low, @high)`


* there is no operator precedence
* parser works in 3 modes - Value, Operator, Parameter
* 

`V` - Value
`O` - Operator
`P` - Parameter
`K` - Key

```
$gt 1 || $gt 2 $gt 3
-O- P // V from ctxt
--V-- O  --P-- 
         -O- P // V from ctxt
-------V------ -O- P         
```

```
$between 1, 5 && 10 $between @bounds(.rect) && (foo bar $regex "/foo" "bar/")
----O--- P+ P // V from ctxt, P as array because of ','
------V------ O- P- // no operator precedence!
--------V---------- ---O---- _-----P-------  
                             ---K--- --K--^
-----------------------V------------------- O- _---------------P-------------
                                                -V- +V- ---O-- --P--- --+P--^ // two string concatenations
```

