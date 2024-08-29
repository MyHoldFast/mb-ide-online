<?

/*
 * ****************************************************************
  CREATE BY HoldFast
  Андрей Рейгант
  http://vk.com/holdfast
  MBTEAM.RU
 * ****************************************************************
  Коды ответов:
  -1: неверный BAS-файл
  -2: файл нельзя декмопилировать, поскольку он обфусцирован
  0: файл успешно обфусцирован
 * ****************************************************************
  Пример:
  -----------------------------------------------------------------
  $bas = new BAS('Autorun.bas'); ///Открываем BAS
  $code = $bas->decompile(); //$code - декомпилированный код BAS
  echo $code;
  -----------------------------------------------------------------
  $bas->obfuscation('newbas.bas'); //обфусцировать открытый BAS, и сохранить
  его с именем newbas.bas
 */

class BAS {

    public $file;
    public $data;

    function BAS($files) {
        $this->file = $files;
        $this->init();
    }

    function init() {
        $this->data = file_get_contents($this->file);
    }

    function decompile() {
        $ops = array(
            " STOP ", " POP ", " RETURN ", " END ", " NEW ", " RUN ", " DIR ", " DEG ", " RAD ", " BYE ", " GOTO ",
            " GOSUB ", " SLEEP ", " PRINT ", " REM ", " DIM ", " IF ", " THEN ", " CLS ", " PLOT ", " DRAWLINE ",
            " FILLRECT ", " DRAWRECT ", " FILLROUNDRECT ", " DRAWROUNDRECT ", " FILLARC ",
            " DRAWARC ", " DRAWSTRING ", " SETCOLOR ", " BLIT ", " FOR ", " TO ", " STEP ", " NEXT ", " INPUT ",
            " LIST ", " ENTER ", " LOAD ", " SAVE ", " DELETE ", " EDIT ", " TRAP ", " OPEN ", " CLOSE ",
            " NOTE ", " POINT ", " PUT ", " GET ", " DATA ", " RESTORE ", " READ ", "=", "<>", "<", "<=",
            ">", ">=", "(", ")", ",", "+", "-", "-", "*", "/", "^", " BITAND ", " BITOR ", " BITXOR ", " NOT ",
            " AND ", " OR ", "SCREENWIDTH", "SCREENHEIGHT", " ISCOLOR ", " NUMCOLORS ", "STRINGWIDTH", "STRINGHEIGHT",
            "LEFT$", "MID$", "RIGHT$", "CHR$", "STR$", "LEN", "ASC", "VAL", " UP ", " DOWN ", " LEFT ", " RIGHT ",
            " FIRE ", " GAMEA ", " GAMEB ", " GAMEC ", " GAMED ", " DAYS ", " MILLISECONDS ",
            " YEAR ", " MONTH ", " DAY ", " HOUR ", " MINUTE ", " SECOND ", " MILLISECOND ", "RND", " ERR ",
            " FRE ", "MOD", "EDITFORM ", "GAUGEFORM ", "CHOICEFORM", "DATEFORM", "MESSAGEFORM",
            "LOG", "EXP", "SQR", "SIN", "COS", "TAN", "ASIN", "ACOS", "ATAN", "ABS", "=", "#", " PRINT ",
            " INPUT ", ":", " GELGRAB ", " DRAWGEL ", " SPRITEGEL ", " SPRITEMOVE ", " SPRITEHIT ",
            "READDIR$", "PROPERTY$", " GELLOAD ", " GELWIDTH", " GELHEIGHT", " PLAYWAV ", " PLAYTONE ",
            " INKEY", "SELECT", "ALERT ", " SETFONT ", " MENUADD ", " MENUITEM", " MENUREMOVE ",
            " CALL ", " ENDSUB ", " REPAINT", "SENDSMS ", " RAND", " ALPHAGEL ", " COLORALPHAGEL ", " PLATFORMREQUEST ", " DELGE L", " DELSPRITE ", "MKDIR"
        );
        ///version of BAS
        switch ($this->readInt($this->data)) {
            case '4d420001':
                $version = 1;
                break;
            case '4d420191':
                $version = 2;
                break;
            default:
                $version = 0;
                break;
        }
        if ($version == 0) {
            return -1;
        } else {
            $isDIM = false;
            $varnum = hexdec($this->readShort($this->data)); ///number variable
            for ($i = 0; $i < $varnum; $i++) { //read variable name
                $num = hexdec($this->readShort($this->data)); ///name length
                for ($ii = 0; $ii < $num; $ii++) {
                    $varname[$i] .= substr($this->data, 0, 1);
                    $this->readByte($this->data);
                }
                /// end read variable name
                $this->readByte($this->data); // variable type
            }
            //float  
            if ($version == 2) {
                $floatnum = hexdec($this->readShort($this->data));
                for ($i = 0; $i < $floatnum; $i++) {
                    $byte[3] = $this->readByte($this->data, false);
                    $byte[2] = $this->readByte($this->data, false);
                    $byte[1] = $this->readByte($this->data, false);
                    $byte[0] = $this->readByte($this->data, false);
                    $tfloat = unpack('f', $byte[0] . $byte[1] . $byte[2] . $byte[3]); //uncpack float
                    $float[$i] = round($tfloat[1], 7);
                }
            }

            $codeln = hexdec($this->readShort($this->data));
            if ($codeln != strlen($this->data)) {
                $this->init();
                return -2;
            }

            ///read code
            while (strlen($this->data) > 0) {
                $line = "";
                $line .= hexdec($this->readShort($this->data)); //write number of line
                $lineS = hexdec($this->readByte($this->data)); //length line
                $isl = 1;
                unset($lims);
                for ($ii = 0; $ii < $lineS - 4; $ii++) { ///read operators
                    $lims[$ii] = $this->readByte($this->data);
                }
                $cur = 0; //position of read
                while ($cur < count($lims)) { //decompilation start
                    $opType = $lims[$cur];
                    $cur++;
                    if (hexdec($opType) == 0xfc) { /// variable 
                        $varNum = hexdec($lims[$cur]);
                        if (hexdec($lims[$cur - 2]) == 16)
                            $line .= "";
                        if ($isl == 1) {
                            $line .= " " . $varname[$varNum];
                        }
                        else
                            $line .= $varname[$varNum];
                        $cur++;
                    }
                    else { ///operator
                        $isl = 0;
                        switch (hexdec($opType)) {
                            case 0x0e:
                                $line .= " REM ";
                                $str = hexdec($lims[$cur]); /// len
                                $cur++;
                                for ($i = 0; $i < $str; $i++) {
                                    $line .= iconv("WINDOWS-1251", "UTF-8", chr(hexdec($lims[$cur])));
                                    $cur++;
                                }
                                //$line .= "";
                                break;
                            case 0xfd:
                                $line .= "\"";
                                $str = hexdec($lims[$cur]);
                                $cur++;
                                for ($i = 0; $i < $str; $i++) {
                                    $line .= str_replace('"', '\"', iconv("WINDOWS-1251", "UTF-8", chr(hexdec($lims[$cur]))));
                                    $cur++;
                                }
                                $line .= "\"";
                                break;
                            case 0x30:
                                $line .= " DATA ";
                                $str = hexdec($lims[$cur]);
                                $cur++;
                                for ($i = 0; $i < $str; $i++) {
                                    $line .= iconv("WINDOWS-1251", "UTF-8", chr(hexdec($lims[$cur])));
                                    $cur++;
                                }
                                break;
                            case 0xf6:
                                $line .= "=";
                                break;
                            case 0xf8:
                                $line .= hexdec($lims[$cur]);
                                $cur++;
                                break;
                            case 0xf7:
                                //$line .= "(";
                                continue;
                            case 0xf9:
                                $line .= hexdec($lims[$cur]);
                                $cur++;
                                break;
                            case 0xfa:
                                $line .= (hexdec($lims[$cur]) * 256 + hexdec($lims[$cur + 1]));
                                $cur = $cur + 2;
                                break;
                            case 0xfb:
                                $line .= (hexdec($lims[$cur]) * 16777216 + hexdec($lims[$cur + 1]) * 65536 + hexdec($lims[$cur + 2]) * 256 + hexdec($lims[$cur + 3]));
                                $cur = $cur + 4;
                                break;
                            case 0xfe: //float
                                if ($version == 1) {
                                    (int) $exp = $this->tosbyte(hexdec($lims[$cur + 3]));
                                    $m = (hexdec($lims[$cur]) * 65536 + hexdec($lims[$cur + 1]) * 256 + hexdec($lims[$cur + 2])) / 500000;
                                    $e = 1;
                                    if ($exp > 0) {
                                        $d = 1;
                                        for ($i = 0; $i < $exp; $i++)
                                            $d = $d * 10;
                                        $e = $d;
                                    }
                                    if ($exp < 0) {
                                        $d = 1;
                                        for ($i = $exp; $i < 0; $i++)
                                            $d = $d / 10;
                                        $e = $d;
                                    }
                                    $line .= (float) ($m * $e);
                                    $cur = $cur + 4;
                                } else {
                                    $line .= $float[hexdec($lims[$cur])];
                                    $cur++;
                                }

                                break;
                            default:
                                $line .= $ops[hexdec($opType)];
                                if ($opType == 15 || $ops[$opType] == " READ ") {
                                    $isDIM = true;
                                }
                                break;
                        }
                    }
                }
                $this->readByte($this->data);
                $line .= "\r\n";
                $main .= $line;
            }
            $this->init();
            return $main;
        }
    }

