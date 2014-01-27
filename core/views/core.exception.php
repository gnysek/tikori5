<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?php echo Core::app()->cfg('appName'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/exception.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo Core::app()->baseUrl() ?>media/prettify.css"/>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>media/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="<?php echo Core::app()->baseUrl() ?>media/prettify.js"></script>
</head>
<body onload="prettyPrint();">

<div id="main">
    <div id="submain">
        <div id="left">
            <?php foreach ($errors as $eid => $errorData): ?>
                <div id="error_<?php echo $eid; ?>" class="box">
                    <h1><strong><?php echo '[' . ($eid + 1) . '] ' . $errorData['message']; ?></strong></h1>
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
