<?

/*
 * ************************************************************
  |    Компилятор LIS=>BAS для MobileBASIC
  |    (с) Андрей Рейгант
  |    http://vk.com/holdfast
  |    http://mbteam.ru
 * ************************************************************
  |   Пример:
  -------------------------------------------------------------
 * ** открыть исходный код MobileBASIC для компиляции в файл Autorun.bas
  $lis = new LIS('10 SLEEP 5000', 'Autorun.bas');

  | или $lis = new LIS(file_get_contents('code.lis'),'Autorun.bas');

 * ** компилировать файл
  $lis->compile();

 * ************************************************************
 */

class LIS {

    public $data, $file, $save, $coderror, $warning, $bufbody, $buffer, $compile = false;
    public $lines = array();
    public $ltypes = array();
    public $lexems = array();
    public $vars = array();
    public $vtypes = array();
    public $currLine;
    public $float = array();
    public $ver = 1; //Version (1 - 1.8.2, 2 - 1.9.1)

    const INT = 0, FLOAT = 1, STR = 2;
    const Operator = 0, Integer = 1, Float = 2, String = 3, Variable = 4, Data = 5, ArrayByte = 6;

    public $ops = array("STOP", "POP", "RETURN", "END", "NEW", "RUN", "DIR", "DEG", "RAD", "BYE", "GOTO", "GOSUB", "SLEEP", "PRINT", "REM", "DIM", "IF", "THEN", "CLS", "PLOT", "DRAWLINE", "FILLRECT", "DRAWRECT", "FILLROUNDRECT", "DRAWROUNDRECT", "FILLARC", "DRAWARC", "DRAWSTRING", "SETCOLOR", "BLIT", "FOR", "TO", "STEP", "NEXT", "INPUT", "LIST", "ENTER", "LOAD", "SAVE", "DELETE", "EDIT", "TRAP", "OPEN", "CLOSE", "NOTE", "POINT", "PUT", "GET", "DATA", "RESTORE", "READ", "=", "<>", "<", "<=", ">", ">=", "(", ")", ",", "+", "-", "-", "*", "/", "^", "BITAND", "BITOR", "BITXOR", "NOT", "AND", "OR", "SCREENWIDTH", "SCREENHEIGHT", "ISCOLOR", "NUMCOLORS", "STRINGWIDTH", "STRINGHEIGHT", "LEFT$", "MID$", "RIGHT$", "CHR$", "STR$", "LEN", "ASC", "VAL", "UP", "DOWN", "LEFT", "RIGHT", "FIRE", "GAMEA", "GAMEB", "GAMEC", "GAMED", "DAYS", "MILLISECONDS", "YEAR", "MONTH", "DAY", "HOUR", "MINUTE", "SECOND", "MILLISECOND", "RND", "ERR", "FRE", "MOD", "EDITFORM", "GAUGEFORM", "CHOICEFORM", "DATEFORM", "MESSAGEFORM", "LOG", "EXP", "SQR", "SIN", "COS", "TAN", "ASIN", "ACOS", "ATAN", "ABS", "=", "#", "PRINT", "INPUT", ":", "GELGRAB", "DRAWGEL", "SPRITEGEL", "SPRITEMOVE", "SPRITEHIT", "READDIR$", "PROPERTY$", "GELLOAD", "GELWIDTH", "GELHEIGHT", "PLAYWAV", "PLAYTONE", "INKEY", "SELECT", "ALERT", "SETFONT", "MENUADD", "MENUITEM", "MENUREMOVE", "CALL", "ENDSUB", "REPAINT", "SENDSMS", "RAND", "ALPHAGEL", "COLORALPHAGEL", "PLATFORMREQUEST", "DELGEL", "DELSPRITE", "MKDIR");
    public $keywords = array("STOP", "POP", "RETURN", "END", "RUN", "DIR", "DEG", "RAD", "BYE", "CLS", "ENDSUB");
    public $functions = array("NEW", "GOTO", "GOSUB", "SLEEP", "PRINT", "REM", "DIM", "IF", "THEN", "PLOT", "DRAWLINE", "FILLRECT", "DRAWRECT", "FILLROUNDRECT", "DRAWROUNDRECT", "FILLARC", "DRAWARC", "DRAWSTRING", "SETCOLOR", "BLIT", "FOR", "TO", "STEP", "NEXT", "INPUT", "LIST", "ENTER", "LOAD", "SAVE", "DELETE", "EDIT", "TRAP", "OPEN", "CLOSE", "NOTE", "POINT", "PUT", "GET", "DATA", "RESTORE", "READ", "BITAND", "BITOR", "BITXOR", "NOT", "AND", "OR", "SCREENWIDTH", "SCREENHEIGHT", "ISCOLOR", "NUMCOLORS", "STRINGWIDTH", "STRINGHEIGHT", "LEFT$", "MID$", "RIGHT$", "CHR$", "STR$", "LEN", "ASC", "VAL", "UP", "DOWN", "LEFT", "RIGHT", "FIRE", "GAMEA", "GAMEB", "GAMEC", "GAMED", "DAYS", "MILLISECONDS", "YEAR", "MONTH", "DAY", "HOUR", "MINUTE", "SECOND", "MILLISECOND", "RND", "ERR", "FRE", "MOD", "EDITFORM", "GAUGEFORM", "CHOICEFORM", "DATEFORM", "MESSAGEFORM", "LOG", "EXP", "SQR", "SIN", "COS", "TAN", "ASIN", "ACOS", "ATAN", "ABS", "=", "#", "PRINT", "INPUT", ":", "GELGRAB", "DRAWGEL", "SPRITEGEL", "SPRITEMOVE", "SPRITEHIT", "READDIR$", "PROPERTY$", "GELLOAD", "GELWIDTH", "GELHEIGHT", "PLAYWAV", "PLAYTONE", "INKEY", "SELECT", "ALERT", "SETFONT", "MENUADD", "MENUITEM", "MENUREMOVE", "CALL", "SENDSMS", "RAND", "ALPHAGEL", "COLORALPHAGEL", "PLATFORMREQUEST", "DELGEL", "DELSPRITE", "MKDIR");
    public $func191 = array("REPAINT", "SENDSMS", "RAND", "ALPHAGEL", "COLORALPHAGEL", "PLATFORMREQUEST", "DELGEL", "DELSPRITE", "MKDIR");

