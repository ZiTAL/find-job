<?php
require_once('lib/curl.php');

class milanuncios extends page
{
	private $info;
	private $curl;
    
	private $base_url = 'http://www.milanuncios.com';
	private $url = '/ofertas-de-empleo-en-';
	
	private $province_array = array
	(
		'araba' => 'alava',
		'bizkaia' => 'vizcaya',
		'nafarroa' => 'navarra',
		'gipuzkoa' => 'guipuzcoa',
		'burgos' => 'burgos'
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
					$params = array
					(
						'demanda' => 'n',
						'pagina' => $i
					);
					$content = $this->curl->request($url, 'GET', $params);   
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

        $ofertas = $xpath->query("//div[@id=\"cuerpo\"]/div[@class=\"x1\"]");

        foreach($ofertas as $oferta)
        {
            $x9 = $xpath->query("div[@class=\"x9\"]", $oferta);
            $x7 = $xpath->query("div[@class=\"x7\"]", $oferta);

            if($x9->length>0)
                $x = $x9->item(0);
            else
                $x = $x7->item(0);

            if(isset($x))
            {
                $a = $xpath->query("a[@class=\"cti\"]", $x)->item(0);
					$description = $xpath->query("div[@class=\"tx\"]", $x)->item(0);
 
					$result[] = array
					(
						'title' => $a->nodeValue,
						'description' => $description->nodeValue,
						'link' => $this->base_url.$a->getAttribute('href')
					 );
            }
            unset($x);
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
			$url = $this->base_url.$this->url.$this->province_array[$probintzia]."/";
			$urls[$probintzia] = array();
			foreach($tagak as $tag)
			{
				$tag = $this->prepareTag($tag);
				$urls[$probintzia][] = $url.$tag.".htm";
			}
		}

		return $urls;
	}
}