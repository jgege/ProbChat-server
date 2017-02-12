<?php
    use Yii;
?>


<?php
    $websocketUrl = (Yii::$app->params['chatServer']['url'] . ':' . Yii::$app->params['chatServer']['port']);
?>
<script>
var conn = new WebSocket('<?= $websocketUrl ?>');
conn.onopen = function(e) {
    console.log("Connection established!");
    joinLobby();
};

conn.onmessage = function(e) {
    //console.log(e.data);
    showMessage('BlueDog: ' + e.data);
};

function chat(message) {
    conn.send(JSON.stringify({
        msg: message,
        action: 'message',
    }));
    showMessage('You: ' + message);
}

function showMessage(message) {
    console.log((new Date) + ' ' + message);
}

function joinLobby() {
    conn.send(JSON.stringify({
        problem: 'family',
        action: 'matching',
    }));
}

</script>