    function LIS($code, $newname, $v = 1) {
        $this->data = $code;
        $this->save = $newname;
        if ($v > 0 && $v < 3)
            $this->ver = $v;
    }

    function compile() {
        if (!$this->compile) {
            $string = explode("\n", $this->data);
            for ($i = 0; $i < count($string); $i++) {
                if (trim($string[$i]) != "") {
                    $this->lines[] = trim($string[$i]);
                }
            }
            $this->analize();
            if ($this->coderror != "")
                return array("error", $this->coderror);
            $this->compileCode();
            file_put_contents($this->save, $this->buffer);
            $this->compile = true;
            if ($this->warning != "")
                return array("warning", $this->warning);
        }
    }

    function analize() {
        $this->currLine = 0;
        for ($i = 0; $i < count($this->lines); $i++) {
            $this->analizeLine($this->lines[$i]);
            $this->check($this->currLine);
            $this->currLine++;
            if ($this->coderror != "")
                break;
        }
    }

    function check($lineNum) {
        $braketCount = 0;
        if ($this->ltypes[$lineNum][0] != Integer) {
            $this->error("Invalid line number [" . $this->lexems[$lineNum][0] . "] ");
        }


        if ($this->ltypes[$lineNum][1] == Variable) {
            if (!in_array($this->lexems[$lineNum][2], $this->ops)) {
                $this->error("Error function [" . $this->lexems[$lineNum][1] . "] on line " . $this->lexems[$lineNum][0]);
            }
        }
        if (count($this->lexems[$lineNum][1]) == 0)
            $this->error("Error at line[" . $this->lexems[$lineNum][0] . "] ");
        for ($i = 0; $i < count($this->lexems[$lineNum]); $i++) {
            if ($this->lexems[$lineNum][$i] == "(")
                $bracketCount++;
            if ($this->lexems[$lineNum][$i] == ")")
                $bracketCount--;
        }
        for ($ii = 0; $ii < count($this->lexems[$lineNum]); $ii++) {
            $i = $ii;

            //echo $this->lexems[$lineNum][$i]." (".$this->ltypes[$lineNum][$i].") ";

            if ($this->lexems[$lineNum][$ii] == ">" && $this->lexems[$lineNum][$ii + 1] == "=") {
                $this->lexems[$lineNum][$ii] = '>=';
                $this->lexems[$lineNum][$ii + 1] = '';
                $this->ltype[$lineNum][$ii + 1] = '';
            } elseif ($this->lexems[$lineNum][$ii] == "<" && $this->lexems[$lineNum][$ii + 1] == "=") {
                $this->lexems[$lineNum][$ii] = '<=';
                $this->lexems[$lineNum][$ii + 1] = '';
                $this->ltype[$lineNum][$ii + 1] = '';
            } elseif ($this->lexems[$lineNum][$ii] == "<" && $this->lexems[$lineNum][$ii + 1] == ">") {
                $this->lexems[$lineNum][$ii] = '<>';
                $this->lexems[$lineNum][$ii + 1] = '';
                $this->ltype[$lineNum][$ii + 1] = '';
            }

            if ($this->ltypes[$lineNum][$i] == Variable) {
                if ($this->lexems[$lineNum][$i - 1] == '$' || $this->lexems[$lineNum][$i - 1] == '%')
                    $this->error("Uncorrect variable name [" . $this->lexems[$lineNum][$i] . "] on line " . $this->lexems[$lineNum][0]);;
            }

            if ($this->lexems[$lineNum][$i] == "=") {
                if (count($this->lexems[$lineNum][$i + 1]) < 1)
                    $this->error("Null after [" . $this->lexems[$lineNum][$i] . "] on line " . $this->lexems[$lineNum][0]);
            }
            if ($this->ltypes[$lineNum][$i] == Operator) {
                if ($this->ver == 1 && in_array($this->lexems[$lineNum][$i], $this->func191)) {
                    if ($this->lexems[$lineNum][$i] != "REPAINT")
                        $this->error("Operator [" . $this->lexems[$lineNum][$i] . "] not support in MobileBASIC 1.8.6 on line " . $this->lexems[$lineNum][0]); else {
                        $this->warn("Operator [REPAINT] not support in MobileBASIC 1.8.6, this operator will removed from code. Select 1.9.1 version of basic to use operator REPAINT");
                        if ($this->lexems[$lineNum][$i - 1] == ":") {
                            $this->lexems[$lineNum][$i] = "";
                            $this->lexems[$lineNum][$i - 1] = "";
                        } elseif ($this->lexems[$lineNum][$i + 1] = ":") {
                            $this->lexems[$lineNum][$i] = "";
                            $this->lexems[$lineNum][$i + 1] = "";
                        }
                        else
                            $this->lexems[$lineNum] = "";
                    }
                } else

                if (in_array($this->lexems[$lineNum][$i], $this->keywords)) {
                    if (!($i == count($this->lexems[$lineNum]) - 1 || $this->lexems[$lineNum][$i + 1] == ":"))
                        $this->error("Operator must have 0 arguments [" . $this->lexems[$lineNum][$i] . "] on line " . $this->lexems[$lineNum][0]);
                }
                elseif (in_array($this->lexems[$lineNum][$i], $this->functions)) {
                    if ($i == count($this->lexems[$lineNum]) - 1 || $this->lexems[$lineNum][$i + 1] == ":" || $this->lexems[$lineNum][$i + 1] != "(" && $this->lexems[$lineNum][$i + 1] != ":" && count($this->lexems[$lineNum][$i + 1]) < 1)
                        $this->error("Error function [" . $this->lexems[$lineNum][$i] . "] on line " . $this->lexems[$lineNum][0]);
                }
                elseif (!in_array($this->lexems[$lineNum][$i], $this->functions) && count($this->lexems[$lineNum][$i]) > 1) {
                    if ($i == count($this->lexems[$lineNum]) - 1 || $this->lexems[$lineNum][$i + 1] == ":") {
                        $this->error("Operator must have arguments [" . $this->lexems[$lineNum][$i] . "] on line " . $this->lexems[$lineNum][0]);
                    } elseif ($this->lexems[$lineNum][$i + 1] == "(" && $this->lexems[$lineNum][$i] != "TO" && $this->lexems[$lineNum][$i] != "STEP") {
                        $this->error("Not an operator [" . $this->lexems[$lineNum][$i] . "] on line " . $this->lexems[$lineNum][0]);
                    }
                }
                if ($this->lexems[$lineNum][$i] == "DIM") {
                    array_splice($this->lexems[$lineNum], $i + 2, 0, "");
                    array_splice($this->ltypes[$lineNum], $i + 2, 0, ArrayByte);
                }
            } elseif ($this->ltypes[$lineNum][$i] == Variable && $this->lexems[$lineNum][$i + 1] == "(") {
                array_splice($this->lexems[$lineNum], $i + 1, 0, "");
                array_splice($this->ltypes[$lineNum], $i + 1, 0, ArrayByte);
            }
        }

        if ($bracketCount != 0) {
            $this->error("Error: Some braket not open/close on line " . $this->lexems[$lineNum][0]);
            return;
        }
    }

