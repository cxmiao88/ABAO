<?php

namespace App\Services\Panel;

use App\Models\Environment;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class PanelProjectService
{
    /**
     * 项目列表（含环境）。
     */
    public function projects(int $teamId): array
    {
        return Project::whereTeamId($teamId)
            ->select('id', 'name', 'description', 'uuid')
            ->get()
            ->map(fn (Project $p) => [
                'uuid' => $p->uuid,
                'name' => $p->name,
                'description' => $p->description,
                'environments' => $p->environments()->get(['uuid', 'name'])->map(fn ($e) => [
                    'uuid' => $e->uuid,
                    'name' => $e->name,
                ])->values(),
            ])
            ->values()
            ->all();
    }

    /**
     * 创建项目（name 必填 + 团队 scope）。
     */
    public function create(array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name', 'description']);
        if (blank(data_get($data, 'name'))) {
            throw ValidationException::withMessages(['name' => 'name is required.']);
        }
        if (mb_strlen($data['name']) > 255) {
            throw ValidationException::withMessages(['name' => 'The name field must not be greater than 255 characters.']);
        }

        $project = Project::create([
            'name' => $data['name'],
            'description' => data_get($data, 'description'),
            'team_id' => $teamId,
        ]);

        return ['uuid' => $project->uuid];
    }

    /**
     * 更新项目（name/description）。
     */
    public function update(string $uuid, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name', 'description']);
        $project = Project::whereTeamId($teamId)->whereUuid($uuid)->firstOrFail();
        if (array_key_exists('name', $data)) {
            $project->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $project->description = $data['description'];
        }
        $project->save();

        return ['uuid' => $project->uuid];
    }

    /**
     * 删除项目（有资源时 400，与官方一致）。
     */
    public function destroy(string $uuid, int $teamId): array
    {
        $project = Project::whereTeamId($teamId)->whereUuid($uuid)->firstOrFail();
        if (! $project->isEmpty()) {
            return ['status' => 'error', 'message' => 'Project has resources, so it cannot be deleted.', 'http' => 400];
        }
        $project->delete();

        return ['status' => 'ok', 'message' => 'Project deleted.'];
    }

    /**
     * 创建环境（重名 409，与官方一致）。
     */
    public function createEnvironment(string $projectUuid, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name']);
        if (blank(data_get($data, 'name'))) {
            throw ValidationException::withMessages(['name' => 'name is required.']);
        }
        $project = Project::whereTeamId($teamId)->whereUuid($projectUuid)->firstOrFail();

        $existing = $project->environments()->where('name', $data['name'])->first();
        if ($existing) {
            return ['status' => 'error', 'message' => 'Environment with this name already exists.', 'http' => 409];
        }

        $env = $project->environments()->create(['name' => $data['name']]);

        return ['status' => 'ok', 'uuid' => $env->uuid];
    }

    /**
     * 更新环境。
     */
    public function updateEnvironment(string $projectUuid, string $envUuid, array $data, int $teamId): array
    {
        $this->assertAllowed($data, ['name']);
        $project = Project::whereTeamId($teamId)->whereUuid($projectUuid)->firstOrFail();
        $env = $project->environments()->whereUuid($envUuid)->firstOrFail();
        if (array_key_exists('name', $data)) {
            $existing = $project->environments()->where('name', $data['name'])->where('id', '!=', $env->id)->first();
            if ($existing) {
                return ['status' => 'error', 'message' => 'Environment with this name already exists.', 'http' => 409];
            }
            $env->name = $data['name'];
            $env->save();
        }

        return ['status' => 'ok', 'uuid' => $env->uuid];
    }

    /**
     * 删除环境。
     */
    public function destroyEnvironment(string $projectUuid, string $envUuid, int $teamId): array
    {
        $project = Project::whereTeamId($teamId)->whereUuid($projectUuid)->firstOrFail();
        $env = $project->environments()->whereUuid($envUuid)->firstOrFail();
        if ($env->applications()->exists() || $env->services()->exists() || $env->postgresqls()->exists() || $env->mysqls()->exists() || $env->redis()->exists() || $env->mongodbs()->exists() || $env->mariadbs()->exists()) {
            return ['status' => 'error', 'message' => 'Environment has resources, so it cannot be deleted.', 'http' => 400];
        }
        $env->delete();

        return ['status' => 'ok', 'message' => 'Environment deleted.'];
    }

    private function assertAllowed(array $data, array $allowed): void
    {
        $extra = array_diff(array_keys($data), $allowed);
        if (! empty($extra)) {
            throw ValidationException::withMessages([implode(',', $extra) => 'This field is not allowed.']);
        }
    }
}
