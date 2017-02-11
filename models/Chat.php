<?php

namespace app\models;

class Chat extends \yii\base\Model
{
    public static function getProblemCategories()
    {
        return [
            'family' => 'Family',
            'relationship' => 'Relationship',
            'bullying' => 'Bullying',
        ];
    }
}
