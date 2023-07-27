<?php
namespace bvb\meta;

use Yii;
use yii\base\BadRequestHttpException;

/**
 * MetaSaveRestAction is for saving Meta models whether they are being created
 * or they are being updated and is intended to be used as a RESTful endpoint
 */
class MetaSaveRestAction extends \yii\base\Action
{
    /**
     * Implement properties needed for MetaActions
     */
    use MetaActionTrait;

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
     * @var string The name of the third column on the $metaModel record besides
     * the key and value
     */
    public $subjectIdFieldName;

    /**
     * Loads the options from the database or creates new ones where necessary; passes
     * the models through to the view; saves any data posted in forms while
     * @param mixed $subjectId The ID of the model that we are saving meta data
     * for. Required for saving the database records and also used to load the
     * subject from the database
     * @throws NotFoundHttpException
     * @return string
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $subjectModel);
        }

        $postParams = Yii::$app->request->post();
        if(!empty($this->metaConfig)){
            $validKeys = array_keys($this->metaConfig);
            if(!in_array($postParams['key'], $validKeys)){
                throw new BadRequestHttpException('`'.$postParams['key'].'` is not a valid key');
            }
        }

        $metaModel = $this->metaModelClass::getModel(
            $postParams['key'],
            $postParams[$this->metaModelClass::getForeignKeyFieldName()],
            isset($this->metaConfig[$postParams['key']]) ? $this->metaConfig[$postParams['key']] : []
        );
    
        $metaModel->value = $postParams['value'];

        if ($metaModel->save() === false && !$metaModel->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        return $metaModel;
    }
}