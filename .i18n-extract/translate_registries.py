#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

def dump(p, d):
    keys = sorted(d.keys())
    lines = ["{"]
    for i, k in enumerate(keys):
        c = "," if i < len(keys) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")

KEYS = {
    "server.reg_error_not_reachable": ("The server is not reachable. Validate the server to read its registry logins.", "服务器无法访问。请验证服务器以读取其镜像仓库登录信息。"),
    "server.reg_logins_note": ("Registry logins are read from the Docker config that deployments use. Use <b>Log in</b>, or run <code>docker login &lt;registry&gt;</code> on the server.", "镜像仓库登录信息从部署使用的 Docker 配置中读取。使用 <b>登录</b>，或在服务器上运行 <code>docker login &lt;registry&gt;</code>。"),
    "server.reg_refresh": ("Refresh", "刷新"),
    "server.reg_new": ("New", "新建"),
    "server.reg_login_to": ("Log in to a registry", "登录镜像仓库"),
    "server.reg_login_subtitle": ("Runs docker login on :server.", "在 :server 上运行 docker login。"),
    "server.reg_not_logged_in_note": ("Not logged in to any registry, and no resource on this server uses a private registry host.", "未登录任何镜像仓库，此服务器上也没有资源使用私有镜像仓库主机。"),
    "server.reg_col_registry": ("Registry", "镜像仓库"),
    "server.reg_col_status": ("Status", "状态"),
    "server.reg_col_used_by": ("Used by", "使用方"),
    "server.reg_status_login_failed": ("Login failed", "登录失败"),
    "server.reg_status_cred_helper": ("Credential helper", "凭据助手"),
    "server.reg_status_logged_in": ("Logged in", "已登录"),
    "server.reg_status_unknown": ("Unknown", "未知"),
    "server.reg_status_not_logged_in": ("Not logged in", "未登录"),
    "server.reg_dockerhub_no_login": ("Public Docker Hub images do not need a login.", "公共 Docker Hub 镜像无需登录。"),
    "server.reg_test_tip": ("Run docker login with the saved credentials", "使用已保存的凭据运行 docker login"),
    "server.reg_test": ("Test", "测试"),
    "server.reg_log_in": ("Log in", "登录"),
    "server.reg_login_registry": ("Log in to :registry", "登录 :registry"),
    "server.reg_edit": ("Edit", "编辑"),
    "server.reg_edit_registry": ("Edit login for :registry", "编辑 :registry 的登录信息"),
    "server.reg_edit_subtitle": ("Runs docker login on :server again. The new values replace the old login.", "在 :server 上再次运行 docker login。新值将替换旧登录信息。"),
    "server.reg_delete_confirm": ("Delete the login for :registry?", "删除 :registry 的登录信息？"),
    "server.reg_delete": ("Delete", "删除"),
    "server.reg_delete_action1": ("Runs docker logout on :server and removes the saved credentials.", "在 :server 上运行 docker logout 并移除已保存的凭据。"),
    "server.reg_delete_action2": ("The server can no longer pull private images from this registry.", "服务器将无法再从该镜像仓库拉取私有镜像。"),
    "server.reg_delete_action3": ("Deployments that need this registry fail until you log in again.", "需要此镜像仓库的部署将失败，直到您重新登录。"),
    "server.reg_dockerhub_uses": ("1 resource on this server uses|:count resources on this server use", "此服务器上有 :count 个资源使用"),
    "server.reg_dockerhub_suffix": ("Docker Hub images. Public images need no login. Log in to Docker Hub for private images or higher pull limits.", "Docker Hub 镜像。公共镜像无需登录。如需私有镜像或更高拉取限额，请登录 Docker Hub。"),
}

