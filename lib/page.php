<?php

class page
{
	public function __construct()
	{
	}
	
	protected function prepareTag($tag)
	{
		$tag = preg_replace("/\ /", '-', $tag);
		return $tag;
	}	
}