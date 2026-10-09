$ErrorActionPreference = "Continue"
$log = "C:\Windows\Temp\sshd-setup.log"
"=== start $(Get-Date) ===" | Out-File $log
try {
    $cap = Get-WindowsCapability -Online -Name "OpenSSH.Server*"
    $cap | Out-File $log -Append
    if ($cap.State -ne "Installed") {
        Add-WindowsCapability -Online -Name "OpenSSH.Server~~~~0.0.1.0" | Out-File $log -Append
    }
} catch { "cap error: $_" | Out-File $log -Append }

try {
    Set-Service sshd -StartupType Automatic -ErrorAction Stop
    Start-Service sshd -ErrorAction Stop
    "sshd started: $(Get-Service sshd).Status" | Out-File $log -Append
} catch { "service error: $_" | Out-File $log -Append }

# 防火墙放行 22
try {
    if (-not (Get-NetFirewallRule -DisplayName "*OpenSSH*" -ErrorAction SilentlyContinue)) {
        New-NetFirewallRule -Name "OpenSSH-Server-In-TCP" -DisplayName "OpenSSH Server (sshd)" -Enabled True -Direction Inbound -Protocol TCP -Action Allow -LocalPort 22 | Out-File $log -Append
    }
} catch { "fw error: $_" | Out-File $log -Append }

"=== done $(Get-Date) ===" | Out-File $log -Append
