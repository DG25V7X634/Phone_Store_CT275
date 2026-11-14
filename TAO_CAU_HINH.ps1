$ErrorActionPreference = 'Stop'
$secure = Read-Host 'Nhap mat khau MySQL cua tai khoan root (khong hien ky tu khi go)' -AsSecureString
$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
try {
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    $lines = @(
        'DB_HOST=127.0.0.1',
        'DB_PORT=3306',
        'DB_NAME=phone_store',
        'DB_USER=root',
        ('DB_PASSWORD=' + $password)
    )
    [System.IO.File]::WriteAllLines((Join-Path $PSScriptRoot '.env'), $lines, [System.Text.UTF8Encoding]::new($false))
    Write-Host 'Da tao file .env thanh cong.' -ForegroundColor Green
}
finally {
    if ($ptr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }
    $password = $null
}
