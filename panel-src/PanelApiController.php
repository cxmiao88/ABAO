<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Panel\PanelApplicationService;
use App\Services\Panel\PanelDatabaseService;
use App\Services\Panel\PanelDeploymentService;
use App\Services\Panel\PanelOverviewService;
use App\Services\Panel\PanelProjectService;
use App\Services\Panel\PanelServerService;
use App\Services\Panel\PanelServiceService;
use App\Services\Panel\PanelSettingsService;
use App\Services\Panel\PanelNotificationService;
use App\Services\Panel\PanelCreateService;
use App\Services\Panel\PanelTeamService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ABao panel JSON API consumed by the Vue frontend (baouit-panel-front).
 *
 * Data endpoints are registered inside the auth+verified middleware group in
 * routes/web.php and every query is scoped to the current team.
 * login / health are public (registered outside the auth group).
 */
class PanelApiController extends Controller
{
    public function __construct(
        private PanelOverviewService $service,
        private PanelDeploymentService $deployments,
        private PanelApplicationService $applications,
        private PanelDatabaseService $databases,
        private PanelServerService $servers,
        private PanelServiceService $panelServices,
        private PanelProjectService $panelProjects,
        private PanelTeamService $panelTeams,
        private PanelSettingsService $panelSettings,
        private PanelNotificationService $panelNotifications,
        private PanelCreateService $panelCreate,
    ) {
    }

