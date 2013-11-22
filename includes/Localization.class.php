<?php

class Localization
{
	private $langDefault = 'en-US';
	private $lang, $langFallback;
	
	private $messages;
	
	private $langs = array(
		'af' => array('ZA'),
		'am' => array('ET'),
		'ar' => array(
			'AE',
			'BH',
			'DZ',
			'EG',
			'IQ',
			'JO',
			'KW',
			'LB',
			'LY',
			'MA',
			'OM',
			'QA',
			'SA',
			'SY',
			'TN',
			'YE'
		),
		'arn' => array('CL'),
		'as' => array('IN'),
		'az' => array(
			'Cyrl-AZ',
			'Latn-AZ'
		),
		'ba' => array('RU'),
		'be' => array('BY'),
		'bg' => array('BG'),
		'bn' => array(
			'BD',
			'IN'
		),
		'bo' => array('CN'),
		'br' => array('FR'),
		'bs' => array(
			'Cyrl-BA',
			'Latn-BA'
		),
		'ca' => array('ES'),
		'co' => array('FR'),
		'cs' => array('CZ'),
		'cy' => array('GB'),
		'da' => array('DK'),
		'de' => array(
			'AT',
			'CH',
			'DE',
			'LI',
			'LU'
		),
		'dsb' => array('DE'),
		'dv' => array('MV'),
		'el' => array('GR'),
		'en' => array(
			'029',
			'AU',
			'BZ',
			'CA',
			'GB',
			'IE',
			'IN',
			'JM',
			'MY',
			'NZ',
			'PH',
			'SG',
			'TT',
			'US',
			'ZA',
			'ZW'
		),
		'en' => array(
			'AR',
			'BO',
			'CL',
			'CO',
			'CR',
			'DO',
			'EC',
			'ES',
			'GT',
			'HN',
			'MX',
			'NI',
			'PA',
			'PE',
			'PR',
			'PY',
			'SV',
			'US',
			'UY',
			'VE'
		),
		'et' => array('EE'),
		'eu' => array('ES'),
		'fa' => array('IR'),
		'fi' => array('FI'),
		'fil' => array('PH'),
		'fo' => array('FO'),
		'fr' => array(
			'FR',
			'CA',
			'CH',
			'BE',
			'LU',
			'MC'
		),
		'fy' => array('NL'),
		'ga' => array('IE'),
		'gd' => array('GB'),
		'gl' => array('ES'),
		'gsw' => array('FR'),
		'gu' => array('IN'),
		'ha' => array('Latn-NG'),
		'he' => array('IL'),
		'hi' => array('IN'),
		'hr' => array(
			'BA',
			'HR'
		),
		'hsb' => array('DE'),
		'hu' => array('HU'),
		'hy' => array('AM'),
		'id' => array('ID'),
		'ig' => array('NG'),
		'ii' => array('CN'),
		'is' => array('IS'),
		'it' => array(
			'CH',
			'IT'
		),
		'iu' => array(
			'Cans-CA',
			'Latn-CA'
		),
		'ja' => array('JP'),
		'ka' => array('GE'),
		'kk' => array('KZ'),
		'kl' => array('GL'),
		'km' => array('KH'),
		'kn' => array('IN'),
		'kok' => array('IN'),
		'ko' => array('KR'),
		'ky' => array('KG'),
		'lb' => array('LU'),
		'lo' => array('LA'),
		'lt' => array('LT'),
		'lv' => array('LV'),
		'mi' => array('NZ'),
		'mk' => array('MK'),
		'ml' => array('IN'),
		'mn' => array(
			'MN',
			'Mong-CN'
		),
		'moh' => array('CA'),
		'mr' => array('IN'),
		'ms' => array(
			'BN',
			'MY'
		),
		'mt' => array('MT'),
		'nb' => array('NO'),
		'ne' => array('NP'),
		'nl' => array(
			'BE',
			'NL'
		),
		'nn' => array('NO'),
		'nso' => array('ZA'),
		'oc' => array('FR'),
		'or' => array('IN'),
		'pa' => array('IN'),
		'pl' => array('PL'),
		'prs' => array('AF'),
		'ps' => array('AF'),
		'pt' => array(
			'BR',
			'PT'
		),
		'qut' => array('GT'),
		'quz' => array(
			'BO',
			'EC',
			'PE'
		),
		'rm' => array('CH'),
		'ro' => array('RO'),
		'ru' => array('RU'),
		'rw' => array('RW'),
		'sah' => array('RU'),
		'sa' => array('IN'),
		'se' => array(
			'FI',
			'NO',
			'SE'
		),
		'si' => array('LK'),
		'sk' => array('SK'),
		'sl' => array('SI'),
		'sma' => array(
			'NO',
			'SE'
		),
		'smj' => array(
			'NO',
			'SE'
		),
		'smn' => array('FI'),
		'sms' => array('FI'),
		'sq' => array('AL'),
		'sr' => array(
			'Cyrl-BA',
			'Cyrl-CS',
			'Cyrl-ME',
			'Cyrl-RS',
			'Latn-BA',
			'Latn-CS',
			'Latn-ME',
			'Latn-RS'
		),
		'sv' => array(
			'FI',
			'SE'
		),
		'sw' => array('KE'),
		'syr' => array('SY'),
		'ta' => array('IN'),
		'te' => array('IN'),
		'tg' => array('Cyrl-TJ'),
		'th' => array('TH'),
		'tk' => array('TM'),
		'tn' => array('ZA'),
		'tr' => array('TR'),
		'tt' => array('RU'),
		'tzm' => array('LatnDZ'),
		'ug' => array('CN'),
		'uk' => array('UA'),
		'ur' => array('PK'),
		'uz' => array(
			'Cyrl-UZ',
			'Latn-UZ'
		),
		'vi' => array('VN'),
		'wo' => array('SN'),
		'xh' => array('ZA'),
		'yo' => array('NG'),
		'zh' => array(
			'CN',
			'HK',
			'MO',
			'SG',
			'TW'
		),
		'zu' => array('ZA')	
	);

