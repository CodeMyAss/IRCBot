@echo off
TITLE PHP
cd /d %~dp0
set PHP=bin\php\php.exe
set FILE=src/Run.php
start "" bin\mintty.exe -h error -t "PHP" %PHP% %FILE% --enable-ansi %*
