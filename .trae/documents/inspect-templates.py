import json, base64

with open('/opt/coolify-src/templates/service-templates.json') as f:
    d = json.load(f)

for name in ['uptime-kuma', 'n8n', 'wordpress', 'dify']:
    if name in d:
        t = d[name]
        print('=== ' + name + ' ===')
        print('keys:', list(t.keys()))
        compose = base64.b64decode(t['compose']).decode()
        print(compose[:1200])
        print('---')
    else:
        print(name + ': NOT FOUND')
