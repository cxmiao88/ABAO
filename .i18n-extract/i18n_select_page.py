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
    "sel_search_resources": ("Search resources", "搜索资源"),
    "sel_filter": ("Filter", "筛选"),
    "sel_resource_type": ("Resource type", "资源类型"),
    "sel_all_resources": ("All resources", "全部资源"),
    "sel_applications": ("Applications", "应用"),
    "sel_databases": ("Databases", "数据库"),
    "sel_services": ("Services", "服务"),
    "sel_all_categories": ("All categories", "全部分类"),
    "sel_search_categories": ("Search categories", "搜索分类"),
    "sel_service_category": ("Service category", "服务分类"),
    "sel_trademarks_title": ("Trademarks policy", "商标声明"),
    "sel_trademarks_desc": ("The respective trademarks mentioned here are owned by the respective companies, and use of them does not imply any affiliation or endorsement.", "此处提及的商标归各公司所有，使用它们不代表任何关联或认可。"),
    "sel_deploy": ("Deploy", "部署"),
    "sel_updated": ("Updated", "更新于"),
    "sel_template_ready": ("Template ready", "模板就绪"),
    "sel_arm_only": ("ARM only", "仅 ARM"),
    "sel_amd_only": ("AMD only", "仅 AMD"),
    "sel_template_fallback": ("Deploy this service with a ready-to-use Coolify template.", "使用现成的 ABao 模板部署此服务。"),
    "sel_docs": ("Docs", "文档"),
    "sel_website": ("Website", "官网"),
    "sel_no_resources": ("No resources found", "未找到资源"),
    "sel_no_resources_desc": ("Try a different search or resource type.", "请尝试其他搜索词或资源类型。"),
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

# ============ Select.php: categories -> [{value,label}] ============
p = r"G:\xianmu\juqing\baoUIIT\app\Livewire\Project\New\Select.php"
c = open(p, encoding="utf-8").read()

old_cat = """        // Extract unique categories from services
        $categories = collect($services)
            ->pluck('category')
            ->filter()
            ->unique()
            ->map(function ($category) {
                // Handle multiple categories separated by comma
                if (str_contains($category, ',')) {
                    return collect(explode(',', $category))->map(fn ($cat) => trim($cat));
                }

                return [$category];
            })
            ->flatten()
            ->unique()
            ->map(function ($category) {
                // Format common acronyms to uppercase
                $acronyms = ['ai', 'api', 'ci', 'cd', 'cms', 'crm', 'erp', 'iot', 'vpn', 'vps', 'dns', 'ssl', 'tls', 'ssh', 'ftp', 'http', 'https', 'smtp', 'imap', 'pop3', 'sql', 'nosql', 'json', 'xml', 'yaml', 'csv', 'pdf', 'sms', 'mfa', '2fa', 'oauth', 'saml', 'jwt', 'rest', 'soap', 'grpc', 'graphql', 'websocket', 'webrtc', 'p2p', 'b2b', 'b2c', 'seo', 'sem', 'ppc', 'roi', 'kpi', 'ui', 'ux', 'ide', 'sdk', 'api', 'cli', 'gui', 'cdn', 'ddos', 'dos', 'xss', 'csrf', 'sqli', 'rce', 'lfi', 'rfi', 'ssrf', 'xxe', 'idor', 'owasp', 'gdpr', 'hipaa', 'pci', 'dss', 'iso', 'nist', 'cve', 'cwe', 'cvss'];
                $lower = strtolower($category);

                if (in_array($lower, $acronyms)) {
                    return strtoupper($category);
                }

                return $category;
            })
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();"""

