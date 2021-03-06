<?php

header('Content-Type: application/json');

require 'dbConnect.php';
require 'storeToDBWorkers.php';
require 'getFromDBWorkers.php';

$form_action_func = $_GET['function'];
$json = $_GET['jsonobj'];

if(isset($form_action_func))
{
    switch ($form_action_func) {
        case 'getRandomRoom':
            getRandomRoom($json);
            break;

        case 'getRoomByID':
            getRoomByID($json);
            break;

        case 'getFreeRoom':
            getFreeRoom($json);
            break;
        case 'getClaimByID':
            getClaimByID($json);
            break;
        case 'isPlayerInRoom':
            isPlayerInRoom($json);
        break;
    }
}

/**
 * will take player id and try to match it into a room with available slots
 * @param $json string containing player id in json form
 */
function getRandomRoom($json) {
    $list = json_decode($json);
    $playerID = $list[0];
    $connection = db_connect();
    $availableRooms = getRoomsWithEmptySlotsWorker($connection);
    if (!count($availableRooms)) {
        echo false;
        return;
    }
    $randRoom = array_rand($availableRooms);
    $roomID = $availableRooms[$randRoom]['id'];
    setPlayersRoomIDWorker($connection,$playerID,$roomID);
    if (!isRoomToFullWorker($connection,$roomID)) {
        echo json_encode(getRoomByIDWorker($roomID,$connection));
    } else {
        //Ugly way of doing it
        getRandomRoom($json);
    }
}

/**
 * Collects the complete room by id from the DB.
 * @param $roomID int, matching the ID of a room in DB
 * @return string in json format which is the room object client side.
 */
function getRoomByID($roomID) {
    $connection = db_connect();
    $room = getRoomByIDWorker($roomID,$connection);
    echo json_encode($room);

}

/**
 * The function being called to get the claim and will echo in JSON format
 * @param $claimID int, the unique id to search with.
 * @room json formatted claim
 */
function getClaimByID($claimID) {
    $connection = db_connect();
    $claim = getClaimByIDWorker($claimID,$connection);
    mysqli_close($connection);
    echo json_encode($claim);
}


/**
 * The function will check is current room wished to join exist
 * and if it have a place over for the player
 * @param $json string contain roomID and playerID
 * @return string in json format containing the room object
 */
function getFreeRoom($json){
    $list = json_decode($json);
    $roomID = $list[0];
    $playerID = $list[1];
    $connection =  db_connect();
    if (!isRoomToFullWorker($connection, $roomID)) {
        setPlayersRoomIDWorker($connection, $playerID, $roomID);
        $room = getRoomByIDWorker($roomID, $connection);
        if($room === NULL) {
            echo false;
        } else {
            echo json_encode($room);
        }
        mysqli_close($connection);
        return;
    }
    echo false;
    mysqli_close($connection);
    return;
}

/**
 * Checks if given player is in room given
 * @param $json string contain roomID and playerID
 */
function isPlayerInRoom($json) {
    $list = json_decode($json);
    $roomID = $list[0];
    $playerID = $list[1];
    $connection = db_connect();
    if ($query = mysqli_prepare($connection, "SELECT COUNT(Player.id) AS bool FROM Player WHERE roomID=? AND id=?")) {
        mysqli_stmt_bind_param($query, "ii",$roomID,$playerID);
        $rows = dbQueryGetResult($query)[0]['bool'];
        if ($rows) {
            echo true;
        } else {
            echo false;
        }
        //TODO handle failure
    }
}