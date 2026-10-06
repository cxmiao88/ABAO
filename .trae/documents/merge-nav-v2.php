<?php
$enFile = '/tmp/en-base.json';
$zhFile = '/tmp/zh-cn-base.json';
$en = json_decode(file_get_contents($enFile), true);
$zh = json_decode(file_get_contents($zhFile), true);
echo 'base en=' . count($en) . ' zh=' . count($zh) . PHP_EOL;
$nav = [
    'nav.workspace' => ['en' => 'Workspace', 'zh' => '工作区'],
    'nav.dashboard' => ['en' => 'Dashboard', 'zh' => '仪表盘'],
    'nav.projects' => ['en' => 'Projects', 'zh' => '项目'],
    'nav.analytics' => ['en' => 'Analytics', 'zh' => '分析'],
    'nav.terminal' => ['en' => 'Terminal', 'zh' => '终端'],
    'nav.infrastructure' => ['en' => 'Infrastructure', 'zh' => '基础设施'],
    'nav.servers' => ['en' => 'Servers', 'zh' => '服务器'],
    'nav.sources' => ['en' => 'Sources', 'zh' => '代码源'],
    'nav.destinations' => ['en' => 'Destinations', 'zh' => '部署目标'],
    'nav.registries' => ['en' => 'Registries', 'zh' => '镜像仓库'],
    'nav.s3_storage' => ['en' => 'S3 Storage', 'zh' => 'S3 存储'],
    'nav.shared_variables' => ['en' => 'Shared Variables', 'zh' => '共享变量'],
    'nav.manage' => ['en' => 'Manage', 'zh' => '管理'],
    'nav.team' => ['en' => 'Team', 'zh' => '团队'],
    'nav.notifications' => ['en' => 'Notifications', 'zh' => '通知'],
    'nav.keys_tokens' => ['en' => 'Keys & Tokens', 'zh' => '密钥与令牌'],
    'nav.subscription' => ['en' => 'Subscription', 'zh' => '订阅'],
    'nav.tags' => ['en' => 'Tags', 'zh' => '标签'],
    'nav.settings' => ['en' => 'Settings', 'zh' => '设置'],
    'nav.admin' => ['en' => 'Admin', 'zh' => '管理后台'],
];
foreach ($nav as $k => $v) {
    $en[$k] = $v['en'];
    $zh[$k] = $v['zh'];
}
ksort($en);
ksort($zh);
file_put_contents($enFile, json_encode($en, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
file_put_contents($zhFile, json_encode($zh, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo 'merged en=' . count($en) . ' zh=' . count($zh) . PHP_EOL;
