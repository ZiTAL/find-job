<?php
require_once('lib/curl.php');

class infoempleo extends page
{
	private $info;
	private $curl;
    
	private $base_url = 'http://www.infoempleo.com';
	private $url = '/trabajo/i/';
	
	private $province_array = array
	(
		'araba' => 'en_alava',
		'bizkaia' => 'en_vizcaya',
		'nafarroa' => 'en_navarra',
		'gipuzkoa' => 'en_guipuzcoa',
		'burgos' => 'en_burgos'
	);
    
	public function __construct($info)
	{
		$this->info = $info;
		$this->curl = new curl();
	}
    
	public function request()
	{
		$urls = $this->prepareUrls();
		
		$result = array();
		foreach($urls as $province => $value)
		{
			$result[$province] = array();
			foreach($value as $url)
			{
				$i = 1;
				do
				{
					//http://www.infoempleo.com/trabajo/i/operario/en_vizcaya/pagina_2/
					$_url = $url."pagina_".$i."/";
					$content = $this->curl->request($_url);   
					$url_array = $this->parseUrl($content);
					$result[$province] = array_merge($result[$province], $url_array);
					$i++;
				}
				while(count($url_array)>0);
			}
		}
            
		return $result;
	}
    
    private function parseUrl($content)
    {
		$dom = new DOMDocument('1.0', 'utf-8');
		@$dom->loadHTML($content);

		$xpath = new DOMXpath($dom);

		$result = array();
		 $ofertas = $xpath->query("//table[@class=\"tabla-ofertas ofertas-noportada tabla-menor tabla-menor2\"]/tbody/tr/th[@scope=\"row\"]"); 

        foreach($ofertas as $oferta)
        {
            $a = $xpath->query("a", $oferta);
			 	$span = $xpath->query("span", $oferta);
			 	if($a->length>0 && $span->length>0)
				{
					$a = $a->item(0);
					$span = $span->item(0);

					$result[] = array
					(
						'title' => strip_tags($a->nodeValue),
						'description' => $span->nodeValue,
						'link' => $this->base_url.$a->getAttribute('href')
					 );
				}
        }
        return $result;
    }
	
	private function prepareUrls()
	{
		$urls = array();
		$probintziak = $this->info['probintziak'];
		$tagak = $this->info['tagak'];
		foreach($probintziak as $probintzia)
		{	
			$urls[$probintzia] = array();
			foreach($tagak as $tag)
			{
				//http://www.infoempleo.com/trabajo/i/operario/en_vizcaya/pagina_2/
			
				$tag = $this->prepareTag($tag);
				$url = $this->base_url.$this->url.$tag."/".$this->province_array[$probintzia]."/";
				
				$urls[$probintzia][] = $url;
			}
		}
		
		return $urls;
	}
}