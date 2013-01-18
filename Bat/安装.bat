@ECHO OFF
SETLOCAL
SET batPath=%~dp0

REG ADD "HKEY_CLASSES_ROOT\.buildconfig\shell\Build Files\Command" /d "%batPath%build.bat \"%%1\"" /f

PAUSE