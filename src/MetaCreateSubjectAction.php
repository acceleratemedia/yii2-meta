<?php

namespace bvb\meta;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\helpers\Inflector;

/**
 * MetaCreateSubjectAction is for pages where one creates a new model and wants
 * to simultaneously create meta records for it. This is kind of a hybrid of the
 * Create CRUD action and MetaSaveAction
 */
class MetaCreateSubjectAction extends \bvb\crud\actions\Create
{
    /**
     * Implement properties needed for MetaActions
     */
    use MetaActionTrait;

    /**
     * The URL to redirect to in case there is a save of the subejct model but one
     * or more of the meta models fails to save
     * @var string
     */
    public $partialSucessRedirect = 'update/index';

    /**
     * Initialize the form used to create the model
     * Set the a submit button as a toolbar widget
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if(empty($this->metaModelClass)){
            throw new InvalidConfigException('The property $metaModelClass must be configured with the class name');
        }
    }

    /**
     * Initialize a new model and new meta models and display a page to 
     * input values and save them
     * @return string
     */
    public function run()
    {
        $this->checkAccess();

        // --- Create the subject model class
        $subjectModel = Yii::createObject(\yii\helpers\ArrayHelper::merge(
            ['class' => $this->modelClass],
            $this->modelDefaults
        ));

        // --- Initialize a variable that will hold the variables passed into the view
        $viewParams = [
            'model' => $subjectModel,
            'metaConfig' => $this->metaConfig,
            'metaModels' => []
        ];

        // --- Initialize the meta models that will be saved in this action
        foreach($this->metaConfig as $metaKey => $metaConfig){
            $metaModel = $this->metaModelClass::getModel($metaKey, null, $metaConfig);
            $viewParams['metaModels'][Inflector::variablize($metaKey)] = $metaModel;
        }

        // --- Check if the form was submitted and process it
        $subjectModelValidates = false;
        if($subjectModel->load(Yii::$app->request->post())){
            $subjectModelValidates = $subjectModel->validate();
        }

        // --- Process the posted meta
        $metaPostParams = Yii::$app->request->post($this->metaModelClass::instance()->formName());
        $allMetaValidates = true;
        if(!empty($metaPostParams)){
            foreach($metaPostParams as $metaKey => $keyValueArray){
                $viewParams['metaModels'][$metaKey]->value = $keyValueArray['value'];

                // --- Validate it but we know it will fail because the subject doesn't exist yet
                $viewParams['metaModels'][$metaKey]->validate();
                    
                // --- if the error is on the foreign key field only then it should save if the
                // --- subject model saves so let's consider it 'valid'
                $errorAttributes = array_keys($viewParams['metaModels'][$metaKey]->getErrors());
                if(
                    in_array('key', $errorAttributes) ||
                    in_array('value', $errorAttributes)
                ){
                    $allMetaValidates = false;
                }
            }
        }

        if($subjectModelValidates && $allMetaValidates && $subjectModel->save()){
            if(!empty($this->savedFlashMessage)){
                Yii::$app->session->addFlash('success', $this->savedFlashMessage);
            }

            if(!empty($metaPostParams)){
                $allMetaSaved = true;
                foreach($viewParams['metaModels'] as $metaModel){
                    if(
                        !empty($metaModel['value']) ||
                        isset($this->metaConfig[$metaModel->key]['saveEmpty']) && $this->metaConfig[$metaModel->key]['saveEmpty']
                    ){
                        $metaModel->{$metaModel::getForeignKeyFieldName()} = $subjectModel->id;
                        if(!$metaModel->save()){
                            Yii::error(
                                'There was a problem saving a meta model after they all validated and the subejct saved: '.
                                print_r($metaModel->getErrors(),true)."\n".
                                print_r($metaModel->attributes, true)
                            );
                            $allMetaSaved = false;
                            if($this->throwErrorOnSaveFail){
                                throw new UserException('Unknown error when trying to save meta field '.$metaKey.'. Please troubleshoot.'.print_r($viewParams['metaModels'][$metaKey],true));
                            } else {
                                Yii::$app->session->addFlash('danger', 'There was a loss of data while saving. Please re-enter the missing information');
                            }
                        }
                    }
                }

                if($allMetaSaved){            
                    // --- Trigger the event that the saving has been done so handlers can be registered
                    $event = new MetaSavedEvent([
                        'subjectModel' => $subjectModel
                    ]);
                    $this->trigger(MetaHelper::EVENT_SAVING_DONE, $event);
                } else {
                    return $this->controller->redirect([$this->partialSucessRedirect, 'id' => $subjectModel->id]);
                }
            }
            return $this->controller->redirect($this->getRedirectUrl($subjectModel));
        }
        return $this->controller->render($this->view, $viewParams);
    }
}