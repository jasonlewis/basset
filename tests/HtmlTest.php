<?php

use Basset\Html;

class HtmlTest extends PHPUnit_Framework_TestCase {


	public function testCanRenderLess()
	{
		$html = new Html('style', 'less', 'foo.less');

		$this->assertEquals('<link rel="stylesheet/less" href="foo.less">', $html->render());
	}


	public function testCanRenderCss()
	{
		$html = new Html('style', 'css', 'foo.css');

		$this->assertEquals('<link rel="stylesheet" href="foo.css">', $html->render());
	}


	public function testCanRenderCoffeeScript()
	{
		$html = new Html('script', 'coffee', 'foo.coffee');

		$this->assertEquals('<script type="text/coffeescript" src="foo.coffee"></script>', $html->render());
	}


	public function testCanRenderJavaScript()
	{
		$html = new Html('script', 'js', 'foo.js');

		$this->assertEquals('<script type="text/javascript" src="foo.js"></script>', $html->render());
	}


	public function testCanRenderFromToStringMethod()
	{
		$html = new Html('script', 'js', 'foo.js');

		ob_start();

		echo $html;

		$this->assertEquals('<script type="text/javascript" src="foo.js"></script>', ob_get_clean());
	}


}