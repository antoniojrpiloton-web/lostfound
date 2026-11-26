@echo off
cd /d "%~dp0"
set BLF_IMG_ENABLE_CLIP=true
echo Starting Boracay Image Validator (FastAPI + OpenCV + OpenCLIP) on http://127.0.0.1:8001 ...
.\.venv\Scripts\uvicorn.exe app.main:app --host 127.0.0.1 --port 8001
pause
