<?php /* @var $model User */ ?>

<h1>Edit user <?php echo $model->name; ?></h1>

<div>
    <?php echo Html::beginForm(''); ?>
    <div>
        <?php echo Html::labelModel($model, 'name'); ?>
        <?php echo Html::textFieldModel($model, 'name'); ?>
        <?php echo Html::errorModel($model, 'name'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'email'); ?>
        <?php echo Html::textFieldModel($model, 'email'); ?>
        <?php echo Html::errorModel($model, 'email'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'sex'); ?>
        <?php echo Html::selectOptionModel(
            $model, 'sex', array('Male', 'Female')
        ); ?>
        <?php echo Html::errorModel($model, 'sex'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'from'); ?>
        <?php echo Html::textFieldModel($model, 'from'); ?>
        <?php echo Html::errorModel($model, 'from'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'www'); ?>
        <?php echo Html::textFieldModel($model, 'www'); ?>
        <?php echo Html::errorModel($model, 'www'); ?>
    </div>
    <div>
        <?php echo Html::submitButton(); ?>
    </div>
    <?php echo Html::endForm(); ?>
</div>
