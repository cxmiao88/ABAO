import io, re
s = io.open(r'G:\xianmu\juqing\baoUIIT\resources\views\components\reicon.blade.php', encoding='utf-8').read()
names = re.findall(r"'([a-z0-9-]+)' =>", s)
print(' | '.join(names))
