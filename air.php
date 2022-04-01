<?php 

include './helpers.php';

mainAirAlarm();


$_PASS_HASH = 'eb63ce7be0623945a27dc91a882dc409';
$cookieName = 'secureAccess';
$cookieValue = (isset($_COOKIE[$cookieName])) ? $_COOKIE[$cookieName] : false;
$openAccess = false;
if ($cookieValue && md5($cookieValue) == $_PASS_HASH) {
    $openAccess = true;
    if (!empty($_POST['alarm'])) {
        if ($_POST['alarm'] == 'start') {
            airAlarm(true);
        } elseif ($_POST['alarm'] == 'stop') {
            airAlarm(false);
        }
        header('Location: '. $_SERVER['PHP_SELF']);
        die();
    }
} else {
    $openAccess = false;
    if (!empty($_POST['access'])) {
        setcookie($cookieName, $_POST['access'], 0x7fffffff, "", "", true, true);
        header('Location: '. $_SERVER['PHP_SELF']);
        die();
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Повітряна тривога</title>
    <style>
        html{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        form {
            display: block;
            padding-top: .7rem;
            padding-bottom: .7rem;
            font-family: sans-serif;

        }
        .btn {
            width: 100%;
            padding: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            border: none;
            border-radius: 1rem;
            outline: none;
        }
        .air-start{
            background-color: red;
            color: white;
        }
        .air-stop{
            background-color: green;
            color: white;
        }
        h2{
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Управління повітряною тривогою в каналі Городище</h2>
    <?php if ($openAccess) { ?>
    <form action="" method="POST">
        <input type="hidden" name="alarm" value="start">
        <button type="submit" class="btn air-start"> ТРИВОГА!!! </button>
    </form>

    <form action="" method="POST">
        <input type="hidden" name="alarm" value="stop">
        <button type="submit" class="btn air-stop"> ВІДБІЙ!!! </button>
    </form>
    <?php } else { ?>
        <form action="" method="POST">
            <input type="password" name="access" require placeholder="Пароль для доступу">
            <button type="submit">Надати доступ</button>
        </form>
    <?php } ?>
</body>
</html>