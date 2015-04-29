D:
cd \Projekty\d24lite\workers\src
copy game.php ..\deployed\game-20007.php
xcopy inc ..\deployed\inc /e /f /y

D:
cd \xampp\php\
php.exe D:\Projekty\d24lite\workers\deployed\game-20007.php 20007

pause