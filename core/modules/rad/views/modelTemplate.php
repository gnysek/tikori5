<?php echo '<?php'; ?>

/**
 *
<?php foreach ($result as $v): ?>
 * @property <?php echo (preg_match('/int/', $v['Type']) ? 'int' : 'string') . ' $' . $v['Field']; ?>

<?php endforeach; ?>
 */

class <?php echo $modelName; ?> extends TModel
{
<?php foreach ($result as $v): ?>
    const FIELD_<?= strtoupper($v['Field']); ?> = '<?= $v['Field']; ?>';
<?php endforeach; ?>

	protected $_primaryKey = <?= 'self::FIELD_' . strtoupper($primaryKey); ?>;

	/**
	 * @param null|string $model
	 * @return <?php echo $modelName; ?>|TModel
	 */
	public static function model($model = __CLASS__)
	{
		return parent::model($model);
	}

	/**
     * @return array[]
     */
	public function rules()
	{
		return [
			<?php echo implode(','.PHP_EOL."\t\t\t", $rulesHtml); ?>

		];
	}

<?php if (!empty($relationsHtml)): ?>
    /**
     * @return array[]
     */
	public function relations()
	{
		return [
			<?php echo implode(','.PHP_EOL."\t\t\t", $relationsHtml); ?>

		];
	}
<?php endif; ?>
}
