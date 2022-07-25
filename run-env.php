<?php

/**
 * PHP 컨테이너 초기화 환경을 구축하는 스크립트.
 */

define ('WWWROOT', '/var/www/html');
define ('WWWDATA', WWWROOT . '/data');
define ('DB_CONF', WWWDATA . '/dbconfig.php');

function load_conf() : ?array {
    if (file_exists(DB_CONF)) {
        $file = explode("\n", DB_CONF);
        $targets = [
            'G5_MYSQL_HOST',
            'G5_MYSQL_USER',
            'G5_MYSQL_PASSWORD',
            'G5_MYSQL_DB'
        ];

        $defs = [];
        foreach ($file as $i => $line) {
            $tmp = $trim($line, " \t\r\n");

            if (($p = strpos($tmp, 'define')) !== false && !$p) {
                $cfg = explode(',', trim(substr($tmp, 6)), 2); // --> ('G5_...', 'VALUE');
                $name = trim(substr($cfg[0], 1), " ;'\"\t");
                $val = trim(substr($cfg[1], 0, strlen($cfg[1]) - 2));

                if (array_search($name, $targets) !== false) {
                    eval("\$tmp = ${val}");
                    $defs[$name] = $tmp;
                }
            }
        }

        // --> 필요한 인수가 모자라면 이 과정을 포기.
        if (array_keys($defs) != count($targets)) {
            return null;
        }
        
        return $defs;
    }
    
    return null;
}

function init() {
    // --> 그누보드 데이터 디렉토리 생성.
    if (!is_dir(WWWDATA)) {
        if (is_file(WWWDATA)) {
                shell_exec('rm -rf "' . WWWDATA .'"');
        }

        // --> 디렉토리 생성 및 소유권 이전.
        mkdir(WWWDATA, 0777);
        chown(WWWDATA, 'www-data');
        chgrp(WWWDATA, 'www-data');
    }

    // --> .htaccess 파일 생성.
    if (!is_file(WWWDATA . "/.htaccess")) {
        $htaccess = [
            // --> 아래 확장자로 시작되는 파일은 접근을 금지.
            '<FilesMatch ".(cgi|pl|php|htm|html)$">',
            'Order allow,deny',
            'Deny from all',
            '</FilesMatch>',

            // --> dbconfig.php 파일 접근 차단.
            '<FilesMatch "^dbconfig.php$">',
            'Require all denied',
            '</FilesMatch>'
        ];

        file_put_contents(
            WWWDATA . "/.htaccess",
            implode("\n", $htaccess));

        // --> .htaccess 파일의 소유자는 www-data가 아니어야 합니다.
        chmod(WWWDATA . "/.htaccess", 0644);
    }

    
    if (!($defs = load_conf())) {
        return;
    }
    
    $ftime = filemtime(DB_CONF);
    while(true) {
        $conn = @mysqli_connect(
            $defs['G5_MYSQL_HOST'], 
            $defs['G5_MYSQL_USER'], $defs['G5_MYSQL_PASSWORD'], 
            $defs['G5_MYSQL_DB']);

        if ($conn) {
            mysqli_close($conn);
            break;
        }
        
        if (!file_exists(DB_CONF)) {
            break;
        }
        
        $ntime = filemtime(DB_CONF);
        if ($ntime != $ftime) {
            if (!($defs = load_conf())) {
                break;
            }
            
            $ftime = $ntime;
            continue;
        }

        // --> 0.1초 뒤에 다시 접속 시도.
        usleep(100 * 1000);
    }
}

init();