<?php

namespace bvb\meta;

use yii\helpers\Inflector;

/**
 * MetaHelper contains functions to help meta models
 */
class MetaHelper
{
    /**
     * Name of the event to be triggered after the saving of metadata is complete.
     * This is run after all posted meta data has at least attempted to been saved
     * regardless of success.
     * @var string
     */
    const EVENT_SAVING_DONE = 'metaSavingDone';

    /**
     * Returns the name attribute that should be used for meta models taking
     * into consideration many of these same models may be on a page at once
     * @param string $key
     * @return string
     */
    static function getActiveFormInputName($key)
    {
        return '['.Inflector::variablize($key).']value';
    }
}
