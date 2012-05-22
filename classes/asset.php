<?php namespace Basset;

use BadMethodCallException, URL, Basset, HTML;

class Asset {

	/**
	 * @var string $group
	 */
	public $group;

	/**
	 * @var string $name
	 */
	public $name;

	/**
	 * @var string $file
	 */
	public $file;

	/**
	 * @var bool $less
	 */
	public $less = false;

	/**
	 * @var bool $bundle
	 */
	public $bundle = false;

	/**
	 * @var bool $external
	 */
	public $external = false;

	/**
	 * @var int @updated
	 */
	public $updated = 0;

	/**
	 * @var string $source
	 */
	public $source;

	/**
	 * __construct
	 *
	 * Create a new Asset object and prepare the new asset.
	 *
	 * @param  string  $name
	 * @param  string  $file
	 * @param  string  $group
	 * @param  string  $extension
	 * @param  string  $directory
	 * @param  array   $dependencies
	 */
	public function __construct($name, $file, $group, $extension, $directory, $dependencies)
	{
		$this->name = $name;

		$this->file = $file;

		$this->group = $group;

		$this->directory = $directory;

		$this->less = ($extension == 'less');

		$this->dependencies = (array) $dependencies;

		// In order of priority. If using a defined source we'll stick to that,
		// or if we can find a prefixed bundle we'll attempt to use that. Last
		// option is to use the standard public path.
		if(!is_null($this->directory))
		{
			$this->source = path('base') . $this->directory;
		}
		elseif(strpos($file, '::') !== false)
		{
			list($bundle, $file) = explode('::', $file);

			$this->source = path('public') . Basset::corrector(Bundle::assets($bundle));

			$this->bundle = true;
		}
		else
		{
			$this->source = path('public') . Basset::corrector(URL::to_asset('/'));
		}

		// If the source has not been specified the public directory or bundle
		// directory is being used, by default we'll go to the public directory
		// and depending on the asset group we'll add the css or js folder.
		if(is_null($this->directory) && strpos($this->file, '/') == false)
		{
			$this->source .= ($this->group == 'styles' ? 'css' : 'js');
		}

		$this->source = realpath($this->source);
	}

	/**
	 * get
	 *
	 * Gets the contents of an asset and if it's a stylesheet it is run through the
	 * URI rewriter to correct any ill-formed directories.
	 *
	 * @param  array   $symlinks
	 * @param  string  $document_root
	 * @return string
	 */
	public function get($symlinks = array(), $document_root = '')
	{
		$fail = PHP_EOL . '/* Basset could not find asset [' . $this->name . '] */' . PHP_EOL;

		if(!$this->exists())
		{
			return $fail;
		}

		$contents = @file_get_contents($this->source . DS . $this->file);

		if(empty($contents))
		{
			return $fail;
		}

		if($this->group == 'styles')
		{
			$contents = Vendor\URIRewriter::rewrite($contents, dirname($this->source .DS . $this->file), $document_root, $symlinks);
		}

		if($this->less && Config::get('less.php'))
		{
			$less = new Vendor\lessc;

			$contents = $less->parse($contents);
		}

		return $contents . PHP_EOL;
	}

	/**
	 * html
	 * 
	 * Gets the HTML tag for the asset with the correct URL.
	 * 
	 * @return string
	 */
	public function html()
	{
		$url = URL::to_asset(str_replace(array(path('base') . 'public', '\\'), array('', '/'), $this->source) . '/' . $this->file);

		$attributes = array();

		if($this->group == 'styles')
		{
			if($this->less)
			{
				$attributes = array('rel' => 'stylesheet/less');
			}

			return HTML::style($url, $attributes);
		}
		else
		{
			return HTML::script($url);
		}
	}


	/**
	 * exists
	 * 
	 * Determines if the asset exists.
	 * 
	 * @return mixed
	 */
	public function exists()
	{
		if(!parse_url($this->file, PHP_URL_SCHEME))
		{
			if(!file_exists($path = ($this->source . DS . $this->file)))
			{
				return false;
			}
		}
		else
		{
			$this->external = true;
		}

		return true;
	}

}