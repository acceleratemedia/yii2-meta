<?php

namespace bvb\meta;

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
     * Array of configuration for the Meta models
     * @see \bvb\meta\MetaSaveAction::$metaConfig
     * @var []
     */
    public $metaConfig;

    /**
     * The models that will have inputs rendered
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
		foreach($this->metaModels as $metaKey => $metaModel){
			$activeField = $this->form->field($metaModel, MetaHelper::getActiveFormInputName($metaKey));
			// --- Check to see if a specific input has been set up
			if(
				isset($this->metaConfig[$metaKey]['input']['type']) &&
				$this->metaConfig[$metaKey]['input']['type'] == 'widget'
			){
				$config = $this->metaConfig[$metaKey]['input']['widgetConfig'];
				$class = ArrayHelper::remove($config, 'class');
				$inputWidgetsHtml[] = $activeField->widget($class, $config);
			} else {
				$inputWidgetsHtml[] = $activeField->textInput();
			}
		}
		return implode("\n", $inputWidgetsHtml);
    }
}