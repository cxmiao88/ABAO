import json, base64

with open('g:/xianmu/juqing/baoUIIT/templates/service-templates.json') as f:
    d = json.load(f)

for name in ['uptime-kuma', 'n8n', 'wordpress', 'dify']:
    if name in d:
        t = d[name]
        print('=== ' + name + ' ===')
        print('keys:', list(t.keys()))
        compose = base64.b64decode(t['compose']).decode()
        print('compose (first 800 chars):')
        print(compose[:800])
        print('---')
    else:
        print(name + ': NOT FOUND')