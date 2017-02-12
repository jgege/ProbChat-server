<?php

namespace app\models;

class Chat extends \yii\base\Model
{
    public static function getProblemCategories()
    {
        return [
            'sexual' => 'Sexual harassment',
            'racial' => 'Racial harassment',
            'personal' => 'Personal harassment',
            'bullying' => 'Bullying',
            'sexual_orientation' => 'Harassment on grounds of sexual orientation',
            'disabled_people' => 'Harassment of disabled people',
            'age' => 'Age harassment',
            'stalking' => 'Stalking',
        ];
    }
}
