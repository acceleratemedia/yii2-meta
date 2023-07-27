<?php

namespace bvb\meta;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yiiutils\Helper;

/**
 * MetaInputListWidget renders inputs for MetaModels in a list
 */
class MetaInputListWidget extends \yii\base\Widget
{
    /**
     * An ActiveForm widget which will be used to render the inputs
     * @var \yii\widget\ActiveForm
     */
    public $form;

    /**
     * Array of configuration for the Meta models. Indexed by key.
     * @see \bvb\meta\MetaSaveAction::$metaConfig
     * @var []
     */
    public $metaConfig;

    /**
     * The models that will have inputs rendered. Should be idnexed by key
     * @var \yii\base\Model[]
     */
    public $metaModels;

    /**
     * Render a list of inputs for $metaModels
     * {@inheritdoc}
     */
    public function run()
    {
        $inputWidgetsHtml = [];
        Helper::sortByPosition($this->metaConfig, 'input.position', false);
        foreach($this->metaConfig as $metaKey => $metaConfig){
            $modelToUse = $this->metaModels[$metaKey];
            $activeField = $this->form->field($modelToUse, MetaHelper::getActiveFormInputName($metaKey));
            // --- Check to see if a specific input has been set up
            if(
                isset($metaConfig['input']['type']) &&
                $metaConfig['input']['type'] == 'widget'
            ){
                $config = $metaConfig['input']['widgetConfig'];
                $class = ArrayHelper::remove($config, 'class');
                $inputWidgetsHtml[] = $activeField->widget($class, $config);
            } else {
                if(!isset($metaConfig['input']['type'])){
                    $inputWidgetsHtml[] = $activeField->textInput();
                } else {                
                    switch($metaConfig['input']['type']){
                        case 'textarea':
                            $inputWidgetsHtml[] = $activeField->textarea();
                            break;
                        case 'checkbox':
                            $inputWidgetsHtml[] = $activeField->checkbox();
                            break;
                        default: throw new InvalidConfigException('Unknown input configured for meta field');
                    }
                }
            }
        }
        return implode("\n", $inputWidgetsHtml);
    }
}