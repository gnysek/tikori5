<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?php echo Core::app()->cfg('appName'); ?></title>
    <?php echo Asset::jsAsset('media/jquery-1.8.2.min.js'); ?>
    <?php echo Asset::jsAsset('media/prettify.js'); ?>
    <?php echo Asset::cssAsset('media/reset.css'); ?>
    <?php echo Asset::cssAsset('media/prettify.css'); ?>
    <?php echo Asset::cssAsset('media/core.exception.css'); ?>
</head>
<body onload="prettyPrint();">

<div id="main">
    <div id="submain">
        <div id="left">
            <?php foreach ($errors as $eid => $errorData): ?>
                <div id="error_<?php echo $eid; ?>" class="box">
                    <p>Looks that there is a problem here:</p>
                    <h1><strong><?php if (count($errors) > 1): echo ($eid + 1) . '/' . count($errors); endif; ?><?php echo $errorData['message']; ?></strong></h1>
                    <code class="box-wrap"><?php echo $errorData['file']; ?>:<?php echo $errorData['line']; ?></code>
                </div>
                <?php foreach ($errorData['files'] as $fid => $fileData): ?>
                    <div id="link_<?php echo $eid . '-' . $fid; ?>" class="box box-clickable">
                        <?php if (!empty($fileData['info']['class'])) : ?>
                            <h2><?php echo $fileData['info']['class'] ?></h2>
                        <?php endif; ?>
                        <?php if (!empty($fileData['info']['function'])) : ?>
                            <h3><?php if (!empty($fileData['info']['type'])) {
                                    echo $fileData['info']['type'];
                                } ?>
                                <?php echo $fileData['info']['function'] ?></h3>
                        <?php endif; ?>
                        <code class="box-wrap"><?php echo $fileData['file']; ?>:<?php echo $fileData['line']; ?></code>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <div id="right">
            <h1><?php echo Core::app()->cfg('appName'); ?> Critical Error</h1>

            <?php foreach ($errors as $eid => $errorData): ?>
                <?php foreach ($errorData['files'] as $fid => $fileData): ?>
                    <div id="file_<?php echo $eid . '-' . $fid; ?>" class="codebox">
                        <a name="file_<?php echo $eid . '-' . $fid; ?>"></a>
                        <?php if (!empty($fileData['html'])) : ?>
                            <div><?php echo $fileData['html'] ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.codebox').hide();
        $('.codebox').first().show();

        $('.box-clickable').click(function () {
            var fileId = $(this).attr('id').replace(/link_/g, '#file_');
            $('.codebox').hide();
            $(fileId).show();
        });
    });
</script>

</body>
</html>
