D:
cd \Projekty\d24lite\workers\src
copy game.php ..\deployed\game-20005.php
xcopy inc ..\deployed\inc /e /f /y

D:
cd \xampp\php\
php.exe D:\Projekty\d24lite\workers\deployed\game-20005.php 20005

pause