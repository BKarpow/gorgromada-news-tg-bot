# Ридер новостей RSS в Telegram канал

#№ Опис

Це проста систима яка парсить джерело RSS і відправляє нові новини у вказаний телеграм канал.
Також проводится парсинг новин із укр.нет по ключовому слові Городище та знайдені новини теж відправляются в канал.

## Встановлення

В деректорії відкрийте термінал і виконайте цю команду

    composer instal

Створіть бот в телеграмі, додайте його у Ваш канал в ролі адміністатора, вкажіть токен та імя каналу у файлі config.php. Налаштуйте cron на файл cron.php.
