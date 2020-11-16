<?php

namespace bvb\meta;

use bvb\siteoption\backend\models\SiteOption;
use kartik\form\ActiveForm;
use Yii;
use yii\base\Action;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\web\NotFoundHttpException;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * MetaSaveAction is best used on pages where one intends to display meta data
 * models on a single page that require saving.
 */
class MetaSaveAction extends Action
{
    /**
     * Name of the event to be triggered after the saving of metadata is complete.
     * This is run after all posted meta data has at least attempted to been saved
     * regardless of success.
     * @var string
     */
    const EVENT_SAVING_DONE = 'metaSavingDone';

    /**
     * The name of the meta model class we will be saving. It should use the trait
     * \common\meta\MetaModelTrait or implement the necessary functions
     * @var string
     */
    public $metaModelClass;

    /**
     * The name of the model class for the subject we are saving meta data about.
     * @var string
     */
    public $subjectModelClass;

    /**
     * Configuration for options that should be saved by this action.
     * An example format is:
     * ```
     * $metaConfig = [
     *       self::MYOPTION => [
     *           'label' => 'Label for My Option',
     *           'hint' => 'This is the hint for the option',
     *           'value' => 'defaultValue',
     *           'rules' => [
     *               ['string', 'max' => 1000]
     *           ]
     *       ]
     * ]
     * ```
     * The key to each options configuration array will be modified using
     * [[yii\helpers\Inflector::variablize()]] and a SiteOption model will
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
    public $view = 'index';

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
     * Whether or not to throw an error when a failed save happens
     * @var boolean
     */
    public $throwErrorOnSaveFail = false;

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
        if(empty($this->subjectModelClass)){
            throw new InvalidConfigException('The property $subjectModelClass must be configured with the class name');
        }
        if(empty($this->metaConfig)){
            throw new InvalidConfigException('A configuration array for meta data intended to be displayed and saved must be provided for this action to save in the $metaConfig property.');
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
        $subjectModel = $this->subjectModelClass::findOne($subjectId);

        if(!$subjectModel){
            throw new NotFoundHttpException('Subject not found');
        }

        if(is_array($this->checkAccess) || $this->checkAccess instanceof \Closure){
            call_user_func($this->checkAccess, $subjectModel);
        }

        $viewParams = [
            'subjectModel' => $subjectModel,
            'metaModels' => []
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
            $this->trigger(self::EVENT_SAVING_DONE, $event);

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