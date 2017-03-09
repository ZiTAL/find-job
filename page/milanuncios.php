<?php
require_once(PATH."/lib/curl.php");

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

        $ofertas = $xpath->query('//div[@id="cuerpo"]/div[@class="aditem"]');

        foreach($ofertas as $oferta)
        {
		$container = $xpath->query('div[@class="aditem-detail-image-container"]', $oferta);
		if($container->length<1)
			$container = $xpath->query('div[@class="aditem-detail-container"]', $oferta);
		$container = $container->item(0);

                $a = $xpath->query('div[@class="aditem-detail"]/a[@class="aditem-detail-title"]', $container)->item(0);
                $description = $xpath->query('div[@class="aditem-detail"]/div[@class="tx"]', $container)->item(0)->nodeValue;

		if(!$this->urlExists($a->getAttribute('href')))
		{
			$this->urlInsert($a->getAttribute('href'));
			$result[] = array
			(
				'title' => utf8_decode($a->nodeValue),
				'description' => utf8_decode($description),
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
