#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

enp = r"G:\xianmu\juqing\baoUIIT\lang\en.json"
zhp = r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json"

EN = {
    "sec_title": "API Tokens | Coolify", "sec_api_disabled": "API disabled",
    "sec_api_disabled_desc": "Enable the Coolify API before creating access tokens.",
    "sec_api_off": "API access is turned off", "sec_api_off_desc": "Enable API access in instance settings to issue tokens.",
    "sec_open_settings": "Open settings", "sec_never": "Never", "sec_new_token": "New API token",
    "sec_create_token": "Create token", "sec_description": "Description", "sec_ci_token_placeholder": "CI deployment token",
    "sec_expires_in": "Expires in", "sec_permissions": "Permissions",
    "sec_permissions_helper": "Only grant the abilities this token needs.",
    "sec_selected_permissions": "Selected permissions:", "sec_root": "Root",
    "sec_root_helper": "Full access to every API operation.", "sec_write": "Write",
    "sec_write_helper": "Create and update resources.", "sec_deploy": "Deploy",
    "sec_deploy_helper": "Trigger deployments through webhooks.", "sec_read": "Read",
    "sec_read_helper": "Read non-sensitive resource data.", "sec_read_sensitive": "Read sensitive data",
    "sec_read_sensitive_helper": "Include secrets, logs, passwords, and Compose content.",
    "sec_copy_token": "Copy your token", "sec_copy_token_desc": "This value will not be shown again after you leave this page.",
    "sec_copy_token_btn": "Copy token", "sec_issued_tokens": "Issued tokens", "sec_search_tokens": "Search tokens",
    "sec_clear_search": "Clear search", "sec_no_tokens": "No API tokens",
    "sec_no_tokens_desc": "Create a token when an external client needs access.",
    "sec_last_used": "Last used", "sec_created": "Created", "sec_expires": "Expires",
    "sec_expired": "Expired", "sec_confirm_revoke": "Confirm API Token Revocation?",
    "sec_revoke_desc": "This API token will be permanently revoked.",
    "sec_confirm_token_label": "Enter the token description to confirm", "sec_token_desc_short": "Token description",
    "sec_revoke_token": "Revoke token", "sec_revoke": "Revoke", "sec_no_matching": "No matching tokens",
    "sec_no_matching_desc": "Try a different description or permission.",
    "sec_keys_title": "Keys & Tokens | Coolify", "sec_private_keys": "Private keys",
    "sec_private_keys_desc": "SSH keys used to connect servers and private repositories.",
    "sec_confirm_unused": "Confirm unused SSH Key Deletion?",
    "sec_unused_desc": "All unused SSH keys (marked with unused) are permanently deleted.",
    "sec_delete_unused": "Delete unused keys", "sec_delete_unused_short": "Delete unused",
    "sec_new_private_key": "New private key", "sec_new_key_short": "New key",
    "sec_gen_ed25519": "Generate ED25519", "sec_gen_rsa": "Generate RSA",
    "sec_add_manual_title": "Add Private Key Manually", "sec_add_manual": "Add manually",
    "sec_no_keys": "No private keys yet",
    "sec_no_keys_desc": "Generate or add an SSH key to connect Coolify to servers and private repositories.",
    "sec_private_key": "Private key", "sec_status": "Status", "sec_in_use": "In use", "sec_unused": "Unused",
    "sec_edit_key": "Edit private key", "sec_edit_key_aria": "Edit :name",
    "sec_no_permission": "You do not have permission to view this private key", "sec_view_only": "View only",
    "sec_edit_key_title": "Edit Private Key", "sec_loading_editor": "Loading private key editor",
    "sec_public_key": "Public key", "sec_public_key_helper": "Copy this value to ~/.ssh/authorized_keys on the target server.",
    "sec_edit_key_btn": "Edit key", "sec_delete": "Delete", "sec_save_changes": "Save changes",
    "sec_continue": "Continue", "sec_confirm_delete_key": "Confirm Private Key Deletion?",
    "sec_delete_desc": "This private key will be permanently deleted.",
    "sec_delete_desc2": "Servers and Git sources using it will stop working.",
    "sec_delete_key_btn": "Delete private key", "sec_confirm_key_label": "Enter the private key name to confirm deletion",
    "sec_key_name_short": "Private key name", "sec_hide_editor": "Hide editor", "sec_general": "General",
    "sec_deleting_key": "Deleting private key...", "sec_used_by_github": "Used by GitHub App",
    "sec_menu_private_keys": "Private Keys", "sec_menu_cloud_tokens": "Cloud Tokens",
    "sec_menu_integration_tokens": "Integration Tokens", "sec_menu_cloud_init": "Cloud-Init Scripts",
    "sec_menu_api_tokens": "API Tokens", "sec_keys_desc": "Manage SSH keys, cloud credentials, and API access tokens.",
    "sec_keys_aria": "Keys and tokens", "sec_actions": "Actions",
}
ZH = {
    "sec_title": "API 令牌 | Coolify", "sec_api_disabled": "API 已禁用",
    "sec_api_disabled_desc": "创建访问令牌前，请先启用 Coolify API。",
    "sec_api_off": "API 访问已关闭", "sec_api_off_desc": "在实例设置中启用 API 访问以发放令牌。",
    "sec_open_settings": "打开设置", "sec_never": "永不过期", "sec_new_token": "新建 API 令牌",
    "sec_create_token": "创建令牌", "sec_description": "描述", "sec_ci_token_placeholder": "CI 部署令牌",
    "sec_expires_in": "过期时间", "sec_permissions": "权限",
    "sec_permissions_helper": "只授予此令牌所需的能力。",
    "sec_selected_permissions": "已选权限：", "sec_root": "根权限",
    "sec_root_helper": "对所有 API 操作的完全访问权限。", "sec_write": "写入",
    "sec_write_helper": "创建和更新资源。", "sec_deploy": "部署",
    "sec_deploy_helper": "通过 Webhook 触发部署。", "sec_read": "读取",
    "sec_read_helper": "读取非敏感资源数据。", "sec_read_sensitive": "读取敏感数据",
    "sec_read_sensitive_helper": "包含密钥、日志、密码和 Compose 内容。",
    "sec_copy_token": "复制您的令牌", "sec_copy_token_desc": "离开此页面后，该值将不再显示。",
    "sec_copy_token_btn": "复制令牌", "sec_issued_tokens": "已发放的令牌", "sec_search_tokens": "搜索令牌",
    "sec_clear_search": "清除搜索", "sec_no_tokens": "没有 API 令牌",
    "sec_no_tokens_desc": "当外部客户端需要访问时，请创建令牌。",
    "sec_last_used": "上次使用", "sec_created": "创建时间", "sec_expires": "过期时间",
    "sec_expired": "已过期", "sec_confirm_revoke": "确认撤销 API 令牌？",
    "sec_revoke_desc": "此 API 令牌将被永久撤销。",
    "sec_confirm_token_label": "输入令牌描述以确认", "sec_token_desc_short": "令牌描述",
    "sec_revoke_token": "撤销令牌", "sec_revoke": "撤销", "sec_no_matching": "没有匹配的令牌",
    "sec_no_matching_desc": "请尝试不同的描述或权限。",
    "sec_keys_title": "密钥与令牌 | Coolify", "sec_private_keys": "私钥",
    "sec_private_keys_desc": "用于连接服务器和私有仓库的 SSH 密钥。",
    "sec_confirm_unused": "确认删除未使用的 SSH 密钥？",
    "sec_unused_desc": "所有未使用的 SSH 密钥（标记为未使用）将被永久删除。",
    "sec_delete_unused": "删除未使用的密钥", "sec_delete_unused_short": "删除未使用",
    "sec_new_private_key": "新建私钥", "sec_new_key_short": "新建密钥",
    "sec_gen_ed25519": "生成 ED25519", "sec_gen_rsa": "生成 RSA",
    "sec_add_manual_title": "手动添加私钥", "sec_add_manual": "手动添加",
    "sec_no_keys": "还没有私钥",
    "sec_no_keys_desc": "生成或添加 SSH 密钥，将 Coolify 连接到服务器和私有仓库。",
    "sec_private_key": "私钥", "sec_status": "状态", "sec_in_use": "使用中", "sec_unused": "未使用",
    "sec_edit_key": "编辑私钥", "sec_edit_key_aria": "编辑 :name",
    "sec_no_permission": "您没有查看此私钥的权限", "sec_view_only": "仅查看",
    "sec_edit_key_title": "编辑私钥", "sec_loading_editor": "正在加载私钥编辑器",
    "sec_public_key": "公钥", "sec_public_key_helper": "将此值复制到目标服务器上的 ~/.ssh/authorized_keys。",
    "sec_edit_key_btn": "编辑密钥", "sec_delete": "删除", "sec_save_changes": "保存更改",
    "sec_continue": "继续", "sec_confirm_delete_key": "确认删除私钥？",
    "sec_delete_desc": "此私钥将被永久删除。",
    "sec_delete_desc2": "使用它的服务器和 Git 来源将停止工作。",
    "sec_delete_key_btn": "删除私钥", "sec_confirm_key_label": "输入私钥名称以确认删除",
    "sec_key_name_short": "私钥名称", "sec_hide_editor": "隐藏编辑器", "sec_general": "常规",
    "sec_deleting_key": "正在删除私钥...", "sec_used_by_github": "由 GitHub App 使用",
    "sec_menu_private_keys": "私钥", "sec_menu_cloud_tokens": "云令牌",
    "sec_menu_integration_tokens": "集成令牌", "sec_menu_cloud_init": "Cloud-Init 脚本",
    "sec_menu_api_tokens": "API 令牌", "sec_keys_desc": "管理 SSH 密钥、云凭证和 API 访问令牌。",
    "sec_keys_aria": "密钥与令牌", "sec_actions": "操作",
}


def dump(p, d):
    lines = ["{"]
    ks = list(d.keys())
    for i, k in enumerate(ks):
        c = "," if i < len(ks) - 1 else ""
        lines.append('    "%s": %s%s' % (k, json.dumps(d[k], ensure_ascii=False), c))
    lines.append("}")
    open(p, "w", encoding="utf-8").write("\n".join(lines) + "\n")


for p, lang in ((enp, EN), (zhp, ZH)):
    d = json.load(open(p, encoding="utf-8"))
    d.update(lang)
    dump(p, d)
    print(p, len(d))
