@ECHO OFF
SETLOCAL
SET batPath=%~dp0

REG ADD "HKEY_CLASSES_ROOT\.buildconfig" /d "buildconfig_auto_file" /f
REG ADD "HKEY_CLASSES_ROOT\.buildconfig\shell\Build Files\Command" /d "%batPath%build.bat \"%%1\"" /f
REG ADD "HKEY_CLASSES_ROOT\buildconfig_auto_file\shell\edit\command" /d "%batPath%build.bat \"%%1\"" /f
REG ADD "HKEY_CLASSES_ROOT\buildconfig_auto_file\shell\open\command" /d "%batPath%build.bat \"%%1\"" /f

PAUSE