<h1>Tikori5 Rapid Application Development</h1>
<h2>Sync tables</h2>

<table style="width: 100%;" class="table table-bordered table-condensed">
    <tr>
        <td>ID</td>
        <td>Table name</td>
        <td>Class name</td>
        <td colspan="2">Options</td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <td colspan="2"><?= Html::link('Dump all existing', 'rad/dumpAll'); ?></td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <td colspan="2"><?= Html::link('Compare all existing', 'rad/compareAll'); ?></td>
    </tr>
    <?php $i = 0; ?>
    <?php foreach ($tables as $table => $data): ?>
        <?php
        $exists = true;
        try {
            $modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
            $class = new $modelName;
        } catch (Exception $e) {
            $exists = false;
        }; ?>
        <tr>
            <td class="text-right"><?= $i++; ?></td>
            <td<?= $data['new'] ? ' style="background: tomato;"' : ''; ?>><?= $table; ?></td>
            <td>
                <kbd<?= $exists ? '' : ' style="background: tomato; text-decoration: line-through;"'; ?>>class <?php echo $modelName; ?></kbd>
            </td>
            <?php if ($data['json']): ?>
                <td style="background: yellowgreen"><?= Html::link($data['new'] ? 'Import' : 'Sync', 'rad/syncModel/model/' . $table); ?></td>
            <?php else: ?>
                <td>&ndash;</td>
            <?php endif; ?>

            <?php if ($data['new']): ?>
                <td>&ndash;</td>
            <?php else: ?>
                <td style="background: lightskyblue"><?= Html::link('Dump', 'rad/dumpModel/model/' . $table); ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
</table>
