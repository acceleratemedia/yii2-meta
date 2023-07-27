<?php

namespace bvb\meta;

/**
 * MetaActionTrait implements some of the basic properties needed by
 * and Meta action
 */
trait MetaActionTrait
{
    /**
     * The name of the meta model class we will be saving. It should use the trait
     * \common\meta\MetaModelTrait or implement the necessary functions
     * @var string
     */
    public $metaModelClass;

    /**
     * Configuration for options that should be saved by this action.
     * An example format is:
     * ```
     * $metaConfig = [
     *       self::MY_META => [
     *           'label' => 'Label for My Meta',
     *           'hint' => 'This is the hint for the meta',
     *           'value' => 'defaultValue',
     *           'rules' => [
     *               ['string', 'max' => 1000]
     *           ]
     *       ]
     * ]
     * ```
     * The key to each options configuration array will be modified using
     * [[yii\helpers\Inflector::variablize()]] and a meta model will
     * be passed into the view using that name
     * @var array
     */
    public $metaConfig = [];

    /**
     * The flash message that will be displayed if all options saved successfully
     * on submission. Can be set to false or null to have no flash message
     * @var string
     */
    public $savedFlashMessage = 'Saved Successfully';

    /**
     * Whether or not to throw an error when a failed save happens
     * @var boolean
     */
    public $throwErrorOnSaveFail = false;
}