<?
/* * ***********************************************************
  |    (с) Андрей Рейгант
  |    http://vk.com/holdfast
  |    http://mbteam.ru
  |    PHP 5.3/5.6, mbstring, json modules
 * *********************************************************** */

require 'lang.php';
session_start();
//$_SESSION['restmp'] = '';

switch ($_GET['lang']) {
    case 'en':
        $lng = 'en';
        setcookie("lng", $lng, time() + 3600 * 24 * 30 * 12);
        break;
    case 'ru':
        $lng = 'ru';
        setcookie("lng", $lng, time() + 3600 * 24 * 30 * 12);
        break;

    default:
        $lng = $_COOKIE['lng'];
        if (empty($lng)) {
            $lng = 'ru';
            setcookie("lng", $lng, time() + 3600 * 24 * 30 * 12);
        }
        break;
}
?>

<!doctype html>
<html lang="<?=$lng?>">
    <!--Autor HoldFast->
    <!-http://vk.com/holdfast-->
    <!--All Rights Reserved-->
    <head>
        <meta charset="utf-8">
        <meta name="description" content="Онлайн компилятор MobileBASIC">
        <meta name="keywords" content="online mobile basic, online mobilebasic, онлайн бейсик, mobilebasic, basic ide, mbteam">
        <title>Online MobileBASIC IDE</title>
        <link rel="shortcut icon" href="favicon.png">
        <link rel="stylesheet" href="codemirror/codemirror.css">
        <script src="codemirror/codemirror.js"></script>
        <script src="codemirror/match-highlighter.js"></script>
        <script src="codemirror/basic.js"></script>
        <script src="codemirror/active-line.js"></script>
        <script src="jquery-1.8.3.min.js"></script>
        <script src="jquery.form.js"></script>
        <link rel="stylesheet" href="codemirror/eclipse.css">
        <link rel="stylesheet" href="theme/style.css?442">
        <script src="function.js?90"></script>
    </head>

    <body>

        <div id="lang">
            <a href="?lang=en"><img src="theme/en.png" alt="" title="English"></a>
            <a href="?lang=ru"><img src="theme/ru.png" alt="" title="Русский"></a>
        </div>

        <div id="preload" style="display: none;">
            <img src="theme/error.png" alt="">
            <img src="theme/delete.png" alt="">
            <img src="theme/success.png" alt="">
        </div>


        <div id="logo">Online MobileBASIC IDE</div>
        <div id="ide">
            <div class="panel">
                <button class="button" onClick="javascript:$('#openbas').slideToggle();">
                    <img src="theme/open.png" alt="Open BAS/LIS">&nbsp;<span class="button_text"><?= $lang[$lng]['openbas'] ?></span></button>
                <button class="button" onClick="compiler.compile();" title="F7">
                    <img src="theme/compile.png" alt="Create BAS">&nbsp;<span class="button_text"><?= $lang[$lng]['compile'] ?></span></button>
                <button class="button" onClick="compiler.build();" title="F8">
                    <img src="theme/build.png" alt="Build">&nbsp;<span class="button_text"><?= $lang[$lng]['build'] ?></span></button>
               <!-- <button class="button" onClick="compiler.build(compiler.run);" title="F9">
                    <img src="theme/run.png" alt="Run">&nbsp;<span class="button_text"><?= $lang[$lng]['run'] ?></button>-->
                <button class="button" onClick="javascript:setDialogOpen();">
                    <img src="theme/setting.png" alt="Compiler setting">&nbsp;<span class="button_text"><?= $lang[$lng]['setting'] ?></span></button></div>
            <div id="openbas">
                <form id="basform" method="post" action="ajax.php?act=upload">
                    <input type="file" name="bas">
                    <input type="submit" value="<?= $lang[$lng]['open'] ?>">
                </form>
            </div>

            <div id="overlay" style="display: none;"></div>
            <div id="overlay2" style="display: none;"><table style="width: 100%; height: 100%"><tbody><tr><td><div class="modal"><span id="dialogcontent"></span>

                                </div></td></tr></tbody></table></div>

            <table style="text-align: center; width: 100%;">
                <tbody>
                    <tr>
                        <td style="text-align: left; min-width: 200px; height: 100%; vertical-align: top;">
                            <div class="projectfiles">
                                <div class="title"><?= $lang[$lng]['source'] ?></div>
                                <div id="selectfile">

                                </div>


                                <div style="margin-bottom: 5px; text-align: center;">
                                    <div style="border-bottom: 1px dotted black; margin-top: 5px; margin-bottom: 5px;"></div>
                                    <button onClick="newBuf()" class="button"><img src="theme/page_add.png" alt="" title="Add source file"><span style="vertical-align: middle; font-size: 13px;"> <?= $lang[$lng]['addsource'] ?></span></button>
                                </div>

                                <div class="title"><?= $lang[$lng]['resource'] ?></div> 
                                <div id="resource">

                                </div>

                                <div style="margin-bottom: 5px; text-align: center;">                             
                                    <div style="border-bottom: 1px dotted black; margin-top: 5px; margin-bottom: 5px;"></div>
                                    <button id="add" onClick="javascript:addDialogOpen();" class="button"><img src="theme/add.png" alt="" title="Add resource"><span style="vertical-align: middle; font-size: 13px;"> <?= $lang[$lng]['addresource'] ?></span></button>
                                </div>

                                <div class="title">MANIFEST</div> 
                                <div id="manifest">
                                    MIDlet-Name:<br>
                                    <input type="text" id="midletname" value="NewMidlet"><br>
                                    MIDlet-Vendor:<br>
                                    <input type="text" id="midletvendor" value="mbteam.ru"><br>
                                </div>
                            </div>

                        </td>
                        <td style="vertical-align: top; text-align: left; max-width: 570px; min-width: 570px;  height: 100%">
                            <textarea id="code" name="code"></textarea>
                        </td>                        
                    </tr></tbody>
            </table> <span class="legend"><?= $lang[$lng]['console'] ?></span>

            <div id="console">
                <div id="consoleText"></div>
            </div>
        </div>
        <script>
                        var files = [];
                        var sampleCode = [];
                        var sampleRand = 0;

                        var lng = '<?= $lng ?>';
                        var currentFile = 'Autorun.lis';

                        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
                            lineNumbers: true,
                            styleActiveLine: true,
                            matchBrackets: true,
                            theme: 'eclipse',
                            gutters: ["CodeMirror-linenumbers", "breakpoints"],
                            highlightSelectionMatches: true,
                            extraKeys: {
                                "F8": function(cm) {
                                    compiler.build();
                                },
                                "F7": function(cm) {
                                    compiler.compile();
                                },
                                "F9": function(cm) {
                                    compiler.build(compiler.run);
                                }
                            }
                        });

                        editor.on("gutterClick", function(cm, n) {
                            var info = cm.lineInfo(n);
                            cm.setGutterMarker(n, "breakpoints", info.gutterMarkers ? null : makeMarker());
                        });

                        function makeMarker() {
                            var marker = document.createElement("div");
                            marker.style.color = "#822";
                            marker.innerHTML = "●";
                            return marker;
                        }


                        var buffers = {};

                        $(".file").live("click", function(e) {
                            selectBuffer(editor, $(this).children().text());
                            $('.file span').attr("class", "null");
                            $(this).children().attr('class', 'bold');
                        });

                        $("#addsubm").click(function() {
                            window.location.href = '#console';
                        });

                        function openBuffer(name, text, mode) {
                            buffers[name] = CodeMirror.Doc(text, mode);
                            var opt = document.createElement("option");
                            opt.appendChild(document.createTextNode(name));
                            $('.file span').attr("class", "null");
                            $('#selectfile').html($('#selectfile').html() + '<span class="file"><span class="bold">' + name + '</span></span>');
                            if (name != 'Autorun.lis')
                                $('#selectfile').html($('#selectfile').html() + '<span class="del" onClick="delBuf(\'' + name + '\')"><img src="theme/delete.png" title="Delete"></span>');
                            $('#selectfile').html($('#selectfile').html() + '<br>');

                        }

                        function delBuf(name) {
                            if (name == 'Autorun.lis')
                                return;
                            delete(buffers[name]);
                            $('#selectfile').html('');
                            var i = 0;
                            for (var key in buffers) {
                                $('#selectfile').html($('#selectfile').html() + '<span class="file"><span' + ((i == 0) ? ' class="bold"' : '') + '>' + key + '</span></span>');
                                if (key != 'Autorun.lis')
                                    $('#selectfile').html($('#selectfile').html() + '<span class="del" onClick="delBuf(\'' + key + '\')"><img src="theme/delete.png" title="Delete"></span>');
                                $('#selectfile').html($('#selectfile').html() + '<br>');
                                i++;
                            }
                            selectBuffer(editor, 'Autorun.lis');
                        }

                        function newBuf() {
                            var name = prompt(lang[lng].nameinput, "file");
                            if (name == null)
                                return;
                            if (!name.match(/^([a-z0-9_\-\.])+$/g))
                            {
                                alert(lang[lng].nameerror);
                                return;
                            }
                            name = name.trim() + '.lis';
                            if (buffers.hasOwnProperty(name)) {
                                alert(lang[lng].namexist);
                                return;
                            }
                            if (name.length > 14) {
                                alert(lang[lng].namelong);
                                return;
                            }
                            openBuffer(name, "", "basic");
                            selectBuffer(editor, name);
                        }

                        function selectBuffer(editor, name) {
                            var buf = buffers[name];
                            if (buf.getEditor())
                                buf = buf.linkedDoc({
                                    sharedHist: true
                                });
                            var old = editor.swapDoc(buf);
                            var linked = old.iterLinkedDocs(function(doc) {
                                linked = doc;
                            });
                            if (linked) {
                                for (var name in buffers)
                                    if (buffers[name] == old)
                                        buffers[name] = linked;
                                old.unlinkDoc(linked);
                            }
                            currentFile = name;
                            editor.focus();
                        }

                        function nodeContent(id) {
                            var node = document.getElementById(id),
                                    val = node.textContent || node.innerText;
                            val = val.slice(val.match(/^\s*/)[0].length, val.length - val.match(/\s*$/)[0].length) + "\n";
                            return val;
                        }
                        sampleCode = (version == 1) ? sampleCode1 : sampleCode2;
                        sampleRand = Math.floor(Math.random() * sampleCode.length);
                        openBuffer("Autorun.lis", sampleCode[sampleRand], 'basic');
                        selectBuffer(editor, "Autorun.lis");



                        $('#basform').ajaxForm({
                            beforeSend: function() {
                                consoled.clear();
                                consoled.add(lang[lng].upload);


                            },
                            complete: function(xhr) {
                                var resp = $.parseJSON(xhr.responseText);
                                if ('error' in resp) {
                                    consoled.error(resp.error);
                                    consoled.add(lang[lng].ready);
                                    //editor.setValue('');
                                }
                                if ('lis' in resp) {
                                    consoled.success(lang[lng].osuccess);
                                    consoled.add(lang[lng].ready);
                                    editor.setValue(resp.lis);
                                    $('.CodeMirror-scroll').scrollTop(0);
                                }
                            }
                        });


        </script> <span id="copy" class="legend">&copy; mbteam.ru<br>
            by <a href="http://vk.com/holdfast">HoldFast</a><br><span class="hr"><a href="mbidesource.zip"><?= $lang[$lng]['sourcesite'] ?></a></span><br>
            </span>


    </body>

</html>
