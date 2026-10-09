import json, sys

path = '/www/server/mdserver-web/plugins/init.json'
try:
    d = json.load(open(path, encoding='utf-8'))
except Exception as e:
    print('open err:', e)
    sys.exit(0)
print('root type:', type(d).__name__)
if isinstance(d, list):
    print('len:', len(d))
    for it in d[:5]:
        print(it)
elif isinstance(d, dict):
    keys = list(d.keys())
    print('keys:', keys[:20])
    for k in keys[:5]:
        v = d[k]
        if isinstance(v, dict):
            print(k, '->', {kk: v[kk] for kk in list(v.keys())[:6]})
        else:
            print(k, '->', v)
