///Autor HoldFast, (c) MBTEAM.RU
//http://vk.com/holdfast
var obf = false;
var version = 1;
var midlet = '';

function setCookie(c_name, value, exdays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
    document.cookie = c_name + "=" + c_value;
}

function getCookie(name) {
    var cookie = " " + document.cookie;
    var search = " " + name + "=";
    var setStr = null;
    var offset = 0;
    var end = 0;
    if (cookie.length > 0) {
        offset = cookie.indexOf(search);
        if (offset != -1) {
            offset += search.length;
            end = cookie.indexOf(";", offset)
            if (end == -1) {
                end = cookie.length;
            }
            setStr = unescape(cookie.substring(offset, end));
        }
    }
    return (setStr);
}

setCookie('restmp', '');

if (getCookie("obf") == "true")
    obf = true;
if (getCookie("version") == "2")
    version = 2;

var lang = {
    en: {
        ready: 'Ready.',
        obfuscation: 'Obfuscation',
        compilation: 'Compilation',
        csuccess: 'was compiled successfully',
        bsuccess: 'Builded successfully',
        osuccess: 'File successfully opened',
        linkto: 'Link to',
        isempty: 'is empty',
        upload: 'Uploading...',
        uploadstart: 'Upload',
        uploadesc: 'Select a file to upload',
        nameinput: 'Name for the source (without .lis)',
        namexist: 'There\'s already a source by that name.',
        namelong: 'Very long file name (max. 10)',
        nameerror: 'Error file name',
        uploaderr: 'Error to upload file',
        process: 'Process',
        delerr: 'Error to delete file',
        setting: 'Settings IDE',
        mbver: 'Version of MobileBASIC',
        help: 'Help',
        run: 'Emulator',
        runstart: 'Emulator starting...'
    },
    ru: {
        ready: 'Готов.',
        obfuscation: 'Обфускация',
        compilation: 'Компиляция',
        csuccess: 'успешно скомпилирован',
        bsuccess: 'Сборка успешно завершена',
        osuccess: 'Файл успешно открыт',
        linkto: '<b>Ссылка на</b>',
        isempty: 'пуст',
        upload: 'Выгрузка файла...',
        uploadstart: 'Добавить',
        uploadesc: 'Выберите файл для добавления',
        nameinput: 'Имя файла (без .lis)',
        namexist: 'Такой файл уже есть',
        namelong: 'Слишком длинное имя файла (макс. 10)',
        nameerror: 'Имя содержит недопустимые символы',
        uploaderr: 'Ошибка при добавлении файла',
        process: 'Загрузка',
        delerr: 'Ошибка удаления файла',
        setting: 'Настройки IDE',
        mbver: 'Версия MobileBASIC',
        help: 'Справка',
        run: 'Запуск',
        runstart: 'Запуск эмулятора...'
    }

}


var sampleCode1 = ['10 PRINT "HELLO, MB IDE!"\n20 SLEEP 5000\n30 END', '10 PRINT "Hello, World!"\n11 SLEEP 10000', '10 PRINT "MobileBASIC Online IDE worked! =)"\n20 SLEEP 9000\n30 END'];
var sampleCode2 = ['10 PRINT "HELLO, MB IDE!"\n20 REPAINT\n30 SLEEP 5000\n40 END', '10 PRINT "Hello, World!"\n11 REPAINT\n12 SLEEP 10000', '10 PRINT "MobileBASIC Online IDE worked! =)"\n30 REPAINT\n30 SLEEP 9000\n40 END'];

var consoled = {
    scroll: function() {
        var div = document.getElementById('console');
        div.scrollTop = div.scrollHeight;
    },
    add: function(text) {
        $('#openbas').hide();
        $('#setting').fadeOut();
        $('#consoleText').html($('#consoleText').html() + text + '<br>');
        this.scroll();
    },
    error: function(text) {
        $('#consoleText').html($('#consoleText').html() + '<img src="theme/error.png" alt="" style="vertical-align: middle"> <span style="color: red; white-space: pre-wrap; font-weight: bold; word-wrap: break-word; vertical-align: middle">' + text + '</span><br>');
        this.scroll();
    },
    warning: function(text) {
        $('#consoleText').html($('#consoleText').html() + '<img src="theme/error.png" alt="" style="vertical-align: middle"> <span style="color: blue; white-space: pre-wrap; word-wrap: break-word; vertical-align: middle">Warning! ' + text + '</span><br>');
        this.scroll();
    },
    success: function(text) {
        $('#consoleText').html($('#consoleText').html() + '<img src="theme/success.png" alt="" style="vertical-align: middle"> <span style="color: green; font-weight: bold; vertical-align: middle">' + text + '</span><br>');
        this.scroll();
    },
    clear: function() {
        $('#consoleText').html('');
    }
}

