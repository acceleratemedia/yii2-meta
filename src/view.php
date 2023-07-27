<?php
/** @var $this yii\web\View */
/** @var $metaConfig array */
/** @var $metaModels \bvb\meta\MetaModelTrait */
/** @var $subject array */

$this->title = 'Meta Data';

$this->form = Yii::createObject(['class' => \yii\widgets\ActiveForm::class]);
?>
<div class="form-group mb-3">
    <label class="form-label"><?= $subject['label']; ?></label>
    <input type="text" class="form-control" disabled value="<?= $subject['value']; ?>">
</div>
<?php
echo Yii::createObject([
    'class' => bvb\meta\MetaInputListWidget::class,
    'form' => $this->form,
    'metaConfig' => $metaConfig,
    'metaModels' => $metaModels
])->run();