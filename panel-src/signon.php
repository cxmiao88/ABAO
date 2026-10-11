<?php
/**
 * ABao 阿宝面板：phpMyAdmin signon 单点登录跳板（B03-49）
 *
 * 流程：面板「管理」→ /phpmyadmin/signon.php?db=X&token=T
 *   1) 用 token 向容器 panel-api 换取该库专属账号+密码（一次性、5 分钟有效）
 *   2) 写入 SignonSession 会话（与 phpMyAdmin signon 模式同一 session）
 *   3) 302 到 phpMyAdmin 指定库页；MySQL 授权只允许该账号访问被授权的库 → 天然隔离
 *
 * 本文件由 install.sh 固化部署到 /www/server/phpmyadmin/phpmyadmin_sjqemi/signon.php，
 * 与 config.inc.php 的 auth_type=signon 配套使用。
 */
error_reporting(0);

$db    = isset($_GET['db'])    ? (string) $_GET['db']    : '';
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
if ($db === '' || ! preg_match('/^[a-zA-Z0-9_]+$/', $db) || ! preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(400);
    exit('参数错误：请从 ABao 面板的数据库「管理」入口进入');
}

// 1) 向容器 panel-api 换取凭据（token 自证，免登录；127.0.0.1:8000 即容器 nginx）
$api = 'http://127.0.0.1:8000/panel-api/host/phpmyadmin/resolve/' . $token;
$ch  = curl_init($api);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$resp = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);
if ($code !== 200 || $resp === false) {
    http_response_code(401);
    exit('令牌校验失败，请返回面板重新点击「管理」');
}
$data = json_decode((string) $resp, true);
if (! is_array($data) || ($data['status'] ?? '') !== 'ok' || empty($data['user']) || empty($data['pwd'])) {
    http_response_code(401);
    exit('令牌无效或已过期，请返回面板重新点击「管理」');
}

// 2) 写入 phpMyAdmin signon 会话（session 名与 config.inc.php 的 SignonSession 一致）
session_name('SignonSession');
session_start();
$_SESSION['PMA_single_signon_user']     = (string) $data['user'];
$_SESSION['PMA_single_signon_password'] = (string) $data['pwd'];
$_SESSION['PMA_single_signon_host']     = '127.0.0.1';
$_SESSION['PMA_single_signon_port']     = 33206;
session_write_close();

// 3) 跳转 phpMyAdmin 指定库（走 8000 反代路径）
header('Location: /phpmyadmin/index.php?route=/database/structure&db=' . rawurlencode((string) $data['db']) . '&lang=zh_CN');
exit;