var json = {
    get: function() {
        var jsontxt = '{"file":[';
        var i = 0;
        for (var key in buffers) {
            var code = encodeURIComponent(buffers[key].getValue());
            if (code != '') {
                if (i > 0)
                    jsontxt += ',';
                jsontxt = jsontxt + '{"name":"' + key + '","code":"' + code + '"}';
            }
            i++;
        }
        return jsontxt + ']}';
    }
}

$(document).ready(function() {
    consoled.add(lang[lng].ready);
});

var compiler = {
    compile: function() {
        if (editor.getValue().trim() != '') {
            location.href = '#console';
            consoled.clear();
            consoled.add(lang[lng].obfuscation + ': ' + obf + '...');
            consoled.add(lang[lng].compilation + '...');
            var obfus = (obf) ? 'on' : 0;
            $.post('ajax.php', {
                'act': 'compile',
                'obf': obfus,
                'ver': version,
                'code': editor.getValue()
            }, function(data) {
                var resp = $.parseJSON(data);
                if ('error' in resp) {
                    consoled.error(currentFile + ': ' + resp.error);
                    consoled.add(lang[lng].ready);
                }
                if ('warning' in resp) {
                    consoled.warning(resp.warning);
                }
                if ('bas' in resp) {
                    consoled.success(currentFile + ' ' + lang[lng].csuccess);
                    consoled.add('<b>' + lang[lng].linkto + ' BAS</b>: <a href="src/' + resp.bas + '.bas">' + resp.bas + '.bas</a>');
                    consoled.add(lang[lng].ready);
                }
            });
        } else {
            consoled.clear();
            consoled.error(currentFile + ' ' + lang[lng].isempty);
            consoled.add(lang[lng].ready);
        }
    },
    build: function(run) {
        if (buffers['Autorun.lis'].getValue() != '') {
            location.href = '#console';
            consoled.clear();
            consoled.add(lang[lng].obfuscation + ': ' + obf + '...');
            var obfus = (obf) ? 'on' : 0;
            consoled.add(lang[lng].compilation + '...');
            $.post('ajax.php', {
                'act': 'build',
                'json': json.get(),
                'obf': obfus,
                'ver': version,
                'midletname': $('#midletname').val(),
                'midletvendor': $('#midletvendor').val()
            }, function(data) {
                var resp = $.parseJSON(data);
                if ('error' in resp) {
                    consoled.error(resp.error);
                    consoled.add(lang[lng].ready);
                }
                if ('warning' in resp) {
                    consoled.warning(resp.warning);
                }
                if ('jar' in resp) {
                    consoled.success(lang[lng].bsuccess);
                    consoled.add('<b>' + lang[lng].linkto + ' jar:</b> <a href="jar/' + resp.jar + '/BASIC.jar">BASIC.jar</a>');

                    if (run != undefined) {
                        if (version == 1) {
                            midlet = 'cpu';
                            consoled.add(lang[lng].runstart);
                            run(resp.jar, midlet);
                        } else
                            consoled.error('Emulator do not work for MobileBASIC 1.9.1');
                    } else
                        consoled.add(lang[lng].ready);
                }
            });
        } else {
            consoled.clear();
            consoled.error('Autorun.lis ' + lang[lng].isempty);
            consoled.add(lang[lng].ready);
        }
    },
    run: function(jar, midlet) {
        emulatorDialogOpen(jar, midlet);
    }
}

function fileDelete(id) {
    $.post('ajax.php', {
        'act': 'deletefile',
        'file': files[id]
    }, function(data) {

        if (data === 'del') {
            delete(files[id]);
            fileWrite();
        } else {
            consoled.error(lang[lng].delerr);
            consoled.add(lang[lng].ready);
        }
    });
}

function fileWrite() {
    var html = '';
    for (i = 0; i < files.length; i++) {
        if (files[i] !== undefined)
            html += '<span class="res">' + files[i] + '</span><a href="javascript:fileDelete(' + i + ');"><img src="theme/delete.png" title="Delete"></a><br>';
    }
    $('#resource').html(html);
}


function emulatorDialogOpen(src) {
    $('#dialogcontent').html('<div class="modalhead">' + lang[lng].run + '<span class="close"><a href="javascript:DialogClose();"><img src="theme/cross.png" alt=""></a></span></div><div id="emulator"><applet id="emul" code="org.microemu.applet.Main" width="240" height="471" archive="/emulator/microemu-javase-applet.jar,/emulator/microemu-jsr-120.jar,/emulator/microemu-jsr-135.jar,/emulator/microemu-jsr-75.jar,/emulator/microemu-jsr-82.jar,/emulator/microemu-nokiaui.jar,/emulator/cldcapi10.jar,/emulator/cldcapi11.jar,/emulator/microemu-siemensapi.jar,/emulator/midpapi20,/jar/' + src + '/BASIC.jar"><param name="midlet" value="' + midlet + '"></applet></div>');

    $('#overlay').fadeIn(200);
    $('#overlay2').fadeIn(200);
}

function addDialogOpen() {
    $('.modal').css('width', '350px');
    $('#dialogcontent').html('<div class="modalhead">' + lang[lng].uploadesc + '<span class="close"><a href="javascript:DialogClose();"><img src="theme/cross.png" alt=""></a></span></div>(.mp3, .mid, .midi, .png, .jpg, .png, .bmp, .gif, .wav, .txt, .lis)<br><br><form id="resform" method="post" action="ajax.php?act=add"><input type="file" name="res"><input id="addsubm" type="submit" value="' + lang[lng].uploadstart + '"></form>');
    $('#resform').ajaxForm({
        beforeSend: function() {
            DialogClose();
            $('#add').hide();
        },
        uploadProgress: function(event, position, total, percentComplete) {
            consoled.clear();
            consoled.add('<span style="color: green; font-weight: bold;">' + lang[lng].process + ': ' + percentComplete + '%</span>');
        },
        complete: function(xhr) {
            consoled.clear();
            $('#add').show();
            if (xhr.responseText !== '' && xhr.responseText !== '{error}' && xhr.status === 200) {
                files.push(xhr.responseText);
                fileWrite();
                consoled.add(lang[lng].ready);
            } else {
                consoled.error('<b>' + lang[lng].uploaderr + '<b>');
                consoled.add(lang[lng].ready);
            }
        }
    });
    $('#overlay').fadeIn(200);
    $('#overlay2').fadeIn(200);
}


function setDialogOpen() {
    $('#dialogcontent').html('<div class="modalhead">' + lang[lng].setting + '<span class="close"><a href="javascript:DialogClose();"><img src="theme/cross.png" alt=""></a></span></div><div id="setting">' + lang[lng].mbver + ':<br><select id="ver"><option' + ((version == 1) ? ' selected="selected"' : '') + ' value="1">MobileBASIC 1.8.6.2</option><option' + ((version == 2) ? ' selected="selected"' : '') + ' value="2">MobileBASIC 1.9.1</option></select></div><div id="setting"> ' + lang[lng].obfuscation + ' <input type="checkbox" ' + (obf ? 'checked="checked"' : '') + ' id="obf"></div>');
    $('#overlay').fadeIn(200);
    $('#overlay2').fadeIn(200);
}

function helpDialogOpen() {
    $('#dialogcontent').html('<div class="modalhead">' + lang[lng].help + '<span class="close"><a href="javascript:DialogClose();"><img src="theme/cross.png" alt=""></a></span></div><div id="help"><b>Онлайн среда разработки на MobileBASIC</b></div>');
    $('#overlay').fadeIn(200);
    $('#overlay2').fadeIn(200);
}


function DialogClose() {
    $('#emul').hide();
    $('#overlay2').fadeOut(150);
    $('#overlay').fadeOut(150);
}

$('#obf').live('click', function() {
    obf = $("#obf").prop('checked');
    if (obf == true)
        setCookie("obf", "true", 365);
    else
        setCookie("obf", "false", 365);
});

$('#ver').live('change', function() {
    version = $('#ver').val();
    if (version == 2) {
        if (editor.getValue() == sampleCode1[sampleRand])
            editor.setValue(sampleCode2[sampleRand]);
        setCookie("version", "2", 365);
    } else {
        if (editor.getValue() == sampleCode2[sampleRand])
            editor.setValue(sampleCode1[sampleRand]);
        setCookie("version", "1", 365);
    }

});