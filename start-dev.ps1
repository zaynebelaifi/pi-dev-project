$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$conda = 'C:/Users/msi/anaconda3/Scripts/conda.exe'
$condaEnv = 'C:/Users/msi/FirstProject/.conda'

Write-Host "Starting Symfony server..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd `"$root`"; symfony server:start"

Write-Host "Starting Messenger worker..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd `"$root`"; php bin/console messenger:consume async -vv"

Write-Host "Starting FastAPI (AI feedback)..."
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd `"$root`"; & `"$conda`" run -p `"$condaEnv`" python -m uvicorn ai_feedback.delivery_feedback_ai:app --reload --host 127.0.0.1 --port 8001"

Write-Host "All services launched in new terminals."