for p, lang in [(r"G:\xianmu\juqing\baoUIIT\lang\en.json", 0), (r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json", 1)]:
    d = json.load(open(p, encoding="utf-8"))
    added = 0
    for k, (en, zh) in KEYS.items():
        if k not in d:
            d[k] = en if lang == 0 else zh
            added += 1
    if added:
        dump(p, d)
        print(p.split("\\")[-1], "added:", added)

# ---- PHP 层错误消息 ----
p = r"G:\xianmu\juqing\baoUIIT\app\Livewire\Server\DockerRegistries\ServerRegistries.php"
c = open(p, encoding="utf-8").read()
old = "$this->error = 'The server is not reachable. Validate the server to read its registry logins.';"
new = "$this->error = __('server.reg_error_not_reachable');"
if old in c:
    c = c.replace(old, new)
    open(p, "w", encoding="utf-8").write(c)
    print("OK ServerRegistries.php")
else:
    print("MISS ServerRegistries.php")

# ---- registries.blade.php ----
p2 = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\registries.blade.php"
c2 = open(p2, encoding="utf-8").read()
pairs2 = [
    ("{{ data_get_str($server, 'name')->limit(10) }} > Registries | ABao",
     "{{ data_get_str($server, 'name')->limit(10) }} > {{ __('server.registries_title') }} | ABao"),
    ("Registry logins are read from the Docker config that deployments use. Use <b>Log in</b>, or run\n                <code>docker login &lt;registry&gt;</code> on the server.",
     "{!! __('server.reg_logins_note') !!}"),
]
for old, new in pairs2:
    if old not in c2:
        print("MISS2:", old[:60])
    c2 = c2.replace(old, new)
open(p2, "w", encoding="utf-8").write(c2)
print("OK registries.blade")

# ---- server-registries.blade.php ----
p3 = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\docker-registries\server-registries.blade.php"
c3 = open(p3, encoding="utf-8").read()
pairs3 = [
    ("                Refresh\n", "                {{ __('server.reg_refresh') }}\n"),
    ('<x-modal-input buttonTitle="New" title="Log in to a registry"\n                    subtitle="Runs docker login on {{ $server->name }}.">',
     '<x-modal-input buttonTitle="{{ __(\'server.reg_new\') }}" title="{{ __(\'server.reg_login_to\') }}"\n                    subtitle="{{ __(\'server.reg_login_subtitle\', [\'server\' => $server->name]) }}">'),
    ("Not logged in to any registry, and no resource on this server uses a private registry host.",
     "{{ __('server.reg_not_logged_in_note') }}"),
    ("<div>Registry</div>", "<div>{{ __('server.reg_col_registry') }}</div>"),
    ("<div>Status</div>", "<div>{{ __('server.reg_col_status') }}</div>"),
    ("<div>Used by</div>", "<div>{{ __('server.reg_col_used_by') }}</div>"),
    ('<x-status-badge type="error" label="Login failed"\n                                    :title="$loginChecks[$row[\'registry\']][\'message\']" />',
     '<x-status-badge type="error" label="{{ __(\'server.reg_status_login_failed\') }}"\n                                    :title="$loginChecks[$row[\'registry\']][\'message\']" />'),
    (":label=\"$row['source'] === 'credHelpers' ? 'Credential helper' : 'Logged in'\"",
     ":label=\"$row['source'] === 'credHelpers' ? __('server.reg_status_cred_helper') : __('server.reg_status_logged_in')\""),
    ('<x-status-badge label="Unknown" />', '<x-status-badge label="{{ __(\'server.reg_status_unknown\') }}" />'),
    ('<x-status-badge :type="$row[\'registry\'] === \'docker.io\' ? \'neutral\' : \'warning\'" label="Not logged in"\n                                    :title="$row[\'registry\'] === \'docker.io\' ? \'Public Docker Hub images do not need a login.\' : null" />',
     '<x-status-badge :type="$row[\'registry\'] === \'docker.io\' ? \'neutral\' : \'warning\'" label="{{ __(\'server.reg_status_not_logged_in\') }}"\n                                    :title="$row[\'registry\'] === \'docker.io\' ? __(\'server.reg_dockerhub_no_login\') : null" />'),
    ('title="Run docker login with the saved credentials">Test</button>',
     'title="{{ __(\'server.reg_test_tip\') }}">{{ __(\'server.reg_test\') }}</button>'),
    ('<x-modal-input buttonTitle="Log in" title="Log in to {{ $row[\'registry\'] }}"\n                                    subtitle="Runs docker login on {{ $server->name }}.">',
     '<x-modal-input buttonTitle="{{ __(\'server.reg_log_in\') }}" title="{{ __(\'server.reg_login_registry\', [\'registry\' => $row[\'registry\']]) }}"\n                                    subtitle="{{ __(\'server.reg_login_subtitle\', [\'server\' => $server->name]) }}">'),
    ('<x-modal-input buttonTitle="Edit" title="Edit login for {{ $row[\'registry\'] }}"\n                                    subtitle="Runs docker login on {{ $server->name }} again. The new values replace the old login.">',
     '<x-modal-input buttonTitle="{{ __(\'server.reg_edit\') }}" title="{{ __(\'server.reg_edit_registry\', [\'registry\' => $row[\'registry\']]) }}"\n                                    subtitle="{{ __(\'server.reg_edit_subtitle\', [\'server\' => $server->name]) }}">'),
    ('<x-modal-confirmation title="Delete the login for {{ $row[\'registry\'] }}?" buttonTitle="Delete" isErrorButton\n                                    submitAction="logout({{ $row[\'registry\'] }})" :actions="[\n                                        \'Runs docker logout on \'.$server->name.\' and removes the saved credentials.\',\n                                        \'The server can no longer pull private images from this registry.\',\n                                        \'Deployments that need this registry fail until you log in again.\',\n                                    ]"',
     '<x-modal-confirmation title="{{ __(\'server.reg_delete_confirm\', [\'registry\' => $row[\'registry\']]) }}" buttonTitle="{{ __(\'server.reg_delete\') }}" isErrorButton\n                                    submitAction="logout({{ $row[\'registry\'] }})" :actions="[\n                                        __(\'server.reg_delete_action1\', [\'server\' => $server->name]),\n                                        __(\'server.reg_delete_action2\'),\n                                        __(\'server.reg_delete_action3\'),\n                                    ]"'),
    ("{{ trans_choice(':count resource on this server uses|:count resources on this server use', count($dockerHubRow['used_by'])) }}\n            Docker Hub images. Public images need no login. Log in to Docker Hub for private images or higher pull limits.",
     "{{ trans_choice('server.reg_dockerhub_uses', count($dockerHubRow['used_by'])) }}\n            {{ __('server.reg_dockerhub_suffix') }}"),
]
for old, new in pairs3:
    if old not in c3:
        print("MISS3:", old[:70])
    c3 = c3.replace(old, new)
open(p3, "w", encoding="utf-8").write(c3)
print("OK server-registries.blade")
