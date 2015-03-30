<h1>Tikori5 Rapid Application Development</h1>
<h2>Create new Model for <code><?php echo $table; ?></code> table</h2>

<?php echo Html::beginForm('rad/modelCreate/model/' . $table); ?>

<code><?php echo $src; ?></code>

<?php if ($fileExists): ?>
    <div style="border: 1px solid red; padding: 2px; margin: 5px; background: tomato;">
        <h2>This file already exists!</h2>
    </div>
<?php endif; ?>

<div class="block"><?php highlight_string($file); ?></div>

<div>
    <input type="checkbox" name="addRelations" value="1" id="addRelations"<?php if (!empty($relationsHtml)) { echo 'checked'; } ?>>
    <label for="addRelations"><?php echo __('Add relations'); ?></label>
</div>

<div>

    <?php echo $table; ?> . <span id="selectPK"><?php echo $PK; ?></span>

    <select id="selectField">
        <?php foreach ($fields as $field): ?>
            <option value="<?php echo $field; ?>"><?php echo $field; ?></option>
        <?php endforeach; ?>
    </select>

    <select id="selectRelation">
        <option value="hasmany">Has many</option>
        <option value="belongsto">Belongs to</option>
        <option value="hasone" style="text-decoration: line-through; color: #bbb;">Has one</option>
        <option value="stat" style="text-decoration: line-through; color: #bbb;">Stat</option>
    </select>

    :

    <select id="selectTable">
        <?php foreach ($relations as $table => $fields): ?>
			<optgroup label="<?php echo $table; ?>"><?php echo ''; ?>
            <?php foreach ($fields as $field => $isPK): ?>
				<?php
					$tableName = str_replace(' ','',ucwords(str_replace('_', ' ', $table)));
					if (substr($tableName,-1,1) !== 's') {
						$tableName .= 's';
					}
				?>
                <option value="<?php echo $table . $field; ?>" data-table="<?php echo $table; ?>" data-field="<?php echo $field; ?>" data-ispk="<?php echo $isPK ? '1' : '0'; ?>">
                    <?php echo $tableName . ': ' . $table . ' . ' . $field; ?>
				</option>
            <?php endforeach; ?>
			</optgroup>
        <?php endforeach; ?>
    </select>

    <button id="addBtn">Add</button>
</div>

<div>
    <ul id="addedRelations">
		<?php foreach($relationsHtml as $relation): ?>
			<li><code><?php echo $relation; ?></code>
				<input type="hidden" name="relation[]" value="<?php echo $relation; ?>">
				&bull; <a class="removeRel">&times; Remove</a>
			</li>
		<?php endforeach; ?>
    </ul>
</div>

<input type="submit" value="<?php echo __('Preview') . ' ' . $displaySrc; ?>">
<input type="submit" name="save" value="<?php echo ((!$fileExists) ? __('Create') : __('Rewrite')) . ' ' . $displaySrc; ?>">

<?php echo Html::endForm(); ?>

<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript">
    function capitaliseFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    $(document).ready(function () {
        var where = $('#addedRelations');
        var cnt = <?php echo count($relationsHtml) ?>;

        $('#selectRelation').on('change', function () {
            switch ($(this).val()) {
                case 'hasmany':
                    $('#selectTable option').each(function () {
                        $(this).toggle(($(this).data('ispk') == 0) ? true : false);
                    });

                    $('#selectField').hide();
                    $('#selectPK').show();

                    $('#selectTable').val($($('#selectTable option[data-ispk="0"]')[0]).val());
                    break;
                case 'belongsto':
                    $('#selectTable option').each(function () {
                        $(this).toggle(($(this).data('ispk') == 0) ? false : true);
                    });

                    $('#selectField').show();
                    $('#selectPK').hide();

                    $('#selectTable').val($($('#selectTable option[data-ispk="1"]')[0]).val());
                    break;
                default:
                    return false;
            }

        });

        $('#selectRelation').trigger('change');

        $('#addBtn').on('click', function () {

            var text = '---';
			var html = '---';
            var sel = $('#selectTable option:selected');

            switch ($('#selectRelation option:selected').val()) {
                case 'belongsto':
                    text = '\'' + capitaliseFirstLetter(sel.data('table')) + '\' => array(self::BELONGS_TO, \''
                        + capitaliseFirstLetter(sel.data('table'))
                        + '\', \'' + $('#selectField option:selected').val() + '\')';

					html = '<code>' + text + '</code><input type="hidden" name="relation[]" value="' + text + '">';
                    break;
                case 'hasmany':
                    text = '\'' + capitaliseFirstLetter(sel.data('table')) + 's\' => array(self::HAS_MANY, \''
                        + capitaliseFirstLetter(sel.data('table'))
                        + '\', \'' + sel.data('field') + '\')';

                    html = '<code>' + text + '</code><input type="hidden" name="relation[]" value="' + text + '">';
                    break;
                default:
                    return false;
            }

			try {
				var canAdd = true;
				$('ul li code').filter(function(item) {
					if ($(this).text() == text) {
						canAdd = false;
						return false;
					}
				});

				if (canAdd) {
					$('<li>' + html + ' &bull; <a class="removeRel">&times; Remove</a></li>').appendTo(where);
					cnt++;
				} else {
					alert('Already added');
				}

			}catch(e){
				console.log(e);
			}
            return false;
        });

        $(document).on('click', '.removeRel', function () {
            if (cnt > 0) {
                $(this).parents('li').remove();
                cnt--;
            }
            return false;
        });
    });
</script>
