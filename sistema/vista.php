<?php
class Vista {

	private $pageVars = array();
	private $template;

	public function __construct($template)
	{
		$this->template = APP .'vistas/'. $template .'.php';
	}

	public function set($var, $val)
	{
		$this->pageVars[$var] = $val;
	}

	public function renderizar()
	{
		extract($this->pageVars);

		ob_start();
		require($this->template);
		echo ob_get_clean();
	}
    
}