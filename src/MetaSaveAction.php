<?php

namespace bvb\meta;


use Yii;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\web\NotFoundHttpException;
use yii\helpers\Inflector;

/**
 * MetaSaveAction for updating meta models related to a subject model
 * but does not require saving any data on the subject model
 */
class MetaSaveAction extends \yii\base\Action
{
    /**
     * Implement properties needed for MetaActions
     */
    use MetaActionTrait;

    /**
     * @var String initialization event
     */
    const EVENT_INIT = 'init';

    /**
     * The name of the model class for the subject we are saving meta data about.
     * @var string
     */
    public $subjectModelClass;

    /**
     * URL to be redirected to after a successful save. Defaults to self so the
     * page will not try to repeat a submit if the page is refreshed. Setting this
     * as false will not redirect after a successful save
     * @var mixed
     */
    public $redirectUrl;

    /**
     * Name of the view file to be rendered that displays the option inputs.
     * @var string
     */
    public $view = '@bvb/meta/view';

    /**
     * A closure function may be supplied to check if a user should have access.
     * Its signature should be:
     * ```
     * function($subjectModel){}
     * ```
     * @var null|Closure
     */
    public $checkAccess;

    /**
     * Initialize the form used to create the model
     * Set the a submit button as a toolbar widget
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
        if(empty($this->metaModelClass)){
            throw new InvalidConfigException('The property $metaModelClass must be configured with the class name');
        }
        if(empty($this->subjectModelClass)){
            throw new InvalidConfigException('The property $subjectModelClass must be configured with the class name');
        }
    }

    /**
     * Loads the options from the database or creates new ones where necessary; passes
     * the models through to the view; saves any data posted in forms while
     * @param mixed $subjectId The ID of the model that we are saving meta data
     * for. Required for saving the database records and also used to load the
     * subject from the database
     * @throws NotFoundHttpException
     * @return string
     */
    public function run($subjectId)
    {
        if(empty($this->metaConfig)){
            throw new InvalidConfigException('A configuration array for meta data intended to be displayed and saved must be provided for this action to save in the $metaConfig property.');
        }

        $subjectModel = $this->subjectModelClass::findOne($subjectId);

        if(!$subjectModel){
            throw new NotFoundHttpException('Subject not found');
        }

        if(is_array($this->checkAccess) || $this->checkAccess instanceof \Closure){
            call_user_func($this->checkAccess, $this->id, $subjectModel);
        }

        $viewParams = [
            'subjectModel' => $subjectModel,
            'metaModels' => [],
            'metaConfig' => $this->metaConfig
        ];

        foreach($this->metaConfig as $metaKey => $metaConfig){
            $metaModel = $this->metaModelClass::getModel($metaKey, $subjectId, $metaConfig);
            $viewParams['metaModels'][Inflector::variablize($metaKey)] = $metaModel;
        }

        $postParams = Yii::$app->request->post($this->metaModelClass::instance()->formName());
        if(!empty($postParams)){
            $allSaved = true;
            foreach($postParams as $metaKey => $keyValueArray){
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

            if($allSaved){
                if(!empty($this->savedFlashMessage)){
                    Yii::$app->session->addFlash('success', $this->savedFlashMessage);
                }
                if($this->redirectUrl !== false){
                    if($this->redirectUrl === null){
                        return $this->controller->refresh();
                    } else {
                        return $this->controller->redirect($this->redirectUrl);
                    }
                }
            }
        }
        return $this->controller->render($this->view, $viewParams);
    }
}