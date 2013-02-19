<?php namespace JasonLewis\Basset;

class Asset implements FilterableInterface {

	/**
	 * Absolute path to the asset.
	 * 
	 * @var string
	 */
	protected $absolutePath;

	/**
	 * Relative path to the asset.
	 * 
	 * @var string
	 */
	protected $relativePath;

	/**
	 * Array of filters.
	 * 
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Indicates if the asset is to be ignored from compiling.
	 * 
	 * @var bool
	 */
	protected $ignored = false;

	/**
	 * Create a new asset instance.
	 * 
	 * @param  string  $absolutePath
	 * @param  string  $relativePath
	 * @return void
	 */
	public function __construct($absolutePath, $relativePath)
	{
		$this->absolutePath = $absolutePath;
		$this->relativePath = $relativePath;
	}

	/**
	 * Get the absolute path to the asset.
	 * 
	 * @return string
	 */
	public function getAbsolutePath()
	{
		return $this->absolutePath;
	}

	/**
	 * Get the relative path to the asset.
	 * 
	 * @return string
	 */
	public function getRelativePath()
	{
		return $this->relativePath;
	}

	/**
	 * Determine if asset is a script.
	 * 
	 * @return bool
	 */
	public function isScript()
	{
		return in_array(pathinfo($this->absolutePath, PATHINFO_EXTENSION), array('js', 'coffee'));
	}

	/**
	 * Determine if asset is a style.
	 * 
	 * @return bool
	 */
	public function isStyle()
	{
		return ! $this->isScript();
	}

	/**
	 * Determine if asset is remotely hosted.
	 * 
	 * @return bool
	 */
	public function isRemote()
	{
		return parse_url($this->absolutePath, PHP_URL_SCHEME);
	}

	/**
	 * Sets the asset to be ignored.
	 * 
	 * @return JasonLewis\Basset\Asset
	 */
	public function ignore()
	{
		$this->ignored = true;

		return $this;
	}

	/**
	 * Determine if the asset is ignored.
	 * 
	 * @return bool
	 */
	public function isIgnored()
	{
		return $this->ignored;
	}

	/**
	 * Apply a filter to the asset.
	 * 
	 * @param  string  $filter
	 * @param  Closure  $callback
	 * @return JasonLewis\Basset\Filter
	 */
	public function apply($filter, Closure $callback = null)
	{
		$filter = new Filter($filter, $this);

		if (is_callable($callback))
		{
			call_user_func($callback, $filter);
		}

		return $this->filters[] = $filter;
	}

}