    function analizeLine($line) {
        $i = 0;
        while ($i < mb_strlen($line)) {
            $i += $this->readToken($line, $i);
            if ($this->coderror != "")
                break;
        }
    }

    function readToken($line, $startPos) {
        $initPos = $startPos;
        $op = "";
        if (mb_substr($line, $startPos, 1) >= 'A' && mb_substr($line, $startPos, 1) <= 'Z' || mb_substr($line, $startPos, 1) >= 'a' && mb_substr($line, $startPos, 1) <= 'z') {
            while (mb_substr($line, $startPos, 1) >= 'A' && mb_substr($line, $startPos, 1) <= 'Z' || mb_substr($line, $startPos, 1) >= 'a' && mb_substr($line, $startPos, 1) <= 'z' || mb_substr($line, $startPos, 1) >= '0' && mb_substr($line, $startPos, 1) <= '9' || mb_substr($line, $startPos, 1) == '_' || mb_substr($line, $startPos, 1) == '$' || mb_substr($line, $startPos, 1) == '%') {
                $op .= mb_substr($line, $startPos, 1);
                $startPos++;
                if ($startPos >= mb_strlen($line))
                    break;
            }
            $op = mb_strtoupper($op);
            $this->lexems[$this->currLine][] = $op;
            // echo "лан, не урчи";
            $isData = false;
            $data = "";
            if ($op == "DATA" || $op == "REM") {
                $isData = true;
                $startPos++;
                while ($startPos < mb_strlen($line)) {
                    $data .= mb_substr($line, $startPos, 1);
                    $startPos++;
                }
                $this->lexems[$this->currLine][] = $data;
            }
            if (!in_array($op, $this->ops)) {
                if (!in_array($op, $this->vars))
                    $this->vars[] = $op;
                $this->ltypes[$this->currLine][] = Variable;
            }
            else {
                $this->ltypes[$this->currLine][] = Operator;
                if ($isData) {
                    $this->ltypes[$this->currLine][] = Data;
                }
            }
        }
        //STRING
        elseif (mb_substr($line, $startPos, 1) == '"') {
            $startPos++;
            while (!(mb_substr($line, $startPos, 1) == '"') || (mb_substr($line, $startPos - 1, 1) == '\\')) {
                if ((mb_substr($line, $startPos, 1)) != '\\')
                    $op .= mb_substr($line, $startPos, 1);
                $startPos++;
                if ($startPos >= mb_strlen($line)) {
                    $this->coderror = "Error complie: Not found close \" on line " . $this->lexems[$this->currLine][0];
                    break;
                }
            }
            $startPos++;
            $this->lexems[$this->currLine][] = '"' . $op . '"';
            $this->ltypes[$this->currLine][] = String;
        }
        // NUMBER
        elseif (mb_substr($line, $startPos, 1) >= '0' && mb_substr($line, $startPos, 1) <= '9') {
            while (mb_substr($line, $startPos, 1) >= '0' && mb_substr($line, $startPos, 1) <= '9' || mb_substr($line, $startPos, 1) == '.' || mb_substr($line, $startPos, 1) == 'e' || mb_substr($line, $startPos, 1) == 'E') {
                $op .= mb_substr($line, $startPos, 1);
                if (mb_substr($line, $startPos, 1) == 'e' || mb_substr($line, $startPos, 1) == 'E') {
                    $op .= mb_substr($line, $startPos + 1, 1);
                    $startPos += 2;
                }
                else
                    $startPos++;
                if ($startPos >= mb_strlen($line))
                    break;
            }
            $containPoint = false;
            $error = false;
            for ($i = 0; $i < mb_strlen($op); $i++) {
                if ($op[$i] == '.') {
                    if (!$containPoint)
                        $containPoint = true;
                    else {
                        // exit;
                        $this->coderror = "NumberLexem contains more than one point [" . $op . "]";
                        return;
                    }
                }
            }
            if (!$error) {
                $this->lexems[$this->currLine][] = $op;
                if ($containPoint) {
                    $this->ltypes[$this->currLine][] = Float;
                    if (!in_array($op, $this->float)) {
                        $this->float[] = $op;
                    }
                }
                else
                    $this->ltypes[$this->currLine][] = Integer;
            }
        }
        ///SYMBOL
        else {
            if (mb_substr($line, $startPos, 1) != ' ' && mb_substr($line, $startPos, 1) < 'А') {
                $this->lexems[$this->currLine][] = mb_substr($line, $startPos, 1);
                $this->ltypes[$this->currLine][] = Operator;
                $startPos++;
            } elseif (mb_substr($line, $startPos, 1) >= 'А' && mb_substr($line, $startPos, 1) <= 'я') {
                $this->coderror = "SymbolLexem not recognized [" . $line[$startPos] . "]";
                return;
            }
            else
                $startPos++;
        }
        return $startPos - $initPos;
    }

