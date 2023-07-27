<?php

namespace bvb\meta;

use Yii;
use yii\base\Behavior;
use yii\base\UnknownPropertyException;
use yii\base\UnknownMethodException;
use yii\db\Query;

/**
 * MetaBehavior extends objects that have meta data stored in another table
 * where each piece of data is in a key/value format. It allows those objects
 * to access the related meta data records identified by [[$metaModelClass]]
 * by prepending `meta` to the proepty name. So, if there is a meta record
 * with a key `testData` it can be accessed via `$owner->metaTestData`
 */
class MetaBehavior extends Behavior
{
    /**
     * The name of the model class that represents records in the related
     * meta table for the given object
     * @var string
     */
    public $metaModelClass;

    /**
     * The name of the column in the meta data table that is the foreign
     * key to the owner table
     * @var string
     */
    public $ownerForeignKeyField;

    /**
     * Contains meta data in an array with the keys as they are stored in the
     * database and value as they are. Accessed by [[getMeta()]] to allow 
     * accessing of related meta data properties
     * @var array
     */
    private $_meta = [];

    /**
     * Intercepts calls for properties starting with `meta` and will return the 
     * appropriate metadata record value. An issue is that Query::scalar() will
     * return false if no record is found at all so we may end up with a mistaken
     * false value for a meta key. I'm not sure whether it'd be better to unset
     * the key and leave it blank as if it is an unknown property or to set
     * it as null
     * {@inheridoc}
     */
    public function __get($name)
    {
        try{
            return parent::__get($name);
        } catch(UnknownPropertyException $e){
            if(substr($name, 0, 4) == 'meta'){
                $key = lcfirst(substr($name, 4));
                if(array_key_exists($key, $this->_meta)){
                // if(isset($this->_meta[$key])){ --- changed to array key exists but i'm not sure if this is in conflict to my decision below to set the eky to null if false is returned
                    return $this->_meta[$key];
                }
                $this->_meta[$key] = (new Query)
                    ->select(['value'])
                    ->from($this->metaModelClass::instance()->tableName())
                    ->where([
                        $this->getOwnerForeignKeyField() => $this->owner->id,
                        'key' => $key
                    ])->scalar();
                if($this->_meta[$key] === false){
                    $this->_meta[$key] = null;
                }
                return $this->_meta[$key];
            }
            throw $e;
        }
    }

    /**
     * Extend the default functionality of the getter to say it can get
     * the meta property
     * {@inheritdoc}
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return parent::canGetProperty($name, $checkVars) || 
            substr($name, 0, 4) == 'meta';
    }

    /**
     * If no value is set for [[$ownerForeignKeyField]] attempt to automatically
     * guess it by using the table name of the owner with suffix '_id'
     * @return string
     */
    protected function getOwnerForeignKeyField()
    {
        if(empty($this->ownerForeignKeyField)){
            $this->ownerForeignKeyField = $this->owner->tableName().'_id';
        }
        return $this->ownerForeignKeyField;
    }

    /**
     * Extend the default functionality of the getter to say it can get
     * the meta property
     * {@inheritdoc}
     */
    public function hasMethod($name)
    {
        return parent::hasMethod($name) || 
            substr($name, 0, 7) == 'getmeta';
    }

    /**
     * This will make relational queries in the format 'meta{KeyName}'
     */
    public function __call($name, $params)
    {
        try{
            return parent::__get($name);
        } catch(UnknownPropertyException $e){
            if(substr($name, 0, 7) == 'getmeta'){
                $metaKey = lcfirst(str_replace('getmeta', '', $name));
                return $this->owner->hasOne(
                            $this->metaModelClass,
                            [$this->getOwnerForeignKeyField() => 'id']
                        )->andOnCondition([$metaKey.'.key' => $metaKey])
                        ->alias($metaKey);
            }
            throw $e;
        }
    }
}
