<?php

define('FILE_JSON_DB', __DIR__ . '/dbNews.json'); // імя файлу для json бази
define('TELEGRAM_TOKEN', ''); // Токен боту телеграм
define('TELEGRAM_CHAT_ID', '@GorGromadaNews'); // Ід чату в телеграмі

$linkRssChanel = 'https://gromada.org...'; // Джерело RSS Новин

if ( empty(TELEGRAM_TOKEN) ) {
    die('Заповніть токен боту Telegram який привязаний до каналу.');
}
