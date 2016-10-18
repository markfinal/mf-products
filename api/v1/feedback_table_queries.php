<?php
require_once 'api/v1/dbutils.php';

function feedback_addmessage($session_id, $message)
{
    $connection = connectdb();

    $query = $connection->prepare('INSERT INTO Feedback (session,message) VALUES (:session,:message)');
    $query->bindParam(':session', $session_id, PDO::PARAM_INT);
    $query->bindParam(':message', $message, PDO::PARAM_STR);
    $query->execute();

    unset($connection);
}

?>