    function obfuscation($name) {
        switch ($this->readInt($this->data)) {
            case '4d420001':
                $version = 1;
                break;
            case '4d420191':
                $version = 2;
                break;
            default:
                $version = 0;
                break;
        }
        if ($version == 0) {
            return -1;
        } else {
            $main = "";
            if ($version == 1)
                $main .= pack('H*', "4d420001"); //writeHex
            else
                $main .= pack('H*', "4d420191");
            $varnum = hexdec($this->readShort($this->data));
            $main .= pack('n*', $varnum); //writeShort
            for ($i = 0; $i < $varnum; $i++) { //пропуск переменных
                $this->readUTF($this->data);
                $main .= $this->writeUTF("  ");
                $main .= pack('H*', $this->readByte($this->data));
            }

            if ($version == 2) { //пропуск float для MB191
                $floatnum = hexdec($this->readShort($this->data));
                $main .= pack('n*', $float);
                for ($i = 0; $i < $varnum; $i++) {
                    $main .= pack('H*', $this->readInt($this->data));
                }
            }


            $leng = hexdec($this->readShort($this->data));
            if ($version == 1)
                $main .= pack('n*', $leng + 7);
            else
                $main .= pack('n*', $leng + 1);

            for ($i = 0; $i < $leng; $i++) {
                $main .= pack('H*', $this->readByte($this->data));
            }
            $main .= pack('H*', "FFFF");
            file_put_contents($name, $main);
        }
        $this->init();
        return 0;
    }

    function tosbyte($byte) {
        if (0 <= $byte && $byte <= 63)
            return (int) $byte;
        if (64 <= $byte && $byte <= 191)
            return (int) (-(128 - $byte));
        if (192 <= $byte && $byte <= 255)
            return (int) (-(256 - $byte));
        return 0;
    }

    function readInt($text) {
        $this->data = substr($text, 4, strlen($text));
        return bin2hex(substr($text, 0, 4));
    }

    function readShort($text) {
        $this->data = substr($text, 2, strlen($text));
        return bin2hex(substr($text, 0, 2));
    }

    function readByte($text, $dec = true) {
        $this->data = substr($text, 1, strlen($text));
        if ($dec)
            return bin2hex(substr($text, 0, 1));
        else
            return substr($text, 0, 1);
    }

    function readUTF() {
        $to = hexdec($this->readShort($this->data));
        while ($to <> 0) {
            $bombom .= iconv("WINDOWS-1251", "UTF-8", chr(hexdec($this->readByte($this->data))));
            $to--;
        }
        return $bombom;
    }

    function writeUTF($text) {
        $pack = pack('n*', mb_strlen($text));
        $pack .= pack('a*', $text);
        return $pack;
    }

}

?>