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
};

conn.onmessage = function(e) {
    //console.log(e.data);
    showMessage('BlueDog: ' + e.data);
};

chat = function(message) {
    conn.send(message);
    showMessage('You: ' + message);
}

showMessage = function(message) {
    console.log((new Date) + ' ' + message);
}

</script>