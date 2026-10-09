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
    "pop_realtime_title": ("Cannot connect to real-time service", "无法连接实时服务"),
    "pop_realtime_desc1": ("This will cause unusual problems on the UI. Open the", "这会导致界面出现异常问题。请打开"),
    "pop_realtime_ports": ("required ports", "所需端口"),
    "pop_realtime_desc2": ("or get help on", "或前往"),
    "pop_view_docs": ("View docs", "查看文档"),
    "pop_ack_disable": ("Acknowledge & disable", "确认并禁用"),
    "pop_sponsor_title": ("Love Coolify? Support our work.", "喜欢 ABao？支持我们的工作。"),
    "pop_sponsor_desc": ("Coolify is profitable thanks to <span class=\"font-semibold text-coollabs dark:text-warning\">you</span>. Your support helps us build more features and keep improving the project.",
                         "ABao 的持续运营离不开 <span class=\"font-semibold text-coollabs dark:text-warning\">您</span>。您的支持能帮助我们开发更多功能并持续改进项目。"),
    "pop_sponsor_later": ("Maybe next time", "下次再说"),
    "pop_sub_error": ("Subscription Error.", "订阅错误。"),
    "pop_sub_error_desc": ("Something went wrong. Please try again or", "出错了。请重试或"),
    "pop_sub_error_contact": ("contact support", "联系支持"),
    "pop_welcome": ("Welcome onboard!", "欢迎加入！"),
    "pop_welcome_desc": ("Your subscription has been activated. It could take a few seconds before it is fully active.", "您的订阅已激活。完全生效可能需要几秒钟。"),
    "pop_warning": ("WARNING:", "警告："),
    "pop_sub_overdue_1": ("Your subscription is in over-due. If your latest payment is not paid within a week, all automations", "您的订阅已逾期。如果最新款项未在一周内支付，所有自动化"),
    "pop_will_deactivated": ("will be deactivated", "将被停用"),
    "pop_sub_overdue_2": (". Visit", "。请访问"),
    "pop_sub_overdue_3": ("to check your subscription status or pay your invoice (or check your email for the invoice).", "查看订阅状态或支付账单（或查看邮箱中的账单）。"),
    "pop_server_overflow_1": ("The number of active servers exceeds the limit covered by your payment. If not resolved, some of your servers", "活跃服务器数量超过了您的付费额度。如果未解决，部分服务器"),
    "pop_server_overflow_2": (". Visit", "。请访问"),
    "pop_server_overflow_3": ("to update your subscription or remove some servers.", "更新订阅或移除部分服务器。"),
    "pop_notif_title": ("No notifications enabled", "未启用任何通知"),
    "pop_notif_desc1": ("Enable at least one notification channel so you receive important alerts. Visit", "请至少启用一个通知渠道，以便接收重要告警。前往"),
    "pop_notif_desc2": ("to get started.", "开始配置。"),
    "pop_notif_open": ("Open notifications", "打开通知"),
    "pop_notif_accept": ("Accept and close", "接受并关闭"),
    "pop_notif_link": ("notifications", "通知"),
    "pop_dismiss_sponsorship": ("Dismiss sponsorship reminder", "关闭赞助提醒"),
    "pop_dismiss_notification": ("Dismiss notifications reminder", "关闭通知提醒"),
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

p = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\layout-popups.blade.php"
c = open(p, encoding="utf-8").read()
pairs = [
    ("Cannot connect to real-time service", "{{ __('pop_realtime_title') }}"),
    ("This will cause unusual problems on the UI. Open the\n                                        <a class=\"font-medium text-coollabs underline decoration-coollabs/30 underline-offset-2 transition-colors hover:text-coollabs-100 dark:text-warning dark:decoration-warning/30 dark:hover:text-warning/90\"\n                                            href=\"https://coolify.io/docs/knowledge-base/server/firewall\"\n                                            target=\"_blank\" rel=\"noopener noreferrer\">required ports</a>\n                                        or get help on\n                                        <a class=\"font-medium text-coollabs underline decoration-coollabs/30 underline-offset-2 transition-colors hover:text-coollabs-100 dark:text-warning dark:decoration-warning/30 dark:hover:text-warning/90\"\n                                            href=\"https://coollabs.io/discord\" target=\"_blank\"\n                                            rel=\"noopener noreferrer\">Discord</a>.",
     "{{ __('pop_realtime_desc1') }}\n                                        <a class=\"font-medium text-coollabs underline decoration-coollabs/30 underline-offset-2 transition-colors hover:text-coollabs-100 dark:text-warning dark:decoration-warning/30 dark:hover:text-warning/90\"\n                                            href=\"https://coolify.io/docs/knowledge-base/server/firewall\"\n                                            target=\"_blank\" rel=\"noopener noreferrer\">{{ __('pop_realtime_ports') }}</a>\n                                        {{ __('pop_realtime_desc2') }}\n                                        <a class=\"font-medium text-coollabs underline decoration-coollabs/30 underline-offset-2 transition-colors hover:text-coollabs-100 dark:text-warning dark:decoration-warning/30 dark:hover:text-warning/90\"\n                                            href=\"https://coollabs.io/discord\" target=\"_blank\"\n                                            rel=\"noopener noreferrer\">Discord</a>."),
    ("                                    View docs\n", "                                    {{ __('pop_view_docs') }}\n"),
    ("                                    Acknowledge &amp; disable\n", "                                    {{ __('pop_ack_disable') }}\n"),
    ('<button type="button" aria-label="Dismiss sponsorship reminder"', '<button type="button" aria-label="{{ __(\'pop_dismiss_sponsorship\') }}"'),
    ("Love Coolify? Support our work.", "{{ __('pop_sponsor_title') }}"),
    ("Coolify is profitable thanks to <span\n                                        class=\"font-semibold text-coollabs dark:text-warning\">you</span>. Your support\n                                    helps us build more features and keep improving the project.",
     "{!! __('pop_sponsor_desc') !!}"),
    ("Maybe next time", "{{ __('pop_sponsor_later') }}"),
    ('<span class="font-bold text-red-500">Subscription Error.</span> Something went wrong. Please try\n                    again or <a class="underline dark:text-white"\n                        href="{{ config(\'constants.urls.contact\') }}" target="_blank">contact support</a>.</span>',
     '<span class="font-bold text-red-500">{{ __(\'pop_sub_error\') }}</span> {{ __(\'pop_sub_error_desc\') }}\n                    <a class="underline dark:text-white"\n                        href="{{ config(\'constants.urls.contact\') }}" target="_blank">{{ __(\'pop_sub_error_contact\') }}</a>.</span>'),
    ("window.toast('Welcome onboard!', {\n            type: 'success',\n            description: 'Your subscription has been activated. It could take a few seconds before it is fully active.',",
     "window.toast('{{ __(\"pop_welcome\") }}', {\n            type: 'success',\n            description: '{{ __(\"pop_welcome_desc\") }}',"),
    ('<span class="font-bold text-red-500">WARNING:</span> Your subscription is in over-due. If your\n                latest\n                payment is not paid within a week, all automations <span class="font-bold text-red-500">will\n                    be deactivated</span>. Visit <a href="{{ route(\'subscription.show\') }}" {{ wireNavigate() }}\n                    class="underline dark:text-white">/subscription</a> to check your subscription status or pay\n                your\n                invoice (or check your email for the invoice).',
     '<span class="font-bold text-red-500">{{ __(\'pop_warning\') }}</span> {{ __(\'pop_sub_overdue_1\') }} <span class="font-bold text-red-500">{{ __(\'pop_will_deactivated\') }}</span>{{ __(\'pop_sub_overdue_2\') }} <a href="{{ route(\'subscription.show\') }}" {{ wireNavigate() }}\n                    class="underline dark:text-white">/subscription</a> {{ __(\'pop_sub_overdue_3\') }}'),
    ('<span class="font-bold text-red-500">WARNING:</span> The number of active servers exceeds the limit\n                covered by your payment. If not resolved, some of your servers <span class="font-bold text-red-500">will\n                    be deactivated</span>. Visit <a href="{{ route(\'subscription.show\') }}" {{ wireNavigate() }}\n                    class="underline dark:text-white">/subscription</a> to update your subscription or remove some\n                servers.',
     '<span class="font-bold text-red-500">{{ __(\'pop_warning\') }}</span> {{ __(\'pop_server_overflow_1\') }} <span class="font-bold text-red-500">{{ __(\'pop_will_deactivated\') }}</span>{{ __(\'pop_server_overflow_2\') }} <a href="{{ route(\'subscription.show\') }}" {{ wireNavigate() }}\n                    class="underline dark:text-white">/subscription</a> {{ __(\'pop_server_overflow_3\') }}'),
    ('<button type="button" aria-label="Dismiss notifications reminder"', '<button type="button" aria-label="{{ __(\'pop_dismiss_notification\') }}"'),
    ("No notifications enabled", "{{ __('pop_notif_title') }}"),
    ("Enable at least one notification channel so you receive important alerts.\n                                    Visit\n                                    <a href=\"{{ route('notifications.email') }}\" {{ wireNavigate() }}\n                                        class=\"font-medium text-coollabs underline decoration-coollabs/30 underline-offset-2 transition-colors hover:text-coollabs-100 dark:text-warning dark:decoration-warning/30 dark:hover:text-warning/90\">notifications</a>\n                                    to get started.",
     "{{ __('pop_notif_desc1') }}\n                                    <a href=\"{{ route('notifications.email') }}\" {{ wireNavigate() }}\n                                        class=\"font-medium text-coollabs underline decoration-coollabs/30 underline-offset-2 transition-colors hover:text-coollabs-100 dark:text-warning dark:decoration-warning/30 dark:hover:text-warning/90\">{{ __('pop_notif_link') }}</a>\n                                    {{ __('pop_notif_desc2') }}"),
    ("Open notifications", "{{ __('pop_notif_open') }}"),
    ("Accept and close", "{{ __('pop_notif_accept') }}"),
]
miss = 0
for old, new in pairs:
    if old not in c:
        print("MISS:", old[:60].replace("\n", "\\n"))
        miss += 1
    c = c.replace(old, new)
open(p, "w", encoding="utf-8").write(c)
print("OK layout-popups, misses:", miss)
