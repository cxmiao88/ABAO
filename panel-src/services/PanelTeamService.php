<?php

namespace App\Services\Panel;

use App\Models\Team;
use App\Models\User;

class PanelTeamService
{
    /**
     * 当前用户所属团队列表（脱敏）。
     */
    public function teams(User $user): array
    {
        return $user->teams
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'personal_team' => (bool) $team->personal_team,
                'members_count' => $team->members()->count(),
                'role' => $team->members()->where('user_id', $user->id)->first()?->pivot?->role,
            ])
            ->values()
            ->all();
    }

    /**
     * 团队详情 + 成员（脱敏：无 email_change_code/pivot 敏感字段，role 显式补充）。
     */
    public function team(User $user, int $teamId): array
    {
        $team = $user->teams->where('id', $teamId)->first();
        if (! $team) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Team::class, $teamId);
        }
        $members = $team->members->map(function (User $member) use ($user) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot?->role,
                'is_me' => $member->id === $user->id,
            ];
        })->values();

        return [
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
            'personal_team' => (bool) $team->personal_team,
            'members' => $members,
        ];
    }

    /**
     * O10：添加成员（按邮箱查找已注册用户并 attach role）。
     */
    public function addMember(User $user, int $teamId, array $data): array
    {
        $team = $this->requireTeam($user, $teamId);
        if (! $this->isAdminOrOwner($user, $team)) {
            throw new \InvalidArgumentException('只有管理员或所有者可以管理团队成员');
        }
        $email = trim((string) ($data['email'] ?? ''));
        $role = trim((string) ($data['role'] ?? 'member'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('请输入有效的邮箱地址');
        }
        if (! in_array($role, ['member', 'admin', 'owner'], true)) {
            throw new \InvalidArgumentException('角色必须是 member/admin/owner');
        }
        $member = User::query()->where('email', $email)->first();
        if (! $member) {
            throw new \InvalidArgumentException('该邮箱对应的用户不存在（需先在面板注册）');
        }
        if ($team->members()->where('user_id', $member->id)->exists()) {
            throw new \InvalidArgumentException('该用户已在团队中');
        }
        $team->members()->attach($member->id, ['role' => $role]);

        return ['status' => 'ok', 'member' => ['id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'role' => $role]];
    }

    /**
     * O10：更新成员角色。
     */
    public function updateMember(User $user, int $teamId, int $userId, array $data): array
    {
        $team = $this->requireTeam($user, $teamId);
        if (! $this->isAdminOrOwner($user, $team)) {
            throw new \InvalidArgumentException('只有管理员或所有者可以管理团队成员');
        }
        $role = trim((string) ($data['role'] ?? 'member'));
        if (! in_array($role, ['member', 'admin', 'owner'], true)) {
            throw new \InvalidArgumentException('角色必须是 member/admin/owner');
        }
        if (! $team->members()->where('user_id', $userId)->exists()) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(User::class, $userId);
        }
        $team->members()->updateExistingPivot($userId, ['role' => $role]);
        $member = $team->members()->where('user_id', $userId)->first();

        return ['status' => 'ok', 'member' => ['id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'role' => $role]];
    }

    /**
     * O10：移除成员。
     */
    public function removeMember(User $user, int $teamId, int $userId): array
    {
        $team = $this->requireTeam($user, $teamId);
        if (! $this->isAdminOrOwner($user, $team)) {
            throw new \InvalidArgumentException('只有管理员或所有者可以管理团队成员');
        }
        if ($user->id === $userId) {
            throw new \InvalidArgumentException('不能移除自己');
        }
        if (! $team->members()->where('user_id', $userId)->exists()) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(User::class, $userId);
        }
        $team->members()->detach($userId);

        return ['status' => 'ok', 'message' => '成员已移除'];
    }

    private function requireTeam(User $user, int $teamId): \App\Models\Team
    {
        $team = $user->teams->where('id', $teamId)->first();
        if (! $team) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(\App\Models\Team::class, $teamId);
        }

        return $team;
    }

    private function isAdminOrOwner(User $user, \App\Models\Team $team): bool
    {
        $pivot = $team->members()->where('user_id', $user->id)->first()?->pivot;
        $role = $pivot?->role;

        return in_array($role, ['admin', 'owner'], true);
    }
}
