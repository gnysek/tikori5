<?php echo '<?php'; ?>

/**
 *
<?php foreach ($result as $v): ?>
 * @property <?php echo (preg_match('/int/', $v['Type']) ? 'int' : 'string') . ' $' . $v['Field']; ?>

<?php endforeach; ?>
 */
class <?php echo $modelName; ?> extends TModel
{

	protected $_primaryKey = '<?php echo $primaryKey; ?>';

	/**
	 * @param null|string $model
	 * @return <?php echo $modelName; ?>|TModel
	 */
	public static function model($model = __CLASS__)
	{
		return parent::model($model);
	}

	/* rules */
	public function rules()
	{
		return array(
			<?php echo implode(','.PHP_EOL."\t\t\t", $rulesHtml); ?>

		);
	}

<?php if (!empty($relationsHtml)): ?>
	public function relations()
	{
		return array(
			<?php echo implode(','.PHP_EOL."\t\t\t", $relationsHtml); ?>

		);
	}
<?php endif; ?>
}