    function compileHead() {
        // print_r($this->float);
        $buf = "";
        if ($this->ver == 1)
            $buf .= pack('H*', "4d420001");
        else
            $buf .= pack('H*', "4d420191");
        $buf .= pack('n*', count($this->vars));
        for ($i = 0; $i < count($this->vars); $i++) {
            $buf .= $this->writeUTF($this->vars[$i]);
            $type = substr($this->vars[$i], -1);
            //echo $this->vars[$i].' - '.$type;
            switch ($type) {
                case '$':
                    $buf .= pack('H*', "02");
                    break;
                default:
                    $buf .= pack('H*', "01");
                    break;
                case '%':
                    $buf .= pack('H*', "00");
                    break;
            }
        }

        if ($this->ver == 2) { ///write float MB 181
            $buf .= pack('n*', count($this->float));
            for ($i = 0; $i < count($this->float); $i++) {
                $buf.=strrev(pack('f', $this->float[$i]));
            }
        }
        $buf .= pack('n*', strlen($this->bufbody));
        $this->buffer = $buf . $this->bufbody;
    }

    function compileCode() {
        $lexems = $this->lexems;
        $ltypes = $this->ltypes;
        for ($currLine = 0; $currLine < count($lexems); $currLine++) {
            $line = "";
            $notnull = false;
            for ($currLex = 1; $currLex < count($lexems[$currLine]); $currLex++) {
                if ($lexems[$currLine][$currLex] != "") {
                    $notnull = true;
                    break;
                }
            }
            if ($notnull) {
                $buf .= pack('n*', $lexems[$currLine][0]);
                for ($currLex = 1; $currLex < count($lexems[$currLine]); $currLex++) {
                    /////Operator
                    if ($ltypes[$currLine][$currLex] == Operator) {
                        if (trim($lexems[$currLine][$currLex]) == "IF") {
                            $ifStarted = true;
                        } elseif (trim($lexems[$currLine][$currLex]) == "THEN") {
                            $ifStarted = false;
                        } elseif (trim($lexems[$currLine][$currLex]) == "FOR") {
                            $forStarted = true;
                        }
                        for ($i = 0; $i < count($this->ops); $i++) {
                            if (trim($lexems[$currLine][$currLex]) == $this->ops[$i]) {
                                if (trim($lexems[$currLine][$currLex]) == "=") {
                                    if ($ifStarted) {
                                        $i = "33";
                                    } else if ($forStarted) {
                                        $i = "7b";
                                        $forStarted = false;
                                    } else {
                                        $i = "f6";
                                    }
                                } else {
                                    $i = dechex($i);
                                    if (strlen($i) < 2)
                                        $i = "0" . $i;
                                }
                                if (mb_strtoupper($i) == "3D") {
                                    if ($ltypes[$currLine][$currLex - 1] != Integer && $ltypes[$currLine][$currLex - 1] != Float && $ltypes[$currLine][$currLex - 1] != Variable && $lexems[$currLine][$currLex - 1] != ")")
                                        $i = "3e";
                                }

                                $line .= pack('H*', $i);
                                break;
                            }
                        }
                    }
                    /////Variable
                    if ($ltypes[$currLine][$currLex] == Variable) {
                        for ($i = 0; $i < count($this->vars); $i++) {
                            if (trim($lexems[$currLine][$currLex]) == trim($this->vars[$i])) {
                                $line .= pack('H*', "FC");
                                $i = dechex($i);
                                //echo $i;
                                if (strlen($i) < 2)
                                    $i = "0" . $i;
                                $line .= pack('H*', $i);
                                break;
                            }
                        }
                    }
                    if ($ltypes[$currLine][$currLex] == ArrayByte) {
                        $line .= pack('H*', "F7");
                    }
                    if ($ltypes[$currLine][$currLex] == Integer) {
                        if (strlen($lexems[$currLine][$currLex]) > 5)
                            $ltypes[$currLine][$currLex] = Float;
                        else {
                            $val = intval($lexems[$currLine][$currLex]);
                            if ($val <= 127) {
                                $line .= pack('H*', "F8");
                                $i = dechex($val);
                                if (strlen($i) < 2)
                                    $i = "0" . $i;
                                $line .= pack('H*', $i);
                            }
                            elseif ($val >= 128 && $val <= 255) {
                                $line .= pack('H*', "F9");
                                $i = dechex($val);
                                if (strlen($i) < 2)
                                    $i = "0" . $i;
                                $line .= pack('H*', $i);
                            }
                            elseif ($val >= 256 && $val < 65536) {
                                $line .= pack('H*', "FA");
                                $line .= pack('n*', $val);
                            } else {
                                $line .= pack('H*', "FB");
                                $line .= pack('c', $this->shiftRight($val, 24) & 0xFF);
                                $line .= pack('c', $this->shiftRight($val, 16) & 0xFF);
                                $line .= pack('c', $this->shiftRight($val, 8) & 0xFF);
                                $line .= pack('c', $this->shiftRight($val, 0) & 0xFF);
                            }
                        }
                    }
                    /////
                    if ($ltypes[$currLine][$currLex] == String) {
                        $text = mb_substr($lexems[$currLine][$currLex], 1, mb_strlen($lexems[$currLine][$currLex]) - 2);
                        if (preg_match('//u', $text))
                            $text = iconv("UTF-8", "WINDOWS-1251", $text);
                        $line .= pack('H*', "FD");
                        $line .= $this->writeTEXT($text);
                    }
                    if ($ltypes[$currLine][$currLex] == Data) {
                        $text = $lexems[$currLine][$currLex];
                        if (preg_match('//u', $text))
                            $text = iconv("UTF-8", "WINDOWS-1251", $text);
                        $line .= $this->writeTEXT($text);
                    }
                    if ($ltypes[$currLine][$currLex] == Float) {
                        if ($this->ver == 1) {
                            $line .= pack('H*', "FE");
                            $exp = 0x80;
                            $num = doubleval($lexems[$currLine][$currLex]);
                            if ($num < 1) {
                                while ($num < 1) {
                                    $num = $num * 10;
                                    $exp--;
                                }
                            } else if ($num >= 10) {
                                while ($num >= 10) {
                                    $num = $num / 10;
                                    $exp++;
                                }
                            }
                            $radix = ($num * 500000);
                            $line .= pack('c', $this->shiftRight($radix, 16) & 0xFF);
                            $line .= pack('c', $this->shiftRight($radix, 8) & 0xFF);
                            $line .= pack('c', $this->shiftRight($radix, 0) & 0xFF);
                            $line .= pack("c", $exp);
                        } else {
                            //echo $lexems[$currLine][$currLex].' ';
                            ///write float number
                            for ($i = 0; $i < count($this->float); $i++) {
                                if (trim($lexems[$currLine][$currLex]) == trim($this->float[$i])) {
                                    //echo $lexems[$currLine][$currLex];
                                    //echo trim($this->float[$i]);
                                    $line .= pack('H*', "FE");
                                    $i = dechex($i);
                                    //echo $i;
                                    if (strlen($i) < 2)
                                        $i = "0" . $i;
                                    //echo $i.' ';
                                    $line .= pack('H*', $i);
                                    break;
                                }
                            }
                        }
                    }
                }
                $len = dechex(strlen($line) + 4);
                if (mb_strlen($len) < 2)
                    $len = "0" . $len;
                $buf .= pack('H*', $len) . $line . pack('H*', 'FF');
            }
        }
        $this->bufbody = $buf;
        $this->compileHead();
    }

    function error($text) {
        if (empty($this->coderror))
            $this->coderror = $text;
    }

    function warn($text) {
        if (empty($this->warning))
            $this->warning = $text;
    }

    function writeUTF($text) {
        $pack = pack('n*', mb_strlen($text));
        $pack .= pack('a*', $text);
        return $pack;
    }

    function writeTEXT($text) {
        $len = dechex(strlen($text));
        if (strlen($len) < 2)
            $len = "0" . $len;
        $pack = pack('H*', $len);
        $pack .= pack('a*', $text);
        return $pack;
    }

    function shiftRight($a, $b) {
        if (is_numeric($a) && $a < 0) {
            return ($a >> $b) + (2 << ~$b);
        } else {
            return ($a >> $b);
        }
    }

    function endWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

}

?>