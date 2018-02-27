
<?php

require 'dbConnect.php';
require 'storeToDBWorkers.php';
require 'getFromDBWorkers.php';

header('Content-Type: application/json');

$form_action_func = $_GET['function'];
if( isset($_GET['jsonobj']) )
{
    $json = $_GET['jsonobj'];
}

if(isset($form_action_func))
{
    switch ($form_action_func) {
        case 'storePlayer':
            storePlayer($json);
            break;
        case 'updateRoom':
        	updateRoom($json);
        	break;
        case 'createRoom' :
        	createRoom();
        	break;
        case 'updateRoomSize':
            updateRoomSize($json);
            break;
        case 'removePlayerByID':
            removePlayerByID($json);
            break;
        case 'updatePlayerScore' :
            updatePlayerScore($json);
            break;
        case 'updateClaim' :
            updateClaim($json);
            break;
        case 'newClaim' :
            newClaim($json);
            break;

    }
}


/**
 * updates database room with new size
 * @param $json string contains roomID and size to set
 */
function updateRoomSize($json) {
    $list = json_decode($json,true);
    $roomID = $list[0];
    $roomSize = $list[1];
    $connection = db_connect();
    setRoomSizeWorker($connection,$roomID,$roomSize);
    mysqli_close($connection);
    echo true;
}

/**
 * creates a new player in db, depending on json it also assigns a room to player
 * @param $json string will contain either just a player name or name and room to add to
 */
function storePlayer($json) {
    $list = json_decode($json,true);
    $lenghtOfList = count($list);
    $name = $list[0]['name'];
    $roomID = $list[1];
    $connection = db_connect();
    $playerID = storeNewPlayerWorker($connection,$name);
    if ($lenghtOfList === 2) {
        setPlayersRoomIDWorker($connection,$playerID,$roomID);
    }
    mysqli_close($connection);
    echo $playerID;
}

/**
 * Updates a claim in DB, echoes id
 * @param $json string will contain the claim
 */

function updateClaim($json) {
    $list = json_decode($json, true);
    $list = $list[0];
    $claimID = $list['ID'];
    $theClaim = $list['claim'];
    $corrAnsw = $list['correctAnswer'];

    $connection = db_connect();
    setClaimWorker($connection, $claimID, $theClaim, $corrAnsw);
    echo $claimID;
}

/**
 * Creates a new claim and connects it with the player who wrote it
 * @param $json string will contain the claim to create and the player that wrote it
 */
function newClaim($json){
    $list = json_decode($json, true);
    $claimList = $list[0];
    $player = $list[1];
    $connection = db_connect();
    $id = createClaimWorker($connection, $claimList['claim'], $claimList['correctAnswer']);
    updatePlayerClaimWorker($connection, $id, $player['id']);
    echo $id;
}

/**
 * Updates a players score
 * @param $json string will contain player ID and the players new score
 */
function updatePlayerScore($json){
    $list = json_decode($json);
    $playerID = $list[0];
    $newScore = $list[1];
    $connection = db_connect();
    updateScoreWorker($connection, $playerID, $newScore);

}

function updateRoom($json){
	//TODO, add changes to Room, request room by id and then loop values and if changes are detected insert new value
    //TODO probably gonna need a help function to handle players and claims.
}

/**
 * creates a new room in db and returns the ID.
 */
function createRoom(){
    $connection = db_connect();
    if ($query = mysqli_prepare($connection, "INSERT INTO Room () Values()")) {
        $id = dbQueryStoreGetId($query,$connection);
        echo $id;
    }
}

/**
 * removes player by id from DB and if room is after empty it will be removed. echoes succes bool.
 * @param $json string in JSON format containing [roomID,playerID] or just [playerID]
 */
function removePlayerByID($json) {
    $list = json_decode($json);
    if (count($list) > 1) {
        $roomID = $list[0];
        $playerID = $list[1];
    } else {
        $playerID = $list[0];
    }

    $connection = db_connect();
    if ($query = mysqli_prepare($connection, "DELETE FROM Player WHERE ID=?")) {
        mysqli_stmt_bind_param($query, "s", $playerID);
        $resultPlayer =  dbQueryRemove($query);
    }
    if (count($list) > 1) {
        if ($query = mysqli_prepare($connection, "SELECT * FROM Player WHERE RoomID=?")) {
            mysqli_stmt_bind_param($query, "s", $roomID);
            $playersInRoom =  dbQueryGetResult($query);
        }
        if (count($playersInRoom) == 0) {
            removeRoomByIDWorker($roomID,$connection);
        }
    }


    mysqli_close($connection);
    //$result = $playersInRoom && $resultPlayer;
    $result = true;
    //TODO FIX IF FAIL!
    echo $result;
}