import subprocess
import os

def sync_to_container():
    try:
        # 方法1：直接复制文件
        source_file = "g:/xianmu/juqing/baoUIIT/resources/views/components/navbar.blade.php"
        target_path = "/var/www/html/resources/views/components/navbar.blade.php"
        
        print("正在同步 navbar.blade.php 到容器...")
        
        # 使用 docker cp 命令
        cmd = f'docker cp "{source_file}" coolify:{target_path}'
        print(f"执行: {cmd}")
        
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        
        if result.returncode == 0:
            print("文件复制成功！")
            
            # 清理缓存
            cache_commands = [
                'docker exec coolify php artisan config:clear',
                'docker exec coolify php artisan route:clear', 
                'docker exec coolify php artisan view:clear'
            ]
            
            for cmd in cache_commands:
                print(f"执行: {cmd}")
                result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
                if result.returncode == 0:
                    print("✓ 缓存清理成功")
                else:
                    print(f"✗ 缓存清理失败: {result.stderr}")
                    
            print("500 错误修复完成！")
        else:
            print(f"文件复制失败: {result.stderr}")
            
    except Exception as e:
        print(f"同步过程中出错: {e}")

if __name__ == "__main__":
    sync_to_container()