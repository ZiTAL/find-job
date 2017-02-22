<?php
require_once(PATH."/lib/curl.php");

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
	$ofertas = $xpath->query('//ul[@class="mt15 positions "]/li');

        foreach($ofertas as $oferta)
        {
		$a = $xpath->query("h2/a", $oferta);
		$description = $xpath->query('p[@class="description"]', $oferta);
		if($a->length>0 && $description->length>0)
		{
			$a = $a->item(0);
			$description = $description->item(0);

	                if(!$this->urlExists($a->getAttribute('href')))
        	        {
                	        $this->urlInsert($a->getAttribute('href'));

				$result[] = array
				(
					'title' => strip_tags($a->nodeValue),
					'description' => $description->nodeValue,
					'link' => $this->base_url.$a->getAttribute('href')
				);
			}
		}
        }
	print_r($result);
	exit();
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
