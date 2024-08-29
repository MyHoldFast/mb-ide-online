<?

/* * ***********************************************************
  |    (с) Андрей Рейгант
  |    http://vk.com/holdfast
  |    http://mbteam.ru
 * *********************************************************** */

require "bas.class.php";
require "lis.class.php";
require "lang.php";

session_start();
error_reporting(0);

$lng = $_SESSION['lng'];
if (empty($lng))
    $lng = 'ru';

switch (intval($_POST['ver'])) {
    case 1:
        $version = 1;
        break;
    case 2:
        $version = 2;
        break;
    default:
        $version = 1;
        break;
}

$ext = array('.mp3', '.mid', '.midi', '.png', '.jpg', '.png', '.bmp', '.gif', '.wav', '.txt', '.lis');

function removeDirectory($dir) {
    if ($objs = glob($dir . "/*")) {
        foreach ($objs as $obj) {
            $expire_time = 3600;
            $time_sec = time();
            $time_file = filemtime($obj);
            $time = $time_sec - $time_file;
            if ($time > $expire_time)
                is_dir($obj) ? removeDirectory($obj) : unlink($obj);
        }
    }
    rmdir($dir);
}

removeDirectory('src');
removeDirectory('temp');
removeDirectory('jar');
removeDirectory('res');

if (!is_dir('src')) {
    mkdir('src');
}
if (!is_dir('temp')) {
    mkdir('temp');
}
if (!is_dir('jar')) {
    mkdir('jar');
}


