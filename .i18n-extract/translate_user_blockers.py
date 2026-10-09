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
    "user.blocker_root": ("The root user cannot be deleted.", "根用户无法被删除。"),
    "user.blocker_root_team_alone": ("You are the only member of the root team.", "您是根团队的唯一成员。"),
    "user.blocker_delete_team_resources": ("Delete all projects, servers, and Git sources of the team \":team\".", "删除团队“:team”的所有项目、服务器和 Git 代码源。"),
    "user.blocker_promote_owner": ("Make another member of the team \":team\" an admin or owner.", "将团队“:team”的另一名成员设为管理员或所有者。"),
    "user.confirm_sub_cancel": ("The subscription of the team \":team\" will be cancelled immediately. This is required.", "团队“:team”的订阅将立即取消。这是必需的。"),
    "user.confirm_account_deleted": ("Your account will be permanently deleted from Coolify.", "您的账户将从 ABao 中永久删除。"),
    "user.sub_cancel_failed": ("Could not cancel the subscription of the team \":team\". Your account was not deleted. Please try again.", "无法取消团队“:team”的订阅。您的账户尚未删除。请重试。"),
    "user.delete_own_profile": ("Delete your own account from your profile.", "请从您的个人资料中删除自己的账户。"),
    "user.delete_higher_role": ("You cannot delete a user with a higher role in the root team.", "您无法删除在根团队中角色更高的用户。"),
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

# ---- DeleteUserAccount.php ----
p = r"G:\xianmu\juqing\baoUIIT\app\Actions\User\DeleteUserAccount.php"
c = open(p, encoding="utf-8").read()
pairs = [
    ("return ['The root user cannot be deleted.'];",
     "return [__('user.blocker_root')];"),
    ("$blockers[] = 'You are the only member of the root team.';",
     "$blockers[] = __('user.blocker_root_team_alone');"),
    ('$blockers[] = "Delete all projects, servers, and Git sources of the team \\"{$team->name}\\".";',
     '$blockers[] = __(\'user.blocker_delete_team_resources\', [\'team\' => $team->name]);'),
    ('$blockers[] = "Make another member of the team \\"{$team->name}\\" an admin or owner.";',
     '$blockers[] = __(\'user.blocker_promote_owner\', [\'team\' => $team->name]);'),
    ('->map(fn (Team $team): string => "The subscription of the team \\"{$team->name}\\" will be cancelled immediately. This is required.")',
     '->map(fn (Team $team): string => __(\'user.confirm_sub_cancel\', [\'team\' => $team->name]))'),
    ("->push('Your account will be permanently deleted from Coolify.')",
     "->push(__('user.confirm_account_deleted'))"),
    ('throw new RuntimeException("Could not cancel the subscription of the team \\"{$team->name}\\". Your account was not deleted. Please try again.");',
     'throw new RuntimeException(__(\'user.sub_cancel_failed\', [\'team\' => $team->name]));'),
]
for old, new in pairs:
    if old not in c:
        print("MISS-DUA:", old[:70])
    c = c.replace(old, new)
open(p, "w", encoding="utf-8").write(c)
print("OK DeleteUserAccount")

# ---- AdminView.php ----
p2 = r"G:\xianmu\juqing\baoUIIT\app\Livewire\Team\AdminView.php"
c2 = open(p2, encoding="utf-8").read()
pairs2 = [
    ("return 'The root user cannot be deleted.';", "return __('user.blocker_root');"),
    ("return 'Delete your own account from your profile.';", "return __('user.delete_own_profile');"),
    ("return 'You cannot delete a user with a higher role in the root team.';", "return __('user.delete_higher_role');"),
]
for old, new in pairs2:
    if old not in c2:
        print("MISS-AV:", old[:70])
    c2 = c2.replace(old, new)
open(p2, "w", encoding="utf-8").write(c2)
print("OK AdminView")
