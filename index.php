<?php
require_once "php/riot.php";

define("ROOT_PATH", __DIR__);
$riot = new Riot();
?>
<!DOCTYPE html>
<html>
<head>

    <link href="stylesheets/riot.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div>
        <?php
            for($i = 0; $i < count($riot->matches); $i++)
            {
                echo $riot->outputMatch($riot->matches[$i]);
            }
        ?>
    </div>
</body>
</html>
