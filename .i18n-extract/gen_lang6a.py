#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

enp = r"G:\xianmu\juqing\baoUIIT\lang\en.json"
zhp = r"G:\xianmu\juqing\baoUIIT\lang\zh-cn.json"

EN = {
    "auth2_signin_desc": "Sign in to manage your applications and infrastructure.",
    "auth2_or_continue_with": "Or continue with", "auth2_new_to_coolify": "New to Coolify?",
    "auth2_root_desc": "Create the root account for this instance.",
    "auth2_create_desc": "Create your account to get started.",
    "auth2_full_access": "Full instance access", "auth2_root_user": "This first account becomes the root user.",
    "auth2_password_guidance": "Use at least 8 characters with uppercase, lowercase, number, and symbol.",
    "auth2_create_account": "Create account", "auth2_already_account": "Already have an account?",
    "auth2_forgot_desc": "Enter your account email and we\u2019ll send you a secure reset link.",
    "auth2_txn_not_configured": "Transactional email is not configured",
    "auth2_txn_guide_pre": "Configure email delivery or follow the",
    "auth2_txn_guide_link": "manual reset guide", "auth2_txn_guide_post": ".",
    "auth2_remember_password": "Remember your password?", "auth2_back_to_login": "Back to login",
    "auth2_reset_desc": "Choose a strong new password for your Coolify account.",
    "auth2_confirm_desc": "Confirm your password to continue to this secure area.",
    "auth2_confirm_guidance": "This is a secure area. Please confirm your password before continuing.",
    "auth2_2fa_desc": "Verify your identity to finish signing in.",
    "auth2_2fa_code": "Enter the 6-digit code from your authenticator app.",
    "auth2_2fa_recovery": "Enter one of the recovery codes you saved when setting up two-factor authentication.",
    "auth2_2fa_aria": "Two-factor authentication code",
    "auth2_use_recovery": "Use a recovery code", "auth2_use_authenticator": "Use an authenticator code",
    "auth2_verify_continue": "Verify and continue", "auth2_not_account": "Not your account?",
    "auth2_verify_desc": "Verify your email address to activate your account.",
    "auth2_verify_sent": "We sent a verification link to your email address. Open it to continue to Coolify.",
    "auth2_verify_not_received": "Didn\u2019t receive the email?",
    "auth2_verify_check_spam": "Check your spam folder or resend it.",
}
ZH = {
    "auth2_signin_desc": "登录以管理您的应用和基础设施。",
    "auth2_or_continue_with": "或使用以下方式继续", "auth2_new_to_coolify": "Coolify 新用户？",
    "auth2_root_desc": "为此实例创建根账户。", "auth2_create_desc": "创建您的账户以开始使用。",
    "auth2_full_access": "完整实例访问权限", "auth2_root_user": "此首个账户将成为根用户。",
    "auth2_password_guidance": "请使用至少 8 个字符，包含大写、小写、数字和符号。",
    "auth2_create_account": "创建账户", "auth2_already_account": "已有账户？",
    "auth2_forgot_desc": "输入您的账户邮箱，我们将向您发送安全的重置链接。",
    "auth2_txn_not_configured": "未配置事务邮件",
    "auth2_txn_guide_pre": "请配置邮件发送，或按照", "auth2_txn_guide_link": "手动重置指南", "auth2_txn_guide_post": "操作。",
    "auth2_remember_password": "记得密码？", "auth2_back_to_login": "返回登录",
    "auth2_reset_desc": "为您的 Coolify 账户设置一个新的强密码。",
    "auth2_confirm_desc": "确认您的密码以继续进入此安全区域。",
    "auth2_confirm_guidance": "这是安全区域。请先确认您的密码再继续。",
    "auth2_2fa_desc": "验证您的身份以完成登录。",
    "auth2_2fa_code": "输入您的身份验证器应用中的 6 位代码。",
    "auth2_2fa_recovery": "输入您在设置双重身份验证时保存的恢复代码之一。",
    "auth2_2fa_aria": "双重身份验证代码",
    "auth2_use_recovery": "使用恢复代码", "auth2_use_authenticator": "使用身份验证器代码",
    "auth2_verify_continue": "验证并继续", "auth2_not_account": "不是您的账户？",
    "auth2_verify_desc": "验证您的邮箱地址以激活账户。",
    "auth2_verify_sent": "我们已向您的邮箱发送验证链接。打开它以继续使用 Coolify。",
    "auth2_verify_not_received": "没有收到邮件？",
    "auth2_verify_check_spam": "请检查垃圾邮件文件夹或重新发送。",
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
