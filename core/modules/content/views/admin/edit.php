<?php /* @var $model ContentTranslation */ ?>

<?php echo $model->name; ?>


<div>
    <?php echo Html::beginForm(''); ?>
    <div>
        <?php echo Html::labelModel($model, 'name'); ?>
        <?php echo Html::textFieldModel($model, 'name'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'short'); ?>
        <?php echo Html::textareaFieldModel($model, 'short'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'long'); ?>
        <?php echo Html::textareaFieldModel($model, 'long'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'img'); ?>
        <?php echo Html::textFieldModel($model, 'img'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'url'); ?>
        <?php echo Html::textFieldModel($model, 'url', array('disabled' => 'disabled')); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'language_id'); ?>
        <?php echo Html::selectOptionModel(
            $model, 'language_id', $model->getLanguages(), array('disabled' => 'disabled')
        ); ?>
    </div>
    <div>
        <?php echo Html::submitButton(); ?>
    </div>
    <?php echo Html::endForm(); ?>
</div>
