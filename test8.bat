@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/vendor/phpunit/phpunit/phpunit
php8 "%BIN_TARGET%" %*
