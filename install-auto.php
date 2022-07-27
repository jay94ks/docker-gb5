<?php

/**
 * HTTP로 전달되는 $_POST 인수를 수동으로 제공합니다.
 */

$required = [
    "mysql_host",
    "mysql_user",
    "mysql_pass",
    "mysql_db"
];

$defaults = [
    "table_prefix" => "g5_",
    "admin_id" => "admin",
    "admin_pass" => "abcd1234",
    "admin_name" => "최고관리자",
    "admin_email" => "admin@localhost",
    "g5_shop_prefix" => "yc5_",
    "g5_shop_install" => "1",

    // $tm_shop = $_POST['tm_shop']; --> covered by end of this file.
    "tm_name" => 'eb4_basic',

    // --> 현재 eb4에서 아래 값들은 하드코딩된 상수 취급을 받고 있지만,
    //   : 별도로 form field로 작성된 것으로 보아, 머지 않은 미래에 확장될
    //   : 가능성이 있다고 판단, 기본값으로 입력하였습니다.

    "tm_community" => 'y',
    "tm_mainside" => 'y',
    "tm_subside" => 'y',
    "tm_mainpos" => 'right',
    "tm_subpos" => 'right',
    "tm_shopmainside" => 'n',
    "tm_shopsubside" => 'n',
    "tm_shopmainpos" => 'right',
    "tm_shopsubpos" => 'right'
];

function parse_yn($yn) {
    if (!$yn) {
        return $yn;
    }

    switch (strtolower($yn)) {
        case "y": case "yes": case "true":
            return "1";

        default:
            break;
    }
    
    return "0";
}

$_POST["mysql_host"]   = getenv("G5_MYSQL_HOST");
$_POST["mysql_user"]   = getenv("G5_MYSQL_USER");
$_POST["mysql_pass"]   = getenv("G5_MYSQL_PASSWORD");
$_POST["mysql_db"]     = getenv("G5_MYSQL_DB");
$_POST["table_prefix"] = getenv("G5_TABLE_PREFIX");
$_POST["admin_id"]     = getenv("G5_ADMIN_ID");
$_POST["admin_pass"]   = getenv("G5_ADMIN_PASSWORD");
$_POST["admin_name"]   = getenv("G5_ADMIN_NAME");
$_POST["admin_email"]  = getenv("G5_ADMIN_EMAIL");
$_POST["g5_install"]   = parse_yn(getenv("G5_FORCE_INSTALL"));
$_POST["g5_shop_prefix"] = getenv("G5_SHOP_PREFIX");
$_POST["g5_shop_install"] = parse_yn(getenv("G5_SHOP_INSTALL"));

/**
 * TODO: eb4 specific automation options required.
 * 
 * "tm_community" => 'y',
 * "tm_mainside" => 'y',
 * "tm_subside" => 'y',
 * "tm_shopmainside" => 'n',
 * "tm_shopsubside" => 'n',
 * 
 * right, left? mid? 
 * 
 * -- 아직 구현체가 없어서, 어떤 옵션을 어떻게 들어가게 될지 모르므로,
 *    내일의 나에게 일을 미루도록 하자!
 * 
 * "tm_mainpos" => 'right',
 * "tm_subpos" => 'right',
 * "tm_shopmainpos" => 'right',
 * "tm_shopsubpos" => 'right' 
 */

$missings = [];
foreach($required as $i => $key) {
    if (!$_POST[$key] || strlen($_POST[$key]) <= 0) {
        $missings[] = $key;
    }
}

if (count($missings) > 0) {
    echo "required parameters are missing: " . implode(', ', $missings) . "\n";
    exit(1);
}

// --> 필수 인자가 아니라면, 기본값으로 설치합니다.
foreach($defaults as $key => $value) {
    if (!$_POST[$key] || strlen($_POST[$key]) <= 0) {
        $_POST[$key] = $value;

        // --> eb4 에서 사용하는 변수.
        if (substr($key, 0, 2) != 'tm') {
            echo "warning: no ${key} specified, use default: ${value}\n";
        }
    }
}

// --> 여기서 MySQL이 준비될 때 까지 대기합니다.
echo "waiting for MySQL to be available...\n";
while(true) {
    $conn = @mysqli_connect(
        $_POST['mysql_host'], 
        $_POST['mysql_user'], $_POST['mysql_pass'], 
        $_POST['mysql_db']);
        
    if ($conn) {
        mysqli_close($conn);
        break;
    }

    echo
        "failed to connect MySQL server: " .
        mysqli_connect_error() . "\n";
    
    // --> 1초 뒤에 다시 접속 시도.
    usleep(1000 * 1000);
}

// --> from eyoom/install/setup_db.php on line 41
$_POST['tm_shop'] = file_exists('../../shop.config.php') ? 'y': 'n';

// --> eyoom/install/setup_db.php on line 143, 187.
$g5_install = $_POST["g5_install"];

// --> eyoom/install/setup_db.php on line 72
$g5_shop_prefix = $_POST["g5_shop_prefix"];

// --> eyoom/install/setup_db.php on line 726
$_SERVER['HTTP_HOST'] = ""; 

// --> g5 requires:
$_SERVER['REMOTE_ADDR'] = "127.0.0.1";

// --> CLI에 HTML이 출력되는 것을 방지합니다.
ob_start();

// --> 작업 경로가 다를 경우를 대비해 현재 디렉토리로 변경합니다.
chdir (__DIR__);
include (__DIR__ . "/setup_db.php");
ob_end_clean();

if (!file_exists(__DIR__ . "/../../data/dbconfig.php")) {
    echo "failed to install g5 automatically.\n";
    echo "if this occurs again, consider to use non-auto branches.\n";
    exit(1);
}

$rm_legal_info = parse_yn(getenv("G5_RM_LEGAL_INFO"));
if ($rm_legal_info == "1") {
    unlink (__DIR__ . "/../../LICENSE.txt");
    unlink (__DIR__ . "/../../README.md");
    unlink (__DIR__ . "/../../perms.sh");
}

$rm_import_utils = parse_yn(getenv("G5_RM_IMORT_UTILS"));
if ($rm_import_utils == '1') {
    unlink (__DIR__ . "/../../g4_import.php");
    unlink (__DIR__ . "/../../g4_import_run.php");
    unlink (__DIR__ . "/../../yc4_import.php");
    unlink (__DIR__ . "/../../yc4_import_run.php");
}

$rm_yc_old_util = parse_yn(getenv("G5_RM_YC_OLD_UTIL"));
if ($rm_yc_old_util == '1') {
    unlink (__DIR__ . "/../../orderupgrade.php");
}

exit(0);