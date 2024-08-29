CodeMirror.defineMode("basic", function(conf, parserConf) {
    var ERRORCLASS = 'error';

    function wordRegexp(words) {
        return new RegExp("^((" + words.join(")|(") + "))\\b", "i");
    }

    var singleOperators = new RegExp("^[\\+\\-\\*/%&\\\\|\\^~<>!]");
    var singleDelimiters = new RegExp('^[\\(\\)\\[\\]\\{\\}@,:`=;\\.]');
    var doubleOperators = new RegExp("^((==)|(<>)|(<=)|(>=)|(<>)|(<<)|(>>)|(//)|(\\*\\*))");
    var doubleDelimiters = new RegExp("^((\\+=)|(\\-=)|(\\*=)|(%=)|(/=)|(&=)|(\\|=)|(\\^=))");
    var tripleDelimiters = new RegExp("^((//=)|(>>=)|(<<=)|(\\*\\*=))");
    var identifiers = new RegExp("^[_A-Za-z][_A-Za-z0-9]*");

    var openingKeywords = ['if'];
    var middleKeywords = ['then', 'else'];
    var endKeywords = ['next', 'loop'];

    var wordOperators = wordRegexp(['in']);
    var commonkeywords = ['stop', 'pop', 'return', 'repaint', 'sendsms','rand', 'alphagel', 'COLORALPHAGEL', 'end', 'new', 'run', 'dir', 'deg', 'rad', 'bye', 'goto', 'gosub', 'sleep', 'print', 'rem', 'dim', 'if', 'then', 'cls', 'plot', 'drawline', 'fillrect', 'drawrect', 'fillroundrect', 'drawroundrect', 'fillarc', 'drawarc', 'drawstring', 'setcolor', 'blit', 'for', 'to', 'step', 'next', 'input', 'list', 'enter', 'load', 'save', 'delete', 'edit', 'trap', 'open', 'close', 'note', 'point', 'put', 'get', 'data', 'restore', 'read', 'bitand', 'bitor', 'bitxor', 'not', 'and', 'or', 'screenwidth', 'screenheight', 'iscolor', 'numcolors', 'stringwidth', 'stringheight', 'left', 'mid', 'right', 'chr', 'str', 'len', 'asc', 'val', 'up', 'down', 'left', 'right', 'fire', 'gamea', 'gameb', 'gamec', 'gamed', 'days', 'milliseconds', 'year', 'month', 'day', 'hour', 'minute', 'second', 'millisecond', 'rnd', 'err', 'fre', 'mod', 'editform', 'gaugeform', 'choiceform', 'dateform', 'messageform', 'log', 'exp', 'sqr', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'abs', 'print', 'input', ':', 'gelgrab', 'drawgel', 'spritegel', 'spritemove', 'spritehit', 'readdir', 'property', 'gelload', 'gelwidth', 'gelheight', 'playwav', 'playtone', 'inkey', 'select', 'alert', 'setfont', 'menuadd', 'menuitem', 'menuremove', 'call', 'endsub'];
    var commontypes = ['integer', 'string', 'double', 'decimal', 'boolean', 'short', 'char', 'float', 'single'];

    var keywords = wordRegexp(commonkeywords);
    var types = wordRegexp(commontypes);
    var stringPrefixes = '"';

    var opening = wordRegexp(openingKeywords);
    var middle = wordRegexp(middleKeywords);
    var closing = wordRegexp(endKeywords);
    var doubleClosing = wordRegexp(['end']);
    var doOpening = wordRegexp(['do']);

    var indentInfo = null;




    function indent(_stream, state) {
        state.currentIndent++;
    }

    function dedent(_stream, state) {
        state.currentIndent--;
    }
    // tokenizers
    function tokenBase(stream, state) {
        if (stream.eatSpace()) {
            return null;
        }

        var ch = stream.peek();

        // Handle Comments
        if (ch === "'") {
            stream.skipToEnd();
            return 'comment';
        }


        // Handle Number Literals
        if (stream.match(/^((&H)|(&O))?[0-9\.a-f]/i, false)) {
            var floatLiteral = false;
            // Floats
            if (stream.match(/^\d*\.\d+F?/i)) {
                floatLiteral = true;
            }
            else if (stream.match(/^\d+\.\d*F?/)) {
                floatLiteral = true;
            }
            else if (stream.match(/^\.\d+F?/)) {
                floatLiteral = true;
            }

            if (floatLiteral) {
                // Float literals may be "imaginary"
                stream.eat(/J/i);
                return 'number';
            }
            // Integers
            var intLiteral = false;
            // Hex
            if (stream.match(/^&H[0-9a-f]+/i)) {
                intLiteral = true;
            }
            // Octal
            else if (stream.match(/^&O[0-7]+/i)) {
                intLiteral = true;
            }
            // Decimal
            else if (stream.match(/^[1-9]\d*F?/)) {
                // Decimal literals may be "imaginary"
                stream.eat(/J/i);
                // TODO - Can you have imaginary longs?
                intLiteral = true;
            }
            // Zero by itself with no other piece of number.
            else if (stream.match(/^0(?![\dx])/i)) {
                intLiteral = true;
            }
            if (intLiteral) {
                // Integer literals may be "long"
                stream.eat(/L/i);
                return 'number';
            }
        }

        // Handle Strings
        if (stream.match(stringPrefixes)) {
            state.tokenize = tokenStringFactory(stream.current());
            return state.tokenize(stream, state);
        }

        // Handle operators and Delimiters
        if (stream.match(tripleDelimiters) || stream.match(doubleDelimiters)) {
            return null;
        }
        if (stream.match(doubleOperators)
                || stream.match(singleOperators)
                || stream.match(wordOperators)) {
            return 'operator';
        }
        if (stream.match(singleDelimiters)) {
            return null;
        }
        /*if (stream.match(doOpening)) {
            indent(stream, state);
            state.doInCurrentLine = true;
            return 'keyword';
        }
        if (stream.match(opening)) {
            if (!state.doInCurrentLine)
                indent(stream, state);
            else
                state.doInCurrentLine = false;
            return 'keyword';
        }*/
        if (stream.match(middle)) {
            return 'keyword';
        }

        if (stream.match(doubleClosing)) {
            dedent(stream, state);
            dedent(stream, state);
            return 'keyword';
        }
        if (stream.match(closing)) {
            dedent(stream, state);
            return 'keyword';
        }

        if (stream.match(types)) {
            return 'keyword';
        }

        if (stream.match(keywords)) {
            return 'keyword';
        }

        if (stream.match(identifiers)) {
            return 'variable';
        }

        // Handle non-detected items
        stream.next();
        return ERRORCLASS;
    }
    
      function nextUntilUnescaped(stream, end) {
    var escaped = false, next;
    while ((next = stream.next()) != null) {
      if (next == end && !escaped)
        return false;
      escaped = !escaped && next == "\\";
    }
    return escaped;
  }

    function tokenStringFactory(delimiter) {
        var singleline = delimiter.length == 1;
        var OUTCLASS = 'string';
        
        
          return function(stream, state) {
      if (!nextUntilUnescaped(stream, '"'))
        state.tokenize = tokenBase;
            return OUTCLASS;
        };
    }


    function tokenLexer(stream, state) {
        var style = state.tokenize(stream, state);
        var current = stream.current();

        // Handle '.' connected identifiers
        if (current === '.') {
            style = state.tokenize(stream, state);
            current = stream.current();
            if (style === 'variable') {
                return 'variable';
            } else {
                return ERRORCLASS;
            }
        }


        var delimiter_index = '[({'.indexOf(current);
        if (delimiter_index !== -1) {
            indent(stream, state);
        }
        if (indentInfo === 'dedent') {
            if (dedent(stream, state)) {
                return ERRORCLASS;
            }
        }
        delimiter_index = '])}'.indexOf(current);
        if (delimiter_index !== -1) {
            if (dedent(stream, state)) {
                return ERRORCLASS;
            }
        }

        return style;
    }

    var external = {
        electricChars: "dDpPtTfFeE ",
        startState: function() {
            return {
                tokenize: tokenBase,
                lastToken: null,
                currentIndent: 0,
                nextLineIndent: 0,
                doInCurrentLine: false


            };
        },
        token: function(stream, state) {
            if (stream.sol()) {
                state.currentIndent += state.nextLineIndent;
                state.nextLineIndent = 0;
                state.doInCurrentLine = 0;
            }
            var style = tokenLexer(stream, state);

            state.lastToken = {style: style, content: stream.current()};



            return style;
        },
        indent: function(state, textAfter) {
            var trueText = textAfter.replace(/^\s+|\s+$/g, '');
            if (trueText.match(closing) || trueText.match(doubleClosing) || trueText.match(middle))
                return conf.indentUnit * (state.currentIndent - 1);
            if (state.currentIndent < 0)
                return 0;
            return state.currentIndent * conf.indentUnit;
        }

    };
    return external;
});

CodeMirror.defineMIME("text/x-vb", "vb");
