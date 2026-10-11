// ABao: unified panel login (阶段 3.1). Public: health seeds cookies, login
// verifies mdserver-web credentials server-side then logs in the Coolify admin.
Route::get('/panel-api/health', [\App\Http\Controllers\Panel\PanelApiController::class, 'health'])->name('panel-api.health');
Route::post('/panel-api/login', [\App\Http\Controllers\Panel\PanelApiController::class, 'login'])->name('panel-api.login');
// B03-49：phpMyAdmin signon 令牌校验（公共；token 自证，宿主 signon.php 调用）
Route::get('/panel-api/host/phpmyadmin/resolve/{token}', [\App\Http\Controllers\Panel\PanelApiController::class, 'resolvePmaToken'])->where('token', '[a-f0-9]{48}')->name('panel-api.pma-resolve');
