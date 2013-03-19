<?php

/* @var $model User */

$msg = array(
    'Howdy',
    'Hello',
    'Hi',
    'Welcome',
    'It\'s you again'
);
$welcome = array_rand($msg);
?>

<h1><?php echo $msg[$welcome]; ?>, <?php echo Core::app()->session->user()->name; ?>!</h1>

<p>Here you can review your profile.</p>

<p>
    You've got <strong><?php echo Core::app()->session->user()->notifications_new; ?></strong> new notifications.<br/>
    You've got <strong><?php echo Core::app()->session->user()->points; ?></strong> points.
</p>

<?php if ($changed): ?>
    <div class="red-less">
        <p><strong>Changes saved.</strong></p>
    </div>
<?php endif; ?>


<div class="form">
    <?php echo Html::beginForm(''); ?>
    <div>
        <?php echo Html::warnings($model); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'from'); ?>
        <?php echo Html::textFieldModel($model, 'from'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'www'); ?>
        <?php echo Html::textFieldModel($model, 'www'); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'sex'); ?>
        <?php echo Html::radioFieldModel($model, 'sex', array('Man', 'Woman')); ?>
    </div>
    <div>
        <?php echo Html::labelModel($model, 'signature'); ?><br/>
        <?php echo Html::textareaFieldModel($model, 'signature'); ?>
    </div>
    <div>
        <?php echo Html::submitButton(); ?>
    </div>
    <?php echo Html::endForm(); ?>
</div>
