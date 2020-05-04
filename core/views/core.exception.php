<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?php echo Core::app()->cfg('appName'); ?> Application Error</title>
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
                <?php if (count($errors) > 0): ?>
                <div id="error_<?php echo $eid; ?>" class="box" style="background-color: white;">
                    <h1>
                        <strong><?php echo ($eid + 1) . '/' . count($errors); ?>: <?php echo $errorData['message']; ?></strong>
                    </h1>
                    <code class="box-wrap"><?php echo $errorData['file']; ?>:<?php echo $errorData['line']; ?></code>
                </div>
                <?php endif; ?>
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
            <div class="error-header">
                <h3><?php echo Core::app()->cfg('appName'); ?> Application Error &bull; [<?php echo $errorId; ?>] <?php echo $errorType; ?></h3>

                <h1>
                    <strong><?php if (count($errors) > 1): echo ($eid) . '/' . count($errors) . ': '; endif; ?><?php echo $errorData['message']; ?></strong>
                </h1>
            </div>

            <div class="error-preview">
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


            <div class="vars">
                <h1>Tikori Application</h1>

                <table>
                    <tr>
                        <td class="d-k">Locale</td>
                        <td class="d-v">en</td>
                    </tr>
                    <tr>
                        <td class="d-k">PHP Version</td>
                        <td class="d-v"><?= phpversion(); ?></td>
                    </tr>
                </table>

                <h1>Tikori Request</h1>

                <table>
                    <tr>
                        <td class="d-k">URI</td>
                        <td class="d-v">http://x/test.php</td>
                    </tr>
                    <tr>
                        <td class="d-k">Request URI</td>
                        <td class="d-v">/x/test.php</td>
                    </tr>
                    <tr>
                        <td class="d-k">Path Info</td>
                        <td class="d-v">/x/</td>
                    </tr>
                    <tr>
                        <td class="d-k">Base path</td>
                        <td class="d-v"><?php echo htmlspecialchars('<none>'); ?></td>
                    </tr>
                    <tr>
                        <td class="d-k">Base URL</td>
                        <td class="d-v"><?php echo htmlspecialchars('<none>'); ?></td>
                    </tr>
                    <tr>
                        <td class="d-k">Script name</td>
                        <td class="d-v"><?php echo htmlspecialchars('<none>'); ?></td>
                    </tr>
                    <tr>
                        <td class="d-k">Query string</td>
                        <td class="d-v"><?php echo htmlspecialchars('<none>'); ?></td>
                    </tr>
                    <tr>
                        <td class="d-k">HTTP Method</td>
                        <td class="d-v"><?php echo htmlspecialchars('<none>'); ?></td>
                    </tr>
                    <tr>
                        <td class="d-k">Scheme</td>
                        <td class="d-v">http</td>
                    </tr>
                    <tr>
                        <td class="d-k">Port</td>
                        <td class="d-v">80</td>
                    </tr>
                    <tr>
                        <td class="d-k">Host</td>
                        <td class="d-v">localhost</td>
                    </tr>
                </table>

                <h1>Server/Request data</h1>

                <table>
                    <?php foreach($_SERVER as $k => $v): ?>
                        <?php if (in_array($k, array('HTTP_COOKIE', 'PATH'))) {
                            $v = str_replace(';', '<br/>', $v);
                        } ?>
                        <tr>
                            <td class="d-k"><?php echo $k; ?></td>
                            <td class="d-v"><?php echo $v; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php /*<h2>Registered handlers</h2>

                <table>
                    <tr>
                        <td class="d-k">REMOTE_ADDR</td>
                        <td class="d-v">localhost</td>
                    </tr>
                </table> */ ?>

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.codebox').hide();
        $('.codebox').first().show();

        $($('.codebox').first().attr('id').replace('file_', '#link_')).addClass('active');
        console.log($('.codebox').first().attr('id').replace('file_', '#link_'));

        $('.box-clickable').click(function () {
            var fileId = $(this).attr('id').replace(/link_/g, '#file_');
            $('.active').removeClass('active');
            $(this).addClass('active');
            $('.codebox').hide();
            $(fileId).show();
        });
    });
</script>

</body>
</html>
