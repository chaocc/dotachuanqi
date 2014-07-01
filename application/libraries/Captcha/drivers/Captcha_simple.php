<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: Captcha_simple.php UTF-8 2013-11-27 下午9:13:24Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

! defined('IN_CAPTCHA') && define('IN_CAPTCHA', TRUE);
define('SIMPLE_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR.'simple'.DIRECTORY_SEPARATOR);

class Captcha_simple extends CI_Driver {

	/** Width of the image */
	public $width  = 200;
	
	/** Height of the image */
	public $height = 70;
	
	/** Enable $lineWidth && blur */
	public $color = 0;
	
	/** Dictionary word file (empty for random text) */
	private $wordsFile = 'words/en.php';
	
	/**
	 * Path for resource files (fonts, words, etc.)
	 *
	 * NULL by default. For security reasons, is better move this
	 * directory to another location outise the web server
	 *
	 */
	private $resourcesPath = NULL;
	
	/** Min word length (for non-dictionary random text generation) */
    private $minWordLength = 5;

    /**
     * Max word length (for non-dictionary random text generation)
     * 
     * Used for dictionary words indicating the word-length
     * for font-size modification purposes
     */
    private $maxWordLength = 8;
    
    /** word length */
    public $length = NULL;
    
    /** Background color in RGB-array */
    private $backgroundColor = array(255, 255, 255);
    
    /** Foreground colors in RGB-array */
    private $colors = array(
    	array(27,78,181), // blue
    	array(22,163,35), // green
    	array(214,36,7),  // red
    );
    
    /** shadow  */
    public $shadow = 0;
    
    /** Shadow color in RGB-array or null */
    private $shadowColor = array(204, 204, 204);
    
    /** Horizontal line through the text */
    public $lineWidth = 0;
    
    /**
     * Font configuration
     *
     * - font: TTF file
     * - spacing: relative pixel space between character
     * - minSize: min font size
     * - maxSize: max font size
     */
    private $fonts = array(
    	'Antykwa'  => array('spacing' => -3, 'minSize' => 27, 'maxSize' => 30, 'font' => 'AntykwaBold.ttf'),
    	'Candice'  => array('spacing' =>-1.5,'minSize' => 28, 'maxSize' => 31, 'font' => 'Candice.ttf'),
    	'DingDong' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'Ding-DongDaddyO.ttf'),
    	'Duality'  => array('spacing' => -2, 'minSize' => 30, 'maxSize' => 38, 'font' => 'Duality.ttf'),
    	'Heineken' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 34, 'font' => 'Heineken.ttf'),
    	'Jura'     => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 32, 'font' => 'Jura.ttf'),
    	'StayPuft' => array('spacing' =>-1.5,'minSize' => 28, 'maxSize' => 32, 'font' => 'StayPuft.ttf'),
    	'Times'    => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 34, 'font' => 'TimesNewRomanBold.ttf'),
    	'VeraSans' => array('spacing' => -1, 'minSize' => 20, 'maxSize' => 28, 'font' => 'VeraSansBold.ttf'),
    );
    
    /** Wave configuracion in X and Y axes */
    private $Yperiod    = 12;
    private $Yamplitude = 14;
    private $Xperiod    = 11;
    private $Xamplitude = 5;

    /** letter rotation clockwise */
    private $maxRotation = 8;

    /**
     * Internal image size factor (for better image quality)
     * 1: low, 2: medium, 3: high
     */
    private $scale = 2;

    /** 
     * Blur effect for better image quality (but slower image processing).
     * Better image results with scale=3
     */
    private $blur = false;

    /** Debug? */
    private $debug = false;
    
    /** Image format: jpeg or png */
    private $imageFormat = 'png';


    /** GD image */
    private $im = NULL;
    private $GdFgColor = NULL; // 如果使用了 __set 魔法函数，必须声明 $this->变量  的变量，否则可能无法给其赋值
    private $GdShadowColor = NULL;
    private $GdBgColor = NULL;
    private $textFinalX = NULL;
    
    /**  验证码    */
    private $code = NULL;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
    	$this->resourcesPath = SIMPLE_ROOT;
    }
    
    /**
     * 生成验证码
     */
    public function display()
    {
    	$ini = microtime(true);
    	
    	if ($this->color) {
    		$this->blur = TRUE;
    		$this->lineWidth = 2;
    	}
    	
    	/** Initialization */
        $this->_ImageAllocate();
        
        /** Text insertion */
        $fontcfg  = $this->fonts[array_rand($this->fonts)];
        $this->_WriteText($this->code, $fontcfg);
        
        /** Transformations */
        if (!empty($this->lineWidth)) {
        	$this->_WriteLine();
        }
        $this->_WaveImage();
        if ($this->blur && function_exists('imagefilter')) {
        	imagefilter($this->im, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $this->_ReduceImage();        
        
        if ($this->debug) {
        	imagestring($this->im, 1, 1, $this->height-8,
        	"{$this->code} {$fontcfg['font']} ".round((microtime(true)-$ini)*1000)."ms",
        	$this->GdFgColor
        	);
        }       
        
        /** Output */
        $this->_WriteImage();
        $this->_Cleanup();
    }
    
    /**
     * 生成验证码
     */
    public function get_code()
    {
    	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
    	
    	! $this->code && $this->code = $this->_GetCaptchaText();
    	
    	return $this->code;
    }
    
    /**
     * Creates the image resources
     */
    protected function _ImageAllocate() {
    	// Cleanup
    	if (!empty($this->im)) {
    		imagedestroy($this->im);
    	}
    
    	$this->im = imagecreatetruecolor($this->width*$this->scale, $this->height*$this->scale);

    	// Background color
    	$this->GdBgColor = imagecolorallocate($this->im, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
    	
    	imagefilledrectangle($this->im, 0, 0, $this->width*$this->scale, $this->height*$this->scale, $this->GdBgColor);
    
    	// Foreground color
    	$color           = $this->colors[mt_rand(0, sizeof($this->colors)-1)];
    	$this->GdFgColor = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);
    
    	// Shadow color
    	if ($this->shadow && !empty($this->shadowColor) && is_array($this->shadowColor) && sizeof($this->shadowColor) >= 3) {
    		$this->GdShadowColor = imagecolorallocate($this->im,
    				$this->shadowColor[0],
    				$this->shadowColor[1],
    				$this->shadowColor[2]
    		);
    	}
    }
    
	/**
     * Text generation
     *
     * @return string Text
     */
    private function _GetCaptchaText() {
        $text = $this->_GetDictionaryCaptchaText();
        if ( ! $text OR ($this->length > 0 && strlen($text) < $this->length) ) {
            $text = $this->_GetRandomCaptchaText($this->length);
        }
        return $text;
    }
    
    /**
     * Random dictionary word generation
     *
     * @param boolean $extended Add extended "fake" words
     * @return string Word
     */
    function _GetDictionaryCaptchaText($extended = false) {
    	if (empty($this->wordsFile)) {
    		return false;
    	}
    
    	// Full path of words file
    	if (substr($this->wordsFile, 0, 1) == '/') {
    		$wordsfile = $this->wordsFile;
    	} else {
    		$wordsfile = $this->resourcesPath.$this->wordsFile;
    	}
    
    	if ( ! file_exists($wordsfile)) {
    		return false;
    	}
    
    	$fp     = fopen($wordsfile, "rb");
    	$length = strlen(fgets($fp));
    	if ( ! $length) {
    		return false;
    	}
    	$line   = rand(1, ( filesize($wordsfile) / $length ) - 2 );
    	if (fseek($fp, $length * $line) == -1) {
    		return false;
    	}
    	$text = trim(fgets($fp));
    	fclose($fp);    
    
    	/** Change ramdom volcals */
    	if ($extended) {
    		$text   = preg_split('//', $text, -1, PREG_SPLIT_NO_EMPTY);
    		$vocals = array('a', 'e', 'i', 'o', 'u');
    		foreach ($text as $i => $char) {
    			if (mt_rand(0, 1) && in_array($char, $vocals)) {
    				$text[$i] = $vocals[mt_rand(0, 4)];
    			}
    		}
    		$text = implode('', $text);
    	}
    
    	return $text;
    }
    
    /**
     * Random text generation
     *
     * @return string Text
     */
    protected function _GetRandomCaptchaText($length = null) {
    	if (empty($length)) {
    		$length = rand( $this->minWordLength, $this->maxWordLength );
    	}
    
    	$words  = "abcdefghijlmnopqrstvwyz";
    	$vocals = "aeiou";
    
    	$text  = "";
    	$vocal = rand(0, 1);
    	for ($i=0; $i<$length; $i++) {
    		if ($vocal) {
    			$text .= substr($vocals, mt_rand(0, 4), 1);
    		} else {
    			$text .= substr($words, mt_rand(0, 22), 1);
    		}
    		$vocal = !$vocal;
    	}
    	return $text;
    }
    
    /**
     * Text insertion
     */
    protected function _WriteText($text, $fontcfg = array()) {
    	if (empty($fontcfg)) {
    		// Select the font configuration
    		$fontcfg  = $this->fonts[array_rand($this->fonts)];
    	}
    
    	// Full path of font file
    	$fontfile = $this->resourcesPath.'fonts/'.$fontcfg['font'];
    
    	/** Increase font-size for shortest words: 9% for each glyp missing */
    	$lettersMissing = $this->maxWordLength - strlen($text);
    	$fontSizefactor = 1 + ($lettersMissing*0.09);
    
    	// Text generation (char by char)
    	$x      = 20*$this->scale;
    	$y      = round(($this->height*27/40)*$this->scale);
    	$length = strlen($text);
    	for ($i=0; $i<$length; $i++) {
    		$degree   = rand($this->maxRotation*-1, $this->maxRotation);
    		$fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$this->scale*$fontSizefactor;
    		$letter   = substr($text, $i, 1);
    
    		if ($this->shadow && $this->shadowColor) {
    			$coords = imagettftext($this->im, $fontsize, $degree,
    					$x+$this->scale, $y+$this->scale,
    					$this->GdShadowColor, $fontfile, $letter);
    		}
    		$coords = imagettftext($this->im, $fontsize, $degree,
    				$x, $y,
    				$this->GdFgColor, $fontfile, $letter);
    		$x += ($coords[2]-$x) + ($fontcfg['spacing']*$this->scale);
    	}
    
    	$this->textFinalX = $x;
    }
    
    /**
     * Horizontal line insertion
     */
    protected function _WriteLine() {
    
    	$x1 = $this->width*$this->scale*.15;
    	$x2 = $this->textFinalX;
    	$y1 = rand($this->height*$this->scale*.40, $this->height*$this->scale*.65);
    	$y2 = rand($this->height*$this->scale*.40, $this->height*$this->scale*.65);
    	$width = $this->lineWidth/2*$this->scale;
    
    	for ($i = $width*-1; $i <= $width; $i++) {
    		imageline($this->im, $x1, $y1+$i, $x2, $y2+$i, $this->GdFgColor);
    	}
    }
    
    /**
     * Wave filter
     */
    protected function _WaveImage() {
    	// X-axis wave generation
    	$xp = $this->scale*$this->Xperiod*rand(1,3);
    	$k = rand(0, 100);
    	for ($i = 0; $i < ($this->width*$this->scale); $i++) {
    		imagecopy($this->im, $this->im,
    		$i-1, sin($k+$i/$xp) * ($this->scale*$this->Xamplitude),
    		$i, 0, 1, $this->height*$this->scale);
    	}
    
    	// Y-axis wave generation
    	$k = rand(0, 100);
    	$yp = $this->scale*$this->Yperiod*rand(1,2);
    	for ($i = 0; $i < ($this->height*$this->scale); $i++) {
    		imagecopy($this->im, $this->im,
    		sin($k+$i/$yp) * ($this->scale*$this->Yamplitude), $i-1,
    		0, $i, $this->width*$this->scale, 1);
    	}
    }
    
    /**
     * Reduce the image to the final size
     */
    protected function _ReduceImage() {
    	$imResampled = imagecreatetruecolor($this->width, $this->height);
    	imagecopyresampled($imResampled, $this->im,
    	0, 0, 0, 0,
    	$this->width, $this->height,
    	$this->width*$this->scale, $this->height*$this->scale
    	);
    	imagedestroy($this->im);
    	$this->im = $imResampled;
    }
    
    /**
     * File generation
     */
    protected function _WriteImage() {
    	if ($this->imageFormat == 'png' && function_exists('imagepng')) {
    		header("Content-type: image/png");
    		imagepng($this->im, NULL, 9);
    	} else {
    		header("Content-type: image/jpeg");
    		imagejpeg($this->im, null, 100);
    	}
    }
    
    /**
     * Cleanup
     */
    protected function _Cleanup() {
    	imagedestroy($this->im);
    }
}

/* End of file Captcha_simple.php */
/* Location: ./application/libraries/Captcha/drivers/Captcha_simple.php */