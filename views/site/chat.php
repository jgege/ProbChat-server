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
    var response = JSON.parse(e.data);
    switch(response['action']) {
        case 'message':
            showMessage(response['msgs']);
            break;
        case 'partner_disconnected':
            if (response['chatSessionUserCount'] < 2) {
                console.log('looking for the next chat partner');
                joinLobby();
            } else {
                console.log('stayin');
            }
            break;
        default:
            console.log('default :(');
            console.log(response);
            break;
}
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