<div align="center">

<img src="./public/coolify-logo.svg" alt="ABao" width="120" />

# ABao 阿宝面板

**一站式服务器管理面板：整合 Coolify 部署自动化 + mdserver-web 主机管理，开箱即用。**

开源 & 永久免费 · 基于 [Apache-2.0](./LICENSE) 许可

![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg) ![Version](https://img.shields.io/badge/Version-v1.0.0-green.svg)

</div>

## ABao 是什么？

**ABao（阿宝面板）** 是一个开源的、可自托管的服务器统一管理面板，把两大开源后端整合进一个现代化前端：

| 后端 | 能力 | 说明 |
| --- | --- | --- |
| **Coolify** | 应用部署自动化 | 类 Heroku/Netlify/Vercel：Git 推送自动部署、数据库托管、300+ 一键服务、SSH 多服务器管理 |
| **mdserver-web** | 主机级宝塔能力 | 网站管理、文件管理、SSL 证书、MySQL/数据库、面板设置、软件商店、监控、插件 |

你只需要一台 Linux 服务器，一条命令即可全自动安装。所有数据都保存在你自己的服务器上。

## 功能一览

- **仪表中台**：服务器/应用/数据库/站点全局概览，状态卡片 + 实时图表
- **应用部署**：从 GitHub/GitLab/Bitbucket/Gitea 构建部署（Nixpacks/Railpack/Dockerfile/Compose/镜像），推送即部署、PR 预览、回滚
- **主机管理**：网站一键建站、文件管理器（在线编辑/压缩/权限）、SSL 证书签发续期、伪静态模板
- **数据库**：MySQL/Postgres/MongoDB/Redis 等创建与管理，phpMyAdmin 免密直达
- **网络**：自定义域名、自动 HTTPS、反向代理、健康检查、容器网络
- **运维**：多服务器管理、部署与运行日志、网页终端（SSH）、资源监控曲线
- **安全**：数据库/存储备份、定时任务、环境变量/密钥、团队权限、面板 API 令牌
- **通知**：邮件/钉钉/企业微信/Webhook 多渠道告警

## 快速开始

在一台 **2 核 2G 及以上**的 Linux 服务器（Ubuntu/Debian/CentOS 等）上执行：

```bash
国内服务器（推荐，jsdelivr CDN）：

```bash
curl -fsSL https://cdn.jsdelivr.net/gh/cxmiao88/ABAO@main/install.sh | bash
```

海外服务器（GitHub 直连）：

```bash
curl -fsSL https://raw.githubusercontent.com/cxmiao88/ABAO/main/install.sh | bash
```
```

脚本会全自动完成：环境检测 → Docker 安装 → 拉取 ABao 源码 → 生成安全配置 → 启动 Coolify 后端 → 部署 mdserver-web → 安装前端面板 → 输出访问地址与初始账号。

> 全程全自动、无交互（`curl | bash` 管道模式亦可）：管理员账号与所有密钥自动随机生成，安装结束一次性显示并存档于服务器本地。也可用环境变量 `ABAO_ADMIN_EMAIL` / `ABAO_ADMIN_PASSWORD` 自定义管理员账号。

### 手动部署（开发模式）

```bash
# 1. 克隆仓库
git clone https://github.com/cxmiao88/ABAO.git && cd ABAO

# 2. 配置环境变量
cp .env.production .env   # 编辑 APP_KEY / DB_PASSWORD / REDIS_PASSWORD / 管理员账号

# 3. 启动 Coolify（含 Postgres、Redis）
docker compose -f docker-compose.prod.yml up -d

# 4. 部署前端（构建后放入容器 public/abao，访问 http://<服务器IP>:8000/abao）
```

## 项目架构

```
┌─────────────────────────────────────────────┐
│              ABao 前端（Vue 3 + Element Plus）│
│          http://<host>:8000/abao             │
└──────────────┬──────────────────────────────┘
               │
   ┌───────────┴───────────┐
   ▼                       ▼
┌──────────────┐   ┌──────────────────┐
│   Coolify    │   │   mdserver-web   │
│  (Laravel)   │   │    (Python)      │
│  :8000       │   │    :48700        │
└──────┬───────┘   └────────┬─────────┘
       │                    │
  PostgreSQL/Redis    MySQL/网站/文件/SSL
```

- **Coolify**：Docker 容器部署（`docker-compose.prod.yml`），端口 8000
- **mdserver-web**：官方脚本安装（`/www/server/mdserver-web`），端口 48700
- **前端**：单页应用，构建产物置于 Coolify 容器 `public/abao`，与后端同源访问

## 账号与安全

- 安装脚本采用**零凭据**设计：仓库内不包含任何默认密码，安装时全自动随机生成
- 面板管理员账号、数据库密码均随机生成，安装结束界面展示一次并存档于服务器本地 `.abao-credentials`（权限 600），请立即保存
- 支持后续通过面板「设置」修改密码、绑定安全入口

## 开发与贡献

- 前端仓库：`mdserver-web-front`（Vue 3 + Vite + Element Plus + ECharts）
- 后端二开：本仓库即 Coolify 完整源码（含 ABao 定制：中文语言包、双后端整合、品牌替换）
- 提交规范：`feature/*` 分支开发，PR 合入 `main`
- 欢迎提 [Issue](https://github.com/cxmiao88/ABAO/issues) 与 PR

## 许可

本项目基于 **Apache License 2.0** 开源。上游组件：
- [Coolify](https://github.com/coollabsio/coolify)（Apache-2.0）
- [mdserver-web](https://github.com/midoks/mdserver-web)（Apache-2.0）

## 支持

- [GitHub Issues](https://github.com/cxmiao88/ABAO/issues)
- 文档与教程见仓库 `docs/` 目录（持续补充中）