new_cat = """        // Extract unique categories from services, with a Chinese label for the UI.
        // The value keeps the original English key so category filtering keeps working.
        $categoryLabels = [
            'Mail' => '邮件', 'Networking' => '网络', 'RSS' => 'RSS 订阅', 'ai' => 'AI',
            'analytics' => '分析统计', 'api' => 'API', 'auth' => '身份认证', 'automation' => '自动化',
            'backend' => '后端', 'ci' => 'CI 持续集成', 'cms' => 'CMS 内容管理', 'communication' => '沟通协作',
            'database' => '数据库', 'databases' => '数据库', 'database,observability,developer-tools' => '数据库',
            'developer-tools' => '开发工具', 'development' => '开发工具', 'devtools' => '开发工具',
            'documentation' => '文档', 'email' => '电子邮件', 'family' => '家庭生活', 'finance' => '财务管理',
            'games' => '游戏', 'git' => '代码托管', 'health' => '健康', 'helpdesk' => '客服工单',
            'mcp' => 'MCP', 'media' => '多媒体', 'messaging' => '即时通讯', 'monitoring' => '监控告警',
            'productivity' => '效率工具', 'proxy' => '代理', 'search' => '搜索', 'security' => '安全防护',
            'storage' => '存储', 'vpn' => 'VPN',
        ];
        $categories = collect($services)
            ->pluck('category')
            ->filter()
            ->unique()
            ->map(function ($category) {
                // Handle multiple categories separated by comma
                if (str_contains($category, ',')) {
                    return collect(explode(',', $category))->map(fn ($cat) => trim($cat));
                }

                return [$category];
            })
            ->flatten()
            ->unique()
            ->map(function ($category) use ($categoryLabels) {
                // Format common acronyms to uppercase
                $acronyms = ['ai', 'api', 'ci', 'cd', 'cms', 'crm', 'erp', 'iot', 'vpn', 'vps', 'dns', 'ssl', 'tls', 'ssh', 'ftp', 'http', 'https', 'smtp', 'imap', 'pop3', 'sql', 'nosql', 'json', 'xml', 'yaml', 'csv', 'pdf', 'sms', 'mfa', '2fa', 'oauth', 'saml', 'jwt', 'rest', 'soap', 'grpc', 'graphql', 'websocket', 'webrtc', 'p2p', 'b2b', 'b2c', 'seo', 'sem', 'ppc', 'roi', 'kpi', 'ui', 'ux', 'ide', 'sdk', 'api', 'cli', 'gui', 'cdn', 'ddos', 'dos', 'xss', 'csrf', 'sqli', 'rce', 'lfi', 'rfi', 'ssrf', 'xxe', 'idor', 'owasp', 'gdpr', 'hipaa', 'pci', 'dss', 'iso', 'nist', 'cve', 'cwe', 'cvss'];
                $lower = strtolower($category);
                $value = in_array($lower, $acronyms) ? strtoupper($category) : $category;
                $label = $categoryLabels[$value] ?? $value;

                return ['value' => $value, 'label' => $label];
            })
            ->sortBy('label')
            ->values()
            ->all();"""

if old_cat not in c:
    print("MISS Select.php categories block")
else:
    c = c.replace(old_cat, new_cat)
    open(p, "w", encoding="utf-8").write(c)
    print("OK Select.php categories")

