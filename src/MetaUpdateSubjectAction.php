<?php

namespace bvb\meta;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\web\NotFoundHttpException;
use yii\helpers\Inflector;

/**
 * MetaUpdateSubjectAction is for pages where one wants to update a model
 * and related metadata on a single request. This is kind of a hybrid of the
 * Update CRUD action and MetaSaveAction
 */
class MetaUpdateSubjectAction extends \bvb\crud\actions\Update
{
    /**
     * Implement properties needed for MetaActions
     */
    use MetaActionTrait;

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
     * Loads an existing subject and meta meta models and display a page to 
     * update values
     * @param mixed $id The ID of the model that we are saving data and metadata
     * for.
     * @throws NotFoundHttpException
     * @return string
     */
    public function run($id)
    {
        $subjectModel = $this->findModel($id);
        $subjectModel->setScenario($this->scenario);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $subjectModel);
        }

        // --- Initialize a variable that will hold the variables passed into the view
        $viewParams = [
            'model' => $subjectModel,
            'metaConfig' => $this->metaConfig,
            'metaModels' => []
        ];

        // --- Initialize the meta models that will be saved in this action
        foreach($this->metaConfig as $metaKey => $metaConfig){
            $metaModel = $this->metaModelClass::getModel($metaKey, $subjectModel->id, $metaConfig);
            $viewParams['metaModels'][Inflector::variablize($metaKey)] = $metaModel;
        }

        // --- Check if the form was submitted and process it
        
        if($subjectModel->load(Yii::$app->request->post())){
            if($subjectModel->save() && !empty($this->savedFlashMessage)){
                Yii::$app->session->addFlash('success', $this->savedFlashMessage);
            }
            $allSaved = true;

            $metaPostParams = Yii::$app->request->post($this->metaModelClass::instance()->formName());
            if(!empty($metaPostParams)){
                foreach($metaPostParams as $metaKey => $keyValueArray){
                    if(
                        !empty($keyValueArray['value']) ||
                        isset($this->metaConfig[$metaKey]['saveEmpty']) && $this->metaConfig[$metaKey]['saveEmpty']
                    ){
                        $viewParams['metaModels'][$metaKey]->value = $keyValueArray['value'];
                        if(!$viewParams['metaModels'][$metaKey]->save()){
                            $allSaved = false;
                            if($this->throwErrorOnSaveFail){
                                throw new UserException('Unknown error when trying to save meta field '.$metaKey.'. Please troubleshoot.'.print_r($viewParams['metaModels'][$metaKey],true));
                            }
                        }                    
                    } elseif(
                        empty($keyValueArray['value']) &&
                        !$viewParams['metaModels'][$metaKey]->isNewRecord
                    ){
                        $viewParams['metaModels'][$metaKey]->delete();
                    }
                }

                // --- Trigger the event that the saving has been done so handlers can be registered
                $event = new MetaSavedEvent([
                    'subjectModel' => $subjectModel
                ]);
                $this->trigger(MetaHelper::EVENT_SAVING_DONE, $event);
            }

            if($allSaved){
                if(
                    strpos(Yii::$app->request->post('redirect'), \bvb\crud\helpers\Helper::SAVE_AND_CONTINUE) !== false ||
                    $this->redirect === null
                ){
                    return $this->controller->refresh();
                }
                // --- If a custom redirect was passed in then use it
                return $this->controller->redirect(Yii::$app->request->post('redirect') ? Yii::$app->request->post('redirect') : $this->redirect);
            }
        }

        return $this->controller->render($this->view, $viewParams);
    }
}