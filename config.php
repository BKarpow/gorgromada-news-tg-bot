<?php

define('FILE_JSON_DB', __DIR__ . '/dbNews.json'); // імя файлу для json бази
define('TELEGRAM_TOKEN', '5234818902:AAG1OaZf1nw6aeiTVhO9XJB8TJCdrgu_QmA'); // Токен боту телеграм
define('TELEGRAM_CHAT_ID', '@tester19992'); // Ід чату в телеграмі

$linkRssChanel = 'https://gromada.org.ua/rss/104208/'; // Джерело RSS Новин

if ( empty(TELEGRAM_TOKEN) ) {
    die('Заповніть токен боту Telegram який привязаний до каналу.');
}