# ============ select.blade.php ============
p2 = r"G:\xianmu\juqing\baoUIIT\resources\views\livewire\project\new\select.blade.php"
b = open(p2, encoding="utf-8").read()
pairs = [
    ('placeholder="Search resources"', 'placeholder="{{ __(\'sel_search_resources\') }}"'),
    ("Filter\n", "{{ __('sel_filter') }}\n"),
    ("Resource type\n", "{{ __('sel_resource_type') }}\n"),
    (":title=\"selectedCategory === '' ? 'All categories' : selectedCategory\"",
     ":title=\"selectedCategoryLabel\""),
    ('x-text="selectedCategory === \'\' ? \'All categories\' : selectedCategory"',
     'x-text="selectedCategoryLabel"'),
    ('placeholder="Search categories"', 'placeholder="{{ __(\'sel_search_categories\') }}"'),
    ('aria-label="Service category"', 'aria-label="{{ __(\'sel_service_category\') }}"'),
    ("<span>All categories</span>", "<span>{{ __('sel_all_categories') }}</span>"),
    ("x-for=\"category in categories.filter(category => categorySearch === '' || category.toLowerCase().includes(categorySearch.toLowerCase()))\"\n                                        :key=\"category\"",
     "x-for=\"category in categories.filter(category => categorySearch === '' || category.label.toLowerCase().includes(categorySearch.toLowerCase()))\"\n                                        :key=\"category.value\""),
    (":aria-selected=\"selectedCategory === category\"\n                                            @click=\"selectedCategory = category; categorySearch = ''; categoryOpen = false\"",
     ":aria-selected=\"selectedCategory === category.value\"\n                                            @click=\"selectedCategory = category.value; categorySearch = ''; categoryOpen = false\""),
    ('<span class="truncate" x-text="category"></span>', '<span class="truncate" x-text="category.label"></span>'),
    ('x-show="selectedCategory === category"', 'x-show="selectedCategory === category.value"'),
    ('<x-callout type="info" title="Trademarks policy" class="mb-4">\n                            The respective trademarks mentioned here are owned by the respective companies, and use of them\n                            does not imply any affiliation or endorsement.\n                        </x-callout>',
     '<x-callout type="info" :title="__(\'sel_trademarks_title\')" class="mb-4">\n                            {{ __(\'sel_trademarks_desc\') }}\n                        </x-callout>'),
    (":aria-label=\"'Deploy ' + service.name\"",
     ":aria-label=\"'{{ __(\"sel_deploy\") }} ' + service.name\""),
    ("<span x-show=\"service.templateLastUpdated\">Updated </span>\n                                                <span x-text=\"service.templateLastUpdated || 'Template ready'\"></span>",
     "<span x-show=\"service.templateLastUpdated\">{{ __('sel_updated') }} </span>\n                                                <span x-text=\"service.templateLastUpdated || '{{ __(\"sel_template_ready\") }}'\"></span>"),
    ("x-text=\"service.arm_only ? 'ARM only' : 'AMD only'\"",
     "x-text=\"service.arm_only ? '{{ __(\"sel_arm_only\") }}' : '{{ __(\"sel_amd_only\") }}'\""),
    ("x-text=\"service.slogan || service.description || 'Deploy this service with a ready-to-use Coolify template.'\"",
     "x-text=\"service.slogan || service.description || '{{ __(\"sel_template_fallback\") }}'\""),
    ("Docs\n", "{{ __('sel_docs') }}\n"),
    ("Website\n", "{{ __('sel_website') }}\n"),
    ("Deploy\n", "{{ __('sel_deploy') }}\n"),
    ('<x-empty title="No resources found" description="Try a different search or resource type."',
     '<x-empty :title="__(\'sel_no_resources\')" :description="__(\'sel_no_resources_desc\')"'),
    ("label: 'All resources'", "label: '{{ __(\"sel_all_resources\") }}'"),
    ("label: 'Applications'", "label: '{{ __(\"sel_applications\") }}'"),
    ("label: 'Databases'", "label: '{{ __(\"sel_databases\") }}'"),
    ("label: 'Services'", "label: '{{ __(\"sel_services\") }}'"),
    # selectedCategoryLabel getter: 加在 categories: [], 后面
    ("categories: [],\n                        loading: false,",
     "categories: [],\n                        get selectedCategoryLabel() {\n                            if (this.selectedCategory === '') return '{{ __(\"sel_all_categories\") }}';\n                            const hit = this.categories.find(c => c.value === this.selectedCategory);\n                            return hit ? hit.label : this.selectedCategory;\n                        },\n                        loading: false,"),
]
miss = 0
for old, new in pairs:
    if old not in b:
        print("MISS blade:", old[:70].replace("\n", "\\n"))
        miss += 1
    b = b.replace(old, new)
open(p2, "w", encoding="utf-8").write(b)
print("OK select.blade, misses:", miss)
