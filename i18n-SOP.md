# Coolify i18n 二开标准作业流程（SOP）

> 适用：对自部署二开 Coolify 的中文翻译工作。**禁止拉取/升级官方镜像**（已二开，容器代码以本地仓库为权威）。

## 0. 核心原则

- **本地仓库是唯一权威源码**：`G:\xianmu\juqing\baoUIIT`（WSL 路径 `/mnt/g/xianmu/juqing/baoUIIT`）。
- **容器 ≠ 本地**：容器是官方镜像基线 + 逐次 docker cp 同步的文件。镜像版本旧（4.3.23），本地二开源码较新，二者代码可能不一致。
- 页面出现 500 时，按"组件 → PHP 类 → 路由 → 视图 → 数据库迁移"逐层排查补齐（见第 6 步）。
- 每次同步后必须**清缓存 + 浏览器实测**才算验证通过。

## 1. 盘点页面

```bash
# 浏览器（bu 平面）逐页访问，抓文本记录英文残留：
#   bu.navigate(url); bu.get_page_text()
# 本地定位英文所在文件：
grep -rn "英文原文" resources/views   # PowerShell: Select-String -Path ... -Pattern ...
```

盘点清单（当前状态 2026-10-07）：
| 模块 | 页面 | 状态 |
|---|---|---|
| 仪表盘/项目/服务器列表 | /、/projects、/servers | 已中文，残留 localhost 描述 |
| 应用模块 | configuration/deployment/advanced/domains/analytics/rollback 等 | 已中文，残留少量（见下） |
| 共享变量 | /shared-variables* | 未翻译 |
| 代码源 | /sources | 未翻译 |
| 部署目标 | /destinations | 未翻译 |
| S3 存储 | /storages | 未翻译 |
| 团队 | /team | 未翻译 |
| 通知 | /notifications/* | 未翻译 |
| 密钥与令牌 | /security/* | 未翻译 |
| 标签 | /tags | 未翻译 |
| 设置 | /settings* | 未翻译 |
| 通用组件 | unsaved-bar / upgrade / localhost 描述 / 分页等 | 未翻译 |

## 2. 翻译（只改本地）

- 可见英文 → `__('module.key')`
- 规则：
  - `title=""` / `label=""` / `helper=""` / `description=""` 属性内：`{{ __('application.xxx') }}`（不再加外层引号）
  - PHP 表达式（三元/match）：`__('application.xxx')`
  - helper 含 HTML：`{!! __('application.xxx') !!}`
  - 带参数：key 内用 `:param` 占位，调用 `['param' => $value]`
- key 命名前缀：`gen_`(general) `src_`(source) `adv_`(advanced) `an_`(analytics) `bk_`(backup) `dep_`(deployment) `rb_`(rollback) `pv_`(previews) `pvd_`(preview-domains) `dom_`(domains) `dns_`(domain-row) `srv_`(server-status) `swarm_` `cfg_`(configuration) `int_`(internal-access) `nav_`(deployment-navbar) `ub_`(unsaved-bar) 等
- `lang/en.json` 加英文 key、`lang/zh-cn.json` 加中文 key（两文件键集必须一致）

## 3. 校验（本地）

```bash
python .i18n-extract/verify_all_keys.py   # 全库 blade 引用 key 缺失必须 = 0
python .i18n-extract/verify_lang.py       # en/zh 键集一致、JSON 合法
```

## 4. 同步容器

```bash
# blade + lang：
docker cp <file> coolify:/var/www/html/<file>
# 代码与镜像版本不一致时（曾发生），整目录同步：
docker cp app/. coolify:/var/www/html/app/            # PHP 类/trait
docker cp routes/. coolify:/var/www/html/routes/      # 路由
docker cp resources/views/. coolify:/var/www/html/resources/views/  # 视图
docker cp database/migrations/. coolify:/var/www/html/database/migrations/  # 迁移
docker exec coolify php artisan view:clear
docker exec coolify php artisan route:clear   # 改了 routes 时
docker exec coolify php artisan config:clear  # 改了 config 时
```

## 5. 浏览器验证（必须）

```python
bu.navigate("目标URL"); bu.wait_for_load(15)
bu.get_page_text()   # 确认中文出现、英文消失、无 500
```

## 6. 500 排查链（按序）

| 报错特征 | 处理 |
|---|---|
| Unable to locate class or view for component [xxx] | docker cp 本地 resources/views/components 进容器 |
| Trait/Class "App\\..." not found | docker cp 本地 app/ 整目录 |
| Route [...] not defined | docker cp 本地 routes/ + route:clear |
| SQLSTATE ... table does not exist | docker cp database/migrations + `php artisan migrate --force` |
| Property [...] not found on component | docker cp 本地 app/Livewire（旧类缺新属性） |

## 7. 收尾

- 记录：改动文件清单 + 验证页面 + 残留说明
- 不提交 git（用户二开进行中，除非用户要求）
