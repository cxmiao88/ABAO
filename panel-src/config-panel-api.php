<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ABao panel API configuration
    |--------------------------------------------------------------------------
    |
    | 统一登录（阶段 3.1）：新前端登录时，Laravel 用下面配置的服务端校验
    | mdserver-web 凭据，通过后直接建立 Coolify 内部管理员会话。
    | Coolify admin 密码不进入前端，也不需在此配置（走内部登录）。
    */

    // mdserver-web 服务地址（Coolify 容器视角：host.docker.internal 可达 WSL 宿主 48700）
    'mdserver_url' => env('PANEL_MDSERVER_URL', 'http://host.docker.internal:48700'),

    // 统一登录建立的 Coolify 会话归属（根管理员邮箱，id=0）
    'admin_email' => env('PANEL_ADMIN_EMAIL', 'wxck88@qq.com'),
];