	public function __construct()
	{
		\Locale::setDefault($this->langDefault);
		$this->setLang(\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']));

	}
	
	public function setLang($lang)
	{
		$lang = explode('-', $lang);
		if (!exists($this->langs, $lang[0]))
			$this->lang = $this->langFallback = $this->langDefault;
		else {
			
			$regions = $this->langs[$lang[0]];
			
			$this->langFallback = $lang[0] . '-' . $regions[0];
			
			if (!in_array(get($lang, k(1)), $regions))
				$this->lang = $lang[0] . '-' . $regions[0];
			else
				$this->lang = $lang;

		}

		$this->messages = array(
			$this->langDefault => array(),
			$this->langFallback => array(),
			$this->lang => array()
		);
		
		return $this->lang;
	}
	
	public function getLang()
	{
		return $this->lang;
	}
	
	public function storeMessage($dir, $key = null)
	{

		if ($key and exists($this->messages[$this->langDefault], $key))
			return;

		if (is_file($file = $dir . $this->langDefault . '.php')) {
			include $file;
			if ($key)
				$this->messages[$this->langDefault][$key] = $locale;
			else
				$this->messages[$this->langDefault] = $locale;
		}
	
		if (is_file($file = $dir . $this->langFallback . '.php')) {
			include $file;
			if ($key)
				$this->messages[$this->langFallback][$key] = $locale;
			else
				$this->messages[$this->langFallback] = $locale;
		}
	
		if (is_file($file = $dir . $this->lang . '.php')) {
			include $file;
			if ($key)
				$this->messages[$this->lang][$key] = $locale;
			else
				$this->messages[$this->lang] = $locale;
		}
	}

	public function getMessage($keys, $replace = array())
	{
		$keys = explode('.', $keys);
		if (null === $message = geta(array_merge(array($this->messages[$this->lang]), $keys)))
			if (null === $message = geta(array_merge(array($this->messages[$this->langFallback]), $keys)))
				if (null === $message = geta(array_merge(array($this->messages[$this->langDefault]), $keys)))
					$message = implode('.', $keys);
		
		foreach ($replace as $key => $value) {
			if (is_int($key))
				$key = '%' . $key;
			$message = str_replace($key, $value, $message);
		}

		return $message;
			
	}

}

?>
