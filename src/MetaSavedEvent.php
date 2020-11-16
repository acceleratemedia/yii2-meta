<?php

namespace bvb\meta;

/**
 * MetaSavedEvent is intended to be used after metadata have been saved for a 
 * subject model
 */
class MetaSavedEvent extends \yii\base\Event
{
    /**
     * The model that meta data is being saved for
     * @var \yii\base\Model
     */
    public $subjectModel;
}