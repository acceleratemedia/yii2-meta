<?php

namespace bvb\meta;

use yii\helpers\Inflector;

/**
 * MetaHelper contains functions to help meta models
 */
class MetaHelper
{
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