    /**
     * Public. Seeds session/XSRF cookies and reports backend reachability.
     * The Vue frontend calls this before /login so axios can attach X-XSRF-TOKEN.
     */
    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Public. Unified login (阶段 3.1):
     * 1) verifies the credentials against mdserver-web (/do_login, server-side);
     * 2) on success, logs in the configured Coolify admin (config/panel-api.php),
     *    establishing the Laravel session the rest of /panel-api relies on.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->verifyMdserverCredentials($data['username'], $data['password'])) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = User::query()->where('email', config('panel-api.admin_email'))->first();
        if (! $user) {
            Log::error('panel-api login: configured admin email not found: '.config('panel-api.admin_email'));

            return response()->json(['message' => 'Panel admin not configured.'], 500);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['status' => 'ok', 'user' => ['email' => $user->email]]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'ok']);
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->service->overview());
    }

    public function servers(): JsonResponse
    {
        return response()->json(['servers' => $this->service->servers()]);
    }

    public function server(string $uuid): JsonResponse
    {
        $server = $this->service->server($uuid);
        if (! $server) {
            return response()->json(['message' => 'Server not found.'], 404);
        }

        return response()->json(['server' => $server]);
    }

    /**
     * O7：服务器历史监控曲线（CPU/内存，经 coolify-sentinel history 接口）。
     * 入参 ?mins=N（默认 60，最小 5）；Sentinel 未启用或异常时返回 enabled=false / error。
     */
    public function serverMetrics(Request $request, string $uuid): JsonResponse
    {
        $server = \App\Models\Server::where('uuid', $uuid)->first();
        if (! $server) {
            return response()->json(['message' => 'Server not found.'], 404);
        }
        $mins = max(5, (int) $request->query('mins', 60));
        try {
            if (! $server->isMetricsEnabled()) {
                return response()->json(['enabled' => false, 'cpu' => null, 'memory' => null]);
            }

            return response()->json([
                'enabled' => true,
                'cpu' => $server->getCpuMetrics($mins),
                'memory' => $server->getMemoryMetrics($mins),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'enabled' => true,
                'cpu' => null,
                'memory' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function containers(Request $request): JsonResponse
    {
        return response()->json($this->service->containers($request->query('server')));
    }

    public function applications(): JsonResponse
    {
        return response()->json(['applications' => $this->service->applications()]);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W1：部署操作

    public function deployApplication(Request $request, string $uuid): JsonResponse
    {
        return $this->runDeploymentAction(fn () => $this->deployments->deploy(
            $uuid,
            filter_var($request->input('force_rebuild', false), FILTER_VALIDATE_BOOL)
        ), $uuid);
    }

    public function restartApplication(string $uuid): JsonResponse
    {
        return $this->runDeploymentAction(fn () => $this->deployments->restart($uuid), $uuid);
    }

    public function stopApplication(string $uuid): JsonResponse
    {
        return $this->runDeploymentAction(fn () => $this->deployments->stop($uuid), $uuid);
    }

    public function deployments(string $uuid): JsonResponse
    {
        $result = $this->deployments->deployments($uuid);
        if (isset($result['status']) && $result['status'] === 'not_found') {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['deployments' => $result]);
    }

    public function deployment(string $uuid, string $deploymentUuid): JsonResponse
    {
        $result = $this->deployments->deployment($uuid, $deploymentUuid);
        if (isset($result['status']) && $result['status'] === 'not_found') {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['deployment' => $result]);
    }

    public function rollbackImages(string $uuid): JsonResponse
    {
        $application = \App\Models\Application::ownedByCurrentTeam()->where('uuid', $uuid)->first();
        if (! $application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }
        try {
            $this->authorize('view', $application);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Forbidden: you are not a member of this team.'], 403);
        }

        return response()->json($this->deployments->rollbackImages($uuid));
    }

    public function rollback(string $uuid, Request $request): JsonResponse
    {
        return $this->runDeploymentAction(fn () => $this->deployments->rollback($uuid, (string) $request->input('commit', '')), $uuid);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W2：应用配置

    public function application(string $uuid): JsonResponse
    {
        $result = $this->applications->show($uuid);
        if (isset($result['status']) && $result['status'] === 'not_found') {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['application' => $result]);
    }

    public function updateApplication(Request $request, string $uuid): JsonResponse
    {
        return $this->runApplicationWrite(fn () => $this->applications->update($uuid, $request->all()), $uuid, 'update');
    }

    public function applicationEnvs(string $uuid): JsonResponse
    {
        $result = $this->applications->envs($uuid);
        if (isset($result['status']) && $result['status'] === 'not_found') {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['environment_variables' => $result]);
    }

    public function createApplicationEnv(Request $request, string $uuid): JsonResponse
    {
        return $this->runApplicationWrite(fn () => $this->applications->createEnv($uuid, $request->all()), $uuid, 'update');
    }

    public function updateApplicationEnv(Request $request, string $uuid, string $envUuid): JsonResponse
    {
        return $this->runApplicationWrite(fn () => $this->applications->updateEnv($uuid, $envUuid, $request->all()), $uuid, 'update');
    }

    public function deleteApplicationEnv(string $uuid, string $envUuid): JsonResponse
    {
        return $this->runApplicationWrite(fn () => $this->applications->deleteEnv($uuid, $envUuid), $uuid, 'update');
    }

    public function bulkImportApplicationEnvs(Request $request, string $uuid): JsonResponse
    {
        return $this->runApplicationWrite(fn () => $this->applications->bulkImportEnvs($uuid, $request->all()), $uuid, 'update');
    }

    public function destroyApplication(string $uuid): JsonResponse
    {
        return $this->runApplicationWrite(fn () => $this->applications->destroy($uuid), $uuid, 'delete');
    }

    /**
     * Shared wrapper for application configuration writes: policy check
     * ('update' or 'delete') -> service call -> normalized JSON.
     */
    private function runApplicationWrite(callable $action, string $uuid, string $ability): JsonResponse
    {
        $application = \App\Models\Application::ownedByCurrentTeam()->where('uuid', $uuid)->first();
        if (! $application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        try {
            $this->authorize($ability, $application);
        } catch (\Throwable $e) {
            Log::warning("panel-api application write denied: {$uuid} ({$ability}) ({$e->getMessage()})");

            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        try {
            $result = $action();
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return match ($result['status'] ?? 'ok') {
            'not_found' => response()->json(['message' => $result['message']], 404),
            'validation' => response()->json(['message' => $result['message']], 422),
            'error' => response()->json(['message' => $result['message']], 500),
            default => response()->json($result, 200),
        };
    }

    // ---------------------------------------------------------------- 阶段 3.2 W3：数据库

    public function database(string $uuid): JsonResponse
    {
        $result = $this->databases->show($uuid);
        if (isset($result['status']) && $result['status'] === 'not_found') {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['database' => $result]);
    }

    public function updateDatabase(Request $request, string $uuid): JsonResponse
    {
        return $this->runDatabaseWrite(fn () => $this->databases->update($uuid, $request->all()), $uuid, 'update');
    }

    public function destroyDatabase(string $uuid): JsonResponse
    {
        return $this->runDatabaseWrite(fn () => $this->databases->destroy($uuid), $uuid, 'delete');
    }

    public function databaseBackups(string $uuid): JsonResponse
    {
        $result = $this->databases->backups($uuid);
        if (isset($result['status']) && $result['status'] === 'not_found') {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['backups' => $result]);
    }

    public function createDatabaseBackup(Request $request, string $uuid): JsonResponse
    {
        return $this->runDatabaseWrite(fn () => $this->databases->createBackup($uuid, $request->all()), $uuid, 'manageBackups');
    }

    public function runDatabaseBackup(string $uuid, string $backupUuid): JsonResponse
    {
        return $this->runDatabaseWrite(fn () => $this->databases->backupNow($uuid, $backupUuid), $uuid, 'manageBackups');
    }

    public function deleteDatabaseBackup(string $uuid, string $backupUuid): JsonResponse
    {
        return $this->runDatabaseWrite(fn () => $this->databases->deleteBackup($uuid, $backupUuid), $uuid, 'manageBackups');
    }

    /**
     * Shared wrapper for database writes: policy check -> service call ->
     * normalized JSON.
     */
    private function runDatabaseWrite(callable $action, string $uuid, string $ability): JsonResponse
    {
        $database = queryDatabaseByUuidWithinTeam($uuid, currentTeam()->id);
        if (! $database) {
            return response()->json(['message' => 'Database not found.'], 404);
        }

        try {
            $this->authorize($ability, $database);
        } catch (\Throwable $e) {
            Log::warning("panel-api database write denied: {$uuid} ({$ability}) ({$e->getMessage()})");

            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        try {
            $result = $action();
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return match ($result['status'] ?? 'ok') {
            'not_found' => response()->json(['message' => $result['message']], 404),
            'validation' => response()->json(['message' => $result['message']], 422),
            'error' => response()->json(['message' => $result['message']], 500),
            default => response()->json($result, 200),
        };
    }

    /**
     * Shared wrapper for deploy/restart/stop: policy check -> service call ->
     * normalized JSON (403 on authorization, 404 on unknown app, 409 when the
     * deployment queue is full, 422 when preconditions fail).
     */
    private function runDeploymentAction(callable $action, string $uuid): JsonResponse
    {        $application = \App\Models\Application::ownedByCurrentTeam()->where('uuid', $uuid)->first();
        if (! $application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        try {
            $this->authorize('deploy', $application);
        } catch (\Throwable $e) {
            Log::warning("panel-api deploy action denied: {$uuid} ({$e->getMessage()})");

            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        try {
            $result = $action();
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return match ($result['status'] ?? 'ok') {
            'not_found' => response()->json(['message' => $result['message']], 404),
            'precondition' => response()->json(['message' => $result['message'], 'deployment_uuid' => $result['deployment_uuid'] ?? null], 422),
            'queue_full' => response()->json(['message' => $result['message'], 'deployment_uuid' => $result['deployment_uuid'] ?? null], 409),
            'skipped' => response()->json(['status' => 'skipped', 'message' => $result['message'], 'deployment_uuid' => $result['deployment_uuid'] ?? null], 200),
            default => response()->json(['status' => 'ok', 'deployment_uuid' => $result['deployment_uuid'] ?? null], 200),
        };
    }

    /**
     * Shared wrapper for server writes: policy check -> service call ->
     * normalized JSON (403 on authorization, 404 on unknown server,
     * 422 on validation, 500 on internal errors).
     */
    private function runServerWrite(callable $action, string $uuid, string $ability): JsonResponse
    {
        try {
            $server = \App\Models\Server::whereUuid($uuid)->firstOrFail();
            $this->authorize($ability, $server);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Server not found.'], 404);
        } catch (\Throwable $e) {
            Log::warning("panel-api server write denied: {$uuid} ({$ability}) ({$e->getMessage()})");

            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        try {
            $server = $action();
        } catch (ValidationException $e) {
            $msg = $e->validator->errors()->first();

            return response()->json(['message' => $msg ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Server not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['server' => $this->serializeServer($server)], 200);
    }

    /**
     * W4: server detail (sanitized).
     */
    public function serverShow(string $uuid): JsonResponse
    {
        try {
            $server = $this->servers->show($uuid);
            $this->authorize('view', $server);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Server not found.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        return response()->json(['server' => $this->serializeServer($server)], 200);
    }

    /**
     * W4: update server settings (whitelisted fields, see PanelServerService).
     */
    public function serverUpdate(Request $request, string $uuid): JsonResponse
    {
        return $this->runServerWrite(
            fn () => $this->servers->update($uuid, $request->all()),
            $uuid,
            'update'
        );
    }

    /**
     * W4: trigger connection validation (async ValidateServer job, install=false).
     */
    public function serverValidate(string $uuid): JsonResponse
    {
        return $this->runServerWrite(
            fn () => $this->servers->validate($uuid),
            $uuid,
            'update'
        );
    }

    /**
     * Sanitized server payload for the Vue frontend.
     */
    private function serializeServer(\App\Models\Server $server): array
    {
        $s = $server->settings;
        $reachable = (bool) data_get($s, 'is_reachable');
        $usable = (bool) data_get($s, 'is_usable');
        $settings = $s ? [
            'concurrent_builds' => $s->concurrent_builds,
            'dynamic_timeout' => $s->dynamic_timeout,
            'deployment_queue_limit' => $s->deployment_queue_limit,
            'server_disk_usage_notification_threshold' => $s->server_disk_usage_notification_threshold,
            'server_disk_usage_check_frequency' => $s->server_disk_usage_check_frequency,
            'server_disk_usage_notification_interval_hours' => $s->server_disk_usage_notification_interval_hours,
            'connection_timeout' => $s->connection_timeout,
            'is_terminal_enabled' => (bool) $s->is_terminal_enabled,
            'is_reachable' => $reachable,
            'is_usable' => $usable,
        ] : [];

        return [
            'uuid' => $server->uuid,
            'name' => $server->name,
            'description' => $server->description,
            'ip' => $server->ip,
            'port' => (int) $server->port,
            'user' => $server->user,
            'proxy_type' => str($server->proxyType())->lower()->toString(),
            'status' => $reachable && $usable ? 'running' : ($reachable ? 'unusable' : 'offline'),
            'is_coolify_host' => (bool) $server->is_coolify_host,
            'is_functional' => (bool) $server->isFunctional(),
            'settings' => $settings,
        ];
    }

    public function databases(): JsonResponse
    {
        return response()->json(['databases' => $this->service->databases()]);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W5a：服务商店

    public function serviceTemplates(): JsonResponse
    {
        return response()->json(['templates' => $this->panelServices->templates()]);
    }

    public function services(): JsonResponse
    {
        return response()->json(['services' => $this->panelServices->services(currentTeam()->id)]);
    }

    public function serviceShow(string $uuid): JsonResponse
    {
        try {
            $service = $this->panelServices->show($uuid);
            $this->authorize('view', $service);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Service not found.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        return response()->json(['service' => $this->serializeService($service)], 200);
    }

    public function createService(Request $request): JsonResponse
    {
        try {
            $service = $this->panelServices->create($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            $msg = $e->validator->errors()->first();

            return response()->json(['message' => $msg ?: 'Validation failed.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'service' => $this->serializeService($service->load('applications', 'databases'))], 201);
    }

    public function serviceAction(string $uuid, string $op): JsonResponse
    {
        try {
            $service = \App\Models\Service::whereUuid($uuid)->firstOrFail();
            $this->authorize('deploy', $service);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Service not found.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        try {
            $result = $this->panelServices->action($uuid, $op);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Service not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return match ($result['status'] ?? 'ok') {
            'error' => response()->json(['message' => $result['message']], $result['http'] ?? 500),
            default => response()->json(['status' => 'ok', 'message' => $result['message']], 200),
        };
    }

    public function destroyService(string $uuid): JsonResponse
    {
        try {
            $service = \App\Models\Service::whereUuid($uuid)->firstOrFail();
            $this->authorize('delete', $service);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Service not found.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Forbidden: you are not an admin of this team.'], 403);
        }

        try {
            $result = $this->panelServices->destroy($uuid);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    /**
     * W5a: 项目列表（含环境）——供服务商店部署弹窗选择项目/环境。
     */
    public function projects(): JsonResponse
    {
        return response()->json(['projects' => $this->panelProjects->projects(currentTeam()->id)]);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W5b：项目/环境管理

    public function createProject(Request $request): JsonResponse
    {
        try {
            $result = $this->panelProjects->create($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 201);
    }

    public function updateProject(string $uuid, Request $request): JsonResponse
    {
        try {
            $result = $this->panelProjects->update($uuid, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 200);
    }

    public function destroyProject(string $uuid): JsonResponse
    {
        try {
            $result = $this->panelProjects->destroy($uuid, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 400);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    public function createEnvironment(string $uuid, Request $request): JsonResponse
    {
        try {
            $result = $this->panelProjects->createEnvironment($uuid, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 201);
    }

    public function updateEnvironment(string $uuid, string $envUuid, Request $request): JsonResponse
    {
        try {
            $result = $this->panelProjects->updateEnvironment($uuid, $envUuid, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Environment not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 200);
    }

    public function destroyEnvironment(string $uuid, string $envUuid): JsonResponse
    {
        try {
            $result = $this->panelProjects->destroyEnvironment($uuid, $envUuid, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Environment not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 400);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W5c：团队管理（只读）

    public function teams(): JsonResponse
    {
        return response()->json(['teams' => $this->panelTeams->teams(request()->user())]);
    }

    public function teamShow(int $teamId): JsonResponse
    {
        try {
            $team = $this->panelTeams->team(request()->user(), $teamId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Team not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['team' => $team], 200);
    }

    // ---------------------------------------------------------------- O10：团队成员管理

    public function teamAddMember(int $teamId, Request $request): JsonResponse
    {
        try {
            $result = $this->panelTeams->addMember(request()->user(), $teamId, $request->all());
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Team not found.'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'member' => $result['member']], 201);
    }

    public function teamUpdateMember(int $teamId, int $userId, Request $request): JsonResponse
    {
        try {
            $result = $this->panelTeams->updateMember(request()->user(), $teamId, $userId, $request->all());
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Team or member not found.'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'member' => $result['member']], 200);
    }

    public function teamRemoveMember(int $teamId, int $userId): JsonResponse
    {
        try {
            $result = $this->panelTeams->removeMember(request()->user(), $teamId, $userId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Team or member not found.'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    // ---------------------------------------------------------------- O10：通知 / S3 连通测试

    public function testNotificationChannel(string $channel): JsonResponse
    {
        try {
            $result = $this->panelNotifications->test($channel, currentTeam()->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Unknown notification channel.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['status' => 'ok', 'message' => 'Test notification sent.'], 200);
    }

    public function testS3Storage(string $uuid): JsonResponse
    {
        try {
            $result = $this->panelSettings->testS3Storage($uuid, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'S3 storage not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W6a：共享变量 + 标签

    public function sharedEnvs(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $environmentId = $request->query('environment_id');

        return response()->json(['envs' => $this->panelSettings->sharedEnvs(currentTeam()->id, $type ?: null, $environmentId !== null && $environmentId !== '' ? $environmentId : null)]);
    }

    public function createSharedEnv(Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->createSharedEnv($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'id' => $result['id']], 201);
    }

    public function updateSharedEnv(int $envId, Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->updateSharedEnv($envId, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Shared environment variable not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'id' => $result['id']], 200);
    }

    public function deleteSharedEnv(int $envId): JsonResponse
    {
        try {
            $result = $this->panelSettings->deleteSharedEnv($envId, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Shared environment variable not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    public function tags(): JsonResponse
    {
        return response()->json(['tags' => $this->panelSettings->tags(currentTeam()->id)]);
    }

    public function createTag(Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->createTag($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 201);
    }

    public function updateTag(string $uuid, Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->updateTag($uuid, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Tag not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        if (($result['status'] ?? 'ok') === 'error') {
            return response()->json(['message' => $result['message']], $result['http'] ?? 409);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 200);
    }

    public function deleteTag(string $uuid): JsonResponse
    {
        try {
            $result = $this->panelSettings->deleteTag($uuid, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Tag not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W6b：通知

    public function notifications(): JsonResponse
    {
        return response()->json(['channels' => $this->panelNotifications->channels()]);
    }

    public function notificationChannel(string $channel): JsonResponse
    {
        try {
            return response()->json($this->panelNotifications->channel($channel, currentTeam()->id));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Unknown notification channel.'], 404);
        }
    }

    public function updateNotificationChannel(string $channel, Request $request): JsonResponse
    {
        try {
            $result = $this->panelNotifications->updateChannel($channel, $request->all(), currentTeam()->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Unknown notification channel.'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json($result, 200);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W6b：S3 存储

    public function s3Storages(): JsonResponse
    {
        return response()->json(['storages' => $this->panelSettings->s3Storages(currentTeam()->id)]);
    }

    public function createS3Storage(Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->createS3Storage($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 201);
    }

    public function updateS3Storage(string $uuid, Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->updateS3Storage($uuid, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'S3 storage not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid']], 200);
    }

    public function deleteS3Storage(string $uuid): JsonResponse
    {
        try {
            $result = $this->panelSettings->deleteS3Storage($uuid, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'S3 storage not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    // ---------------------------------------------------------------- 阶段 3.2 W6b：SSH 密钥

    public function sshKeys(): JsonResponse
    {
        return response()->json(['keys' => $this->panelSettings->sshKeys(currentTeam()->id)]);
    }

    public function createSshKey(Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->createSshKey($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid'], 'fingerprint' => $result['fingerprint']], 201);
    }

    public function deleteSshKey(string $uuid): JsonResponse
    {
        try {
            $result = $this->panelSettings->deleteSshKey($uuid, currentTeam()->id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'SSH key not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'message' => $result['message']], 200);
    }

    public function updateSshKey(string $uuid, Request $request): JsonResponse
    {
        try {
            $result = $this->panelSettings->updateSshKey($uuid, $request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first() ?: 'Validation failed.'], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'SSH key not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok', 'uuid' => $result['uuid'], 'fingerprint' => $result['fingerprint']], 200);
    }

    /**
     * 服务脱敏序列化（不含 compose/env/密码）。
     */
    private function serializeService(\App\Models\Service $service): array
    {
        return [
            'uuid' => $service->uuid,
            'name' => $service->name,
            'description' => $service->description,
            'service_type' => $service->service_type,
            'status' => $service->status,
            'environment_id' => $service->environment_id,
            'applications' => $service->applications->map(fn ($a) => [
                'name' => $a->name,
                'status' => $a->status,
                'fqdn' => $a->fqdn,
            ])->values(),
            'databases' => $service->databases->map(fn ($d) => [
                'name' => $d->name,
                'status' => $d->status,
            ])->values(),
        ];
    }

    /**
     * Server-side credential check against mdserver-web. Credentials never leave
     * the server beyond this call and never reach the Vue bundle.
     * Retries once (mdserver /do_login intermittently returns an empty body from
     * the container network; observed in E2E).
     */
    private function verifyMdserverCredentials(string $username, string $password): bool
    {
        $url = rtrim(config('panel-api.mdserver_url'), '/').'/do_login';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $res = Http::asForm()
                    ->timeout(10)
                    ->post($url, [
                        'username' => $username,
                        'password' => $password,
                        'code' => '',
                    ]);

                if ($res->successful()) {
                    $json = $res->json();
                    if (isset($json['status']) && (int) $json['status'] === 1) {
                        return true;
                    }
                    Log::warning("panel-api login: mdserver status not ok (attempt {$attempt}): ".substr($res->body(), 0, 120));
                } else {
                    Log::warning("panel-api login: mdserver http {$res->status()} (attempt {$attempt})");
                }
            } catch (ConnectionException $e) {
                Log::warning("panel-api login: mdserver-web unreachable (attempt {$attempt}): ".$e->getMessage());
            }

            usleep(400_000);
        }

        return false;
    }

    // ===== O2 新建资源（应用 / 服务器 / 数据库）=====
    public function createServer(Request $request): JsonResponse
    {
        try {
            $server = $this->panelCreate->createServer($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'ok',
            'server' => [
                'uuid' => $server->uuid,
                'name' => $server->name,
                'ip' => $server->ip,
                'user' => $server->user,
                'port' => $server->port,
                'server_role' => $server->settings->server_role ?? null,
            ],
        ], 201);
    }

    public function createApplication(Request $request): JsonResponse
    {
        try {
            $application = $this->panelCreate->createApplication($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'ok',
            'application' => [
                'uuid' => $application->uuid,
                'name' => $application->name,
                'fqdn' => $application->fqdn,
                'status' => $application->status,
                'build_pack' => $application->build_pack,
            ],
        ], 201);
    }

    public function createDatabase(Request $request): JsonResponse
    {
        try {
            $database = $this->panelCreate->createDatabase($request->all(), currentTeam()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Internal error: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status' => 'ok',
            'database' => [
                'uuid' => $database->uuid,
                'name' => $database->name,
                'type' => $database->getMorphClass(),
            ],
        ], 201);
    }

    // ===== O1 SSH 网页终端（暴露原生 TerminalSessionService，前端 xterm 直连 WS 6002）=====
    public function terminalIps(): JsonResponse
    {
        $servers = auth()->user()->currentTeam()->servers
            ->where('settings.is_terminal_enabled', true)
            ->values()
            ->map(fn ($s) => [
                'uuid' => $s->uuid,
                'name' => $s->name,
                'ip' => $s->ip,
                'terminal_enabled' => true,
            ]);

        return response()->json(['servers' => $servers]);
    }

    public function terminalToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'server_uuid' => ['required', 'string'],
            'container' => ['nullable', 'string'],
        ]);

        $server = auth()->user()->currentTeam()->servers()->where('uuid', $data['server_uuid'])->firstOrFail();

        if (! $server->isTerminalEnabled() || $server->isForceDisabled()) {
            return response()->json(['status' => false, 'msg' => '该服务器未开启终端或已被禁用'], 403);
        }

        try {
            $token = app(\App\Services\TerminalSessionService::class)
                ->issue(auth()->user(), $server, $data['container'] ?? null);

            return response()->json(['status' => true, 'token' => $token]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'msg' => '终端会话签发失败：'.$e->getMessage()], 500);
        }
    }

    public function terminalSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        try {
            $command = app(\App\Services\TerminalSessionService::class)
                ->redeem(auth()->user(), $data['token']);

            return response()->json(['status' => true, 'command' => $command]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'msg' => $e->getMessage()], 403);
        }
    }
}
