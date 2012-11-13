<?php namespace Basset;

class Html {

	/**
	 * HTML tag group.
	 * 
	 * @var string
	 */
	protected $group;

	/**
	 * Extension of asset.
	 * 
	 * @var string
	 */
	protected $extension;

	/**
	 * URL to asset.
	 * 
	 * @var string
	 */
	protected $url;

	/**
	 * Create a new html instance.
	 * 
	 * @param  string  $group
	 * @param  string  $extension
	 * @param  string  $url
	 * @return void
	 */
	public function __construct($group, $extension, $url)
	{
		$this->group = $group;
		$this->extension = $extension;
		$this->url = $url;
	}

	/**
	 * Render the HTML output.
	 * 
	 * @return string
	 */
	public function render()
	{
		switch ($this->group)
		{
			// Generate the HTML link tag for stylesheets.
			case 'style':
				switch ($this->extension)
				{
					// LESS use a different rel attribute on the link tag. So we'll give LESS the one it needs and other stylesheets
					// can use the standard attribute.
					case 'less':
						return '<link rel="stylesheet/less" href="'.$this->url.'">';
						break;
					default:
						return '<link rel="stylesheet" href="'.$this->url.'">';
						break;
				}
				break;

			// Generate the HTML script tag for scripts.
			case 'script':
				switch ($this->extension)
				{
					// CoffeeScript use a different type attribute on the script tag, this is similar to LESS in the above case.
					case 'coffee':
						return '<script type="text/coffeescript" src="'.$this->url.'"></script>';
						break;
					default:
						return '<script type="text/javascript" src="'.$this->url.'"></script>';
						break;
				}
				break;
		}
	}

	/**
	 * Render the HTML output.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

}