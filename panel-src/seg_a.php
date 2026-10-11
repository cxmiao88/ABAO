// ABao: unified panel login (阶段 3.1). Public: health seeds cookies, login
// verifies mdserver-web credentials server-side then logs in the Coolify admin.
Route::get('/panel-api/health', [\App\Http\Controllers\Panel\PanelApiController::class, 'health'])->name('panel-api.health');
Route::post('/panel-api/login', [\App\Http\Controllers\Panel\PanelApiController::class, 'login'])->name('panel-api.login');
