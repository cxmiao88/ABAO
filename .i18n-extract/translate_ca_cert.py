#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json, os

def dump(p, d):
    keys = sorted(d.keys())
    lines = ["{"]
    for i, k in enumerate(keys):
        c = "," if i < len(keys) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")

KEYS = {
    "sec_ca_title": ("CA Certificate", "CA 证书"),
    "sec_ca_section": ("CA certificate", "CA 证书"),
    "sec_ca_helper": ("Manage the certificate authority used to sign database certificates on this server.", "管理用于签署此服务器上数据库证书的证书颁发机构。"),
    "sec_ca_status_valid": ("Valid", "有效"),
    "sec_ca_status_expiring": ("Expiring soon", "即将过期"),
    "sec_ca_using": ("Using this certificate", "使用此证书"),
    "sec_ca_using_desc": ("Mount the CA certificate into containers that connect to databases over SSL. Re-deploy affected databases and resources after replacing or regenerating it.", "将 CA 证书挂载到通过 SSL 连接数据库的容器中。替换或重新生成后，请重新部署受影响的数据库和资源。"),
    "sec_ca_read_ssl_guide": ("Read the SSL guide.", "阅读 SSL 指南。"),
    "sec_ca_bind_mount": ("Read-only bind mount", "只读绑定挂载"),
    "sec_ca_content": ("Certificate content", "证书内容"),
    "sec_ca_content_helper": ("Review or replace the PEM certificate stored on this server.", "查看或替换存储在此服务器上的 PEM 证书。"),
    "sec_ca_hide": ("Hide certificate", "隐藏证书"),
    "sec_ca_show": ("Show certificate", "显示证书"),
    "sec_ca_confirm_change": ("Confirm changing of CA Certificate?", "确认更换 CA 证书？"),
    "sec_ca_save": ("Save certificate", "保存证书"),
    "sec_ca_save_action1": ("This overwrites /data/coolify/ssl/coolify-ca.crt with your custom certificate.", "这会用您的自定义证书覆盖 /data/coolify/ssl/coolify-ca.crt。"),
    "sec_ca_save_action2": ("Database certificates on this server will be regenerated and signed with the custom CA.", "此服务器上的数据库证书将重新生成，并使用自定义 CA 签名。"),
    "sec_ca_redeploy": ("You must redeploy affected databases and resources.", "您必须重新部署受影响的数据库和资源。"),
    "sec_ca_path_label": ("CA Certificate Path", "CA 证书路径"),
    "sec_ca_confirm_regenerate": ("Confirm Regenerate Certificate?", "确认重新生成证书？"),
    "sec_ca_regenerate": ("Regenerate", "重新生成"),
    "sec_ca_regenerate_action1": ("This replaces the current CA certificate with a newly generated certificate.", "这将用新生成的证书替换当前的 CA 证书。"),
    "sec_ca_regenerate_action2": ("Database certificates on this server will be regenerated and signed with the new CA.", "此服务器上的数据库证书将重新生成，并使用新 CA 签名。"),
    "sec_ca_regenerate_btn": ("Regenerate Certificate", "重新生成证书"),
    "sec_ca_pem_label": ("PEM certificate", "PEM 证书"),
    "sec_ca_pem_placeholder": ("Paste or edit CA certificate content here…", "在此粘贴或编辑 CA 证书内容…"),
    "sec_ca_hidden": ("Certificate hidden", "证书已隐藏"),
    "sec_ca_hidden_desc": ("Show the certificate to review or edit its contents.", "显示证书以查看或编辑其内容。"),
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

# ---- CA 证书页替换 ----
p = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\server\ca-certificate\show.blade.php"
c = open(p, encoding="utf-8").read()
pairs = [
    ("{{ data_get_str($server, 'name')->limit(10) }} > CA Certificate | ABao",
     "{{ data_get_str($server, 'name')->limit(10) }} > {{ __('sec_ca_title') }} | ABao"),
    ('<x-application.settings-section id="server-ca-overview-section" title="CA certificate"\n                helper="Manage the certificate authority used to sign database certificates on this server.">',
     '<x-application.settings-section id="server-ca-overview-section" title="{{ __(\'sec_ca_section\') }}"\n                helper="{{ __(\'sec_ca_helper\') }}">'),
    ("? 'Expired'\n                                : (now()->addDays(30)->gt($certificateValidUntil) ? 'Expiring soon' : 'Valid')",
     "? __('sec_expired')\n                                : (now()->addDays(30)->gt($certificateValidUntil) ? __('sec_ca_status_expiring') : __('sec_ca_status_valid'))"),
    ('<x-callout type="info" title="Using this certificate">',
     '<x-callout type="info" title="{{ __(\'sec_ca_using\') }}">'),
    ("Mount the CA certificate into containers that connect to databases over SSL. Re-deploy affected\n                    databases and resources after replacing or regenerating it.",
     "{{ __('sec_ca_using_desc') }}"),
    ("Read the SSL guide.", "{{ __('sec_ca_read_ssl_guide') }}"),
    ('<p class="mb-1.5 text-xs font-medium text-neutral-500 dark:text-fg-dim">Read-only bind mount</p>',
     '<p class="mb-1.5 text-xs font-medium text-neutral-500 dark:text-fg-dim">{{ __(\'sec_ca_bind_mount\') }}</p>'),
    ('<x-application.settings-section id="server-ca-content-section" title="Certificate content"\n                helper="Review or replace the PEM certificate stored on this server.">',
     '<x-application.settings-section id="server-ca-content-section" title="{{ __(\'sec_ca_content\') }}"\n                helper="{{ __(\'sec_ca_content_helper\') }}">'),
    ("{{ $showCertificate ? 'Hide certificate' : 'Show certificate' }}",
     "{{ $showCertificate ? __('sec_ca_hide') : __('sec_ca_show') }}"),
    ('<x-modal-confirmation title="Confirm changing of CA Certificate?"\n                                buttonTitle="Save certificate" submitAction="saveCaCertificate" :actions="[\n                                    \'This overwrites /data/coolify/ssl/coolify-ca.crt with your custom certificate.\',\n                                    \'Database certificates on this server will be regenerated and signed with the custom CA.\',\n                                    \'You must redeploy affected databases and resources.\',\n                                ]" confirmationText="/data/coolify/ssl/coolify-ca.crt"\n                                shortConfirmationLabel="CA Certificate Path"\n                                step3ButtonText="Save Certificate" />',
     '<x-modal-confirmation title="{{ __(\'sec_ca_confirm_change\') }}"\n                                buttonTitle="{{ __(\'sec_ca_save\') }}" submitAction="saveCaCertificate" :actions="[\n                                    __(\'sec_ca_save_action1\'),\n                                    __(\'sec_ca_save_action2\'),\n                                    __(\'sec_ca_redeploy\'),\n                                ]" confirmationText="/data/coolify/ssl/coolify-ca.crt"\n                                shortConfirmationLabel="{{ __(\'sec_ca_path_label\') }}"\n                                step3ButtonText="{{ __(\'sec_ca_save\') }}" />'),
    ('<x-modal-confirmation title="Confirm Regenerate Certificate?"\n                                buttonTitle="Regenerate" submitAction="regenerateCaCertificate" :actions="[\n                                    \'This replaces the current CA certificate with a newly generated certificate.\',\n                                    \'Database certificates on this server will be regenerated and signed with the new CA.\',\n                                    \'You must redeploy affected databases and resources.\',\n                                ]" confirmationText="/data/coolify/ssl/coolify-ca.crt"\n                                shortConfirmationLabel="CA Certificate Path"\n                                step3ButtonText="Regenerate Certificate" />',
     '<x-modal-confirmation title="{{ __(\'sec_ca_confirm_regenerate\') }}"\n                                buttonTitle="{{ __(\'sec_ca_regenerate\') }}" submitAction="regenerateCaCertificate" :actions="[\n                                    __(\'sec_ca_regenerate_action1\'),\n                                    __(\'sec_ca_regenerate_action2\'),\n                                    __(\'sec_ca_redeploy\'),\n                                ]" confirmationText="/data/coolify/ssl/coolify-ca.crt"\n                                shortConfirmationLabel="{{ __(\'sec_ca_path_label\') }}"\n                                step3ButtonText="{{ __(\'sec_ca_regenerate_btn\') }}" />'),
    ('rows="15" label="PEM certificate"\n                        placeholder="Paste or edit CA certificate content here…" />',
     'rows="15" label="{{ __(\'sec_ca_pem_label\') }}"\n                        placeholder="{{ __(\'sec_ca_pem_placeholder\') }}" />'),
    ('<p class="mt-3 text-sm font-medium text-neutral-950 dark:text-fg">Certificate hidden</p>',
     '<p class="mt-3 text-sm font-medium text-neutral-950 dark:text-fg">{{ __(\'sec_ca_hidden\') }}</p>'),
    ("Show the certificate to review or edit its contents.",
     "{{ __('sec_ca_hidden_desc') }}"),
]
for old, new in pairs:
    if old not in c:
        print("MISS:", old[:70])
    c = c.replace(old, new)
open(p, "w", encoding="utf-8").write(c)
print("OK ca-certificate")

# ---- status-summary 面包屑状态徽章 ----
p2 = r"G:\xianmu\juqing\baoUIIT\resources\views\components\server\status-summary.blade.php"
c2 = open(p2, encoding="utf-8").read()
pairs2 = [
    ("! $serverReady => ['Unavailable', 'error'],", "! $serverReady => [__('server.status_unavailable'), 'error'],"),
    ("<span>{{ $serverReady ? 'Ready' : 'Unavailable' }}</span>",
     "<span>{{ $serverReady ? __('server.status_ready') : __('server.status_unavailable') }}</span>"),
]
for old, new in pairs2:
    if old not in c2:
        print("MISS2:", old[:70])
    c2 = c2.replace(old, new)
open(p2, "w", encoding="utf-8").write(c2)
print("OK status-summary")
