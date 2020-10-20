<?php

namespace bvb\meta;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * MetaModelTrait implements functions that can help models that are intended
 * to be extra pieces of data for a related model
 */
trait MetaModelTrait
{
    /**
     * Label to be used for the 'value' attribute to allow for developers to set
     * custom hints for the values of options they create in their extensions
     * @see self::getAttributeLabel()
     * @var string
     */
    public $label = '';

    /**
     * Hint to be used for for the 'value' attribute to allow for developers to
     * set custom hints for the values of options they create in their extensions
     * @see self::getAttributeHint()
     * @var string
     */
    public $hint = '';

    /**
     * Array of rules that can be applied to the 'value' attribute to allow for
     * developers to set custom validation for the values of options they create
     * in their extensions
     * @see self::rules()
     * @var array
     */
    public $rules = [];

    /**
     * Uses [[$rules]] to append additional custom rules per instance for specific
     * needs in regards to options that a developer may include in their application
     * {@inheritdoc}
     */
    public function rules()
    {
        // --- Default rules related to key should not receive user input so these
        // --- are here to make sure developers follow these rules 
        $rules = $this->getDefaultRules();

        if(!empty($this->rules)){
            foreach($this->rules as $rule){
                array_unshift($rule, ['value']);
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    /**
     * Return a set of default rules which are common to apply for validation
     * 1) Requires all fields
     * 2) Key default is a 100 max length string
     * 3) Unique constraint on key and foreign key field
     * 4) Make sure related model exists
     * @return array
     */
    public function getDefaultRules()
    {
        return [
            [[self::getForeignKeyFieldName(), 'key'], 'required'],
            [['key'], 'string', 'max' => 100],
            [[self::getForeignKeyFieldName(), 'key'], 'unique', 'targetAttribute' => [self::getForeignKeyFieldName(), 'key']],
            [[self::getForeignKeyFieldName()], 'exist', 'skipOnError' => true, 'targetClass' => self::getSubjectClass(), 'targetAttribute' => [self::getForeignKeyFieldName() => 'id']]
        ];
    }

    /**
     * Uses [[$label]] to return the label
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'value' => $this->label,
            // --- Sets the label properly for the suggested use of SiteOption
            // --- models when submitting in forms since frequently more than one
            // --- model is updated on a single page so we set the attribute based
            // --- on the key
            '['.Inflector::variablize($this->key).']value' => $this->label,
        ];
    }

    /**
     * Uses [[$hint]] to return the label
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'value' => $this->hint,
            '['.Inflector::variablize($this->key).']value' => $this->hint,
        ];
    }

    /**
     * Returns an instance of the meta model class representing the records with the given key
     * If $returnNull is false it will return a new instance of this class initialized
     * with the given key, with the supplied configuration options. This is is useful
     * when using in the context of updating options that may not yet exist in the database.
     * A `behaviors` key may be added to $config to have and these will be attached after
     * the model is created using [[Yii::configure()]]
     * @param string $key
     * @param mixed $subjectId
     * @param config $array
     * @param boolean $returnNull
     * @return $this|null
     */
    static function getModel($key, $subjectId, $config = [], $returnNull = false)
    {
        // --- Remove behaviors if set because they can't be set by instantiating
        // --- or configuring and must be attached on the fly
        $behaviors = ArrayHelper::remove($config, 'behaviors');

        $model = self::findOne(['key' => $key, self::getForeignKeyFieldName() => $subjectId]);
        if($model){
            // --- If we have an existing model, remove the default value from
            // --- the configuration so we don't override the stored value
            $value = ArrayHelper::remove($config, 'value');
            Yii::configure($model, $config);
        } else if(!$returnNull){
            $model = new static(ArrayHelper::merge(['key' => $key, self::getForeignKeyFieldName() => $subjectId], $config));
        }

        // --- Attach behaviors
        if(!empty($behaviors)){
            foreach($behaviors as $behaviorName => $behaviorConfig){
                $model->attachBehavior($behaviorName, $behaviorConfig);
            }
        }
        return $model;
    }

    /**
     * This is a placeholder function that must be overridden by models
     * using this trait
     * @throws \yii\base\InvalidConfigException
     * @return string
     */
    static function getForeignKeyFieldName()
    {
        throw new InvalidConfigException('Classes implementing '.__TRAIT__.' must declare a static function getForeignKeyFieldName() and return a string which is the name of the database column that is a foreign key to the table that the meta model is storing data on.');
    }

    /**
     * This is a placeholder function that must be overridden by models
     * using this trait
     * @throws \yii\base\InvalidConfigException
     * @return string
     */
    static function getSubjectClass()
    {
        throw new InvalidConfigException('Classes implementing '.__TRAIT__.' must declare a static function getSubjectClass() which returns the string classname of the model class which is the subject this the meta data is being saved for.');
    }
}
