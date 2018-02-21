<?php

require 'dbConnect.php';

$form_action_func = $_GET['function'];
$json = $_GET['jsonobj'];
if(isset($form_action_func))
{
    switch ($form_action_func) {
        case 'isServerAndDBUp':
            isServerAndDBUp();
            break;
        case 'isRoundDone':
            isRoundDone($json);
            break;
    }
}

/**
 * Gets all players from room with id provided in json.
 * Then checks the one by round attribute, if one is on a lower round then one in json
 * that round is considered not done yet and a false (0) will be echoed
 * otherwise if all is on roundNo or above true will be echoed.
 * @param $json containing [roomID,roundNo].
 */
function isRoundDone($json) {
    $list = json_decode($json);
    $roomID = $list[0];
    $roundNo = $list[1];

    $connection = db_connect();
    if ($query = mysqli_prepare($connection, "SELECT * FROM player WHERE RoomID=?")) {
        mysqli_stmt_bind_param($query, "i", $roomID);
        $rows = db_query($query);
    }
    mysqli_close($connection);

    foreach ($rows as $row) {
        if ($row['Round'] < $roundNo) {
            echo 0;
            return;
        }
    }
    echo 1;
}

/**
 * A simple function that checks if the servers db is up.
 */
function isServerAndDBUp() {
    //TODO: Actually check if DB is online
    echo "True";
}
?>