if ($_POST['act'] == 'compile') {

    $code = trim($_POST['code']);
    if (!empty($code)) {
        $name = time() . rand(1000, 9999);
        //file_put_contents('temp/'.$name.'.lis', $code);
        $lis = new LIS($code, 'src/' . $name . '.bas', $version);

        $out = $lis->compile();
        if ($out[0] == "error") {
            echo "{\"error\": " . json_encode($out[1]) . "}";
            exit;
        } elseif ($out[0] == "warning") {
            $warning = json_encode($out[1]);
        }

        if ($_POST['obf'] == 'on') {
            $bas = new BAS('src/' . $name . '.bas');
            $bas->obfuscation('src/' . $name . '.bas');
        }
        echo "{\"bas\": \"$name\"" . (($warning != "") ? ',"warning":' . $warning . '}' : '}');
    }
} elseif ($_POST['act'] == 'build') {

    $code = json_decode($_POST['json']);
    $name = time() . rand(1000, 9999);
    if (!empty($code)) {
        mkdir('src/' . $name);
        for ($i = 0; $i < count($code->file); $i++) {
            //echo urldecode($code->file[$i]->code);//."\n";
            $lis = new LIS(urldecode($code->file[$i]->code), 'src/' . $name . '/' . substr($code->file[$i]->name, 0, -4) . '.bas', $version);
            $out = $lis->compile();
            if ($out[0] == "error") {
                $out = 'File ' . $code->file[$i]->name . ': ' . $out[1];
                echo "{\"error\": " . json_encode($out) . "}";
                exit;
            } elseif ($out[0] == "warning") {
                $warning = json_encode($out[1]);
            }
            if ($_POST['obf'] == 'on') {
                $bas = new BAS('src/' . $name . '/' . substr($code->file[$i]->name, 0, -4) . '.bas');
                $out = $bas->obfuscation('src/' . $name . '/' . substr($code->file[$i]->name, 0, -4) . '.bas');
            }
        }

        require "pclzip.lib.php";
        mkdir('jar/' . $name);
        mkdir('temp/' . $name);
        mkdir('temp/' . $name . "/META-INF");
        $mname = (!empty($_POST['midletname'])) ? mb_substr($_POST['midletname'], 0, 50) : 'NewFile';
        $mvendor = (!empty($_POST['midletvendor'])) ? mb_substr($_POST['midletvendor'], 0, 50) : 'mbteam.ru';

        if ($version == 1)
            $manifest = "Manifest-Version: 1.0
Created-By: Online MobileBASIC IDE (http://mbteam.ru)
MIDlet-1: " . $mname . ",,cpu
MIDlet-Vendor: " . $mvendor . "
MIDlet-Version: 1.0.0
MIDlet-Name: " . $mname . "
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-2.0";
        else
            $manifest = "Manifest-Version: 1.0
Created-By: Online MobileBASIC IDE (http://mbteam.ru)
MIDlet-1: " . $mname . ",,Main
MIDlet-Vendor: " . $mvendor . "
MIDlet-Version: 1.0.0
MIDlet-Name: " . $mname . "
MicroEdition-Configuration: CLDC-1.1
MicroEdition-Profile: MIDP-2.0
FullScreenMode: true
";


        file_put_contents("temp/" . $name . "/META-INF/MANIFEST.MF", iconv("UTF-8", "WINDOWS-1251", $manifest));


        if ($version == 1)
            copy('basic.jar', 'jar/' . $name . '/BASIC.jar');
        else
            copy('basic191.jar', 'jar/' . $name . '/BASIC.jar');
        $archive = new PclZip('jar/' . $name . '/BASIC.jar');
        $archive->add('src/' . $name, PCLZIP_OPT_REMOVE_PATH, 'src/' . $name);
        $archive->add('temp/' . $name, PCLZIP_OPT_REMOVE_PATH, 'temp/' . $name);
        if (!empty($_COOKIE['restmp']))
            $archive->add('res/' . $_COOKIE['restmp'], PCLZIP_OPT_REMOVE_PATH, 'res/' . $_COOKIE['restmp']);
        echo "{\"jar\": \"$name\"" . (($warning != "") ? ',"warning":' . $warning . '}' : '}');
    }
    else
        echo "{\"error\": " . json_encode($console[$lng]['codeempty']) . "}";
}


elseif ($_GET['act'] == 'upload') {

    if (is_uploaded_file($_FILES["bas"]["tmp_name"]) && $_FILES["bas"]["type"] == 'application/octet-stream') {
        $name = time() . rand(1000, 9999);
        move_uploaded_file($_FILES["bas"]["tmp_name"], "temp/" . $name . '.bas');
        $bas = new BAS("temp/" . $name . '.bas');
        $out = $bas->decompile();
        if ($out != -1 && $out != -2) {
            echo "{\"lis\":" . json_encode($out) . "}";
        }
        else
            echo '{"error":"<b>' . $console[$lng]['cantbas'] . '</b>"}';
    }
    else
        echo '{"error":"<b>' . $console[$lng]['cantfile'] . '</b>"}';
}
elseif ($_GET['act'] == 'add') {

    if ($_COOKIE['restmp'] == '') {
        $name = time() . rand(1000, 9999);
        //$_SESSION['restmp'] = $name;
        setcookie('restmp', $name);
        mkdir('res/' . $name);
    } elseif (is_dir('res/' . $_COOKIE['restmp']))
        $name = $_COOKIE['restmp'];
    else {
        $name = time() . rand(1000, 9999);
        //$_SESSION['restmp'] = $name;
        setcookie('restmp', $name);
        mkdir('res/' . $name);
    }


    if (is_uploaded_file($_FILES["res"]["tmp_name"])) {
        $fname = mb_strtolower($_FILES["res"]["name"]);

        $type = strrchr($fname, '.');

        if (in_array($type, $ext)) {
            $fname = substr($fname, 0, strrpos($fname, '.'));
            if (!preg_match('//u', $fname)) {
                echo "{error}";
                exit;
            }
            $fname = substr($fname, 0, 15) . $type;

            if (!file_exists("res/" . $name . '/' . $fname)) {
                move_uploaded_file($_FILES["res"]["tmp_name"], "res/" . $name . '/' . $fname);
                echo $fname;
            }
            else
                echo "{error}";
        }
    }
    else
        echo "{error}";
} elseif ($_POST['act'] == 'deletefile') {

    if (!empty($_COOKIE['restmp'])) {
        if (is_file('res/' . $_COOKIE['restmp'] . '/' . $_POST['file'])) {
            unlink('res/' . $_COOKIE['restmp'] . '/' . $_POST['file']);
            echo 'del';
        }
        else
            'error';
    }
    else
        'error';
}
else
    echo 'Пшёл нах';
?>