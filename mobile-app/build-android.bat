@echo off
echo ===================================================
echo    بناء تطبيق سوق للأندرويد
echo ===================================================
echo.

echo [1/5] تثبيت الاعتماديات...
call npm install
if %ERRORLEVEL% NEQ 0 (
  echo فشل في تثبيت الاعتماديات!
  pause
  exit /b 1
)
echo تم تثبيت الاعتماديات بنجاح.
echo.

echo [2/5] بناء التطبيق...
call npm run build
if %ERRORLEVEL% NEQ 0 (
  echo فشل في بناء التطبيق!
  pause
  exit /b 1
)
echo تم بناء التطبيق بنجاح.
echo.

echo [3/5] إضافة منصة الأندرويد...
call npx cap add android
if %ERRORLEVEL% NEQ 0 (
  echo فشل في إضافة منصة الأندرويد!
  pause
  exit /b 1
)
echo تم إضافة منصة الأندرويد بنجاح.
echo.

echo [4/5] نسخ ملفات التطبيق إلى مجلد الأندرويد...
call npx cap copy android
if %ERRORLEVEL% NEQ 0 (
  echo فشل في نسخ ملفات التطبيق!
  pause
  exit /b 1
)
echo تم نسخ ملفات التطبيق بنجاح.
echo.

echo [5/5] مزامنة التطبيق مع منصة الأندرويد...
call npx cap sync android
if %ERRORLEVEL% NEQ 0 (
  echo فشل في مزامنة التطبيق!
  pause
  exit /b 1
)
echo تم مزامنة التطبيق بنجاح.
echo.

echo ===================================================
echo تم الانتهاء من بناء التطبيق بنجاح!
echo.
echo الخطوات التالية:
echo 1. قم بفتح مشروع الأندرويد في Android Studio:
echo    npx cap open android
echo.
echo 2. من Android Studio، اختر:
echo    Build -^> Build Bundle(s) / APK(s) -^> Build APK(s)
echo.
echo 3. بعد اكتمال البناء، ستجد ملف APK في:
echo    android\app\build\outputs\apk\debug\app-debug.apk
echo ===================================================

echo هل ترغب في فتح مشروع الأندرويد في Android Studio الآن؟ (Y/N)
set /p choice=
if /i "%choice%"=="Y" (
  call npx cap open android
)

pause
