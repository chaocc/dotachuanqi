<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: Captcha_securimage.php UTF-8 2013-11-28 下午1:42:40Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

! defined('IN_CAPTCHA') && define('IN_CAPTCHA', TRUE);
define('SECUREIMG_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR.'securimage'.DIRECTORY_SEPARATOR);

class Captcha_securimage extends CI_Driver {

	/**
	 * Renders captcha as a JPEG image
	 * @var int
	 */
	const SI_IMAGE_JPEG = 1;
	/**
	 * Renders captcha as a PNG image (default)
	 * @var int
	 */
	const SI_IMAGE_PNG  = 2;
	/**
	 * Renders captcha as a GIF image
	 * @var int
	 */
	const SI_IMAGE_GIF  = 3;
	
	/**
	 * Create a normal alphanumeric captcha
	 * @var int
	 */
	const SI_CAPTCHA_STRING     = 0;
	/**
	 * Create a captcha consisting of a simple math problem
	 * @var int
	 */
	const SI_CAPTCHA_MATHEMATIC = 1;
	/**
	 * Create a word based captcha using 2 words
	 * @var int
	 */
	const SI_CAPTCHA_WORDS      = 2;
	
	/*%*********************************************************************%*/
	// Properties
	
	/**
	 * The width of the captcha image
	 * @var int
	 */
	public $width = 215;
	private $image_width = 215;
	/**
	 * The height of the captcha image
	 * @var int
	 */
	public $height = 80;
	private $image_height = 80;
	
	/** Random background */
	public $background	= 1;
	/**
	 * The type of the image, default = png
	 * @var int
	 */
	private $image_type   = self::SI_IMAGE_PNG;
	
	/**
	 * The background color of the captcha
	 * @var Securimage_Color
	 */
	private $image_bg_color = '#ffffff';
	/**
	 * The color of the captcha text
	 * @var Securimage_Color
	 */
	private $text_color     = '#707070';
	/**
	 * The color of the lines over the captcha
	 * @var Securimage_Color
	 */
	private $line_color     = '#707070';
	/**
	 * The color of the noise that is drawn
	 * @var Securimage_Color
	 */
	public $color = 0;
	private $noise_color    = '#707070';
	
	/**
	 * How transparent to make the text 0 = completely opaque, 100 = invisible
	 * @var int
	 */
	private $text_transparency_percentage = 20;
	/**
	 * Whether or not to draw the text transparently, true = use transparency, false = no transparency
	 * @var bool
	 */
	private $use_transparent_text         = true;
	
	/**
	 * The length of the captcha code
	 * @var int
	 */
	public $length = 6;
	private $code_length    = 6;
	
	/**
     * The character set to use for generating the captcha code
     * @var string
     */
    private $charset        = 'ABCDEFGHKLMNPRSTUVWYZabcdefghklmnprstuvwyz23456789';
    /**
     * true to use the wordlist file, false to generate random captcha codes
     * @var bool
     */
    private $use_wordlist   = false;

    /**
     * The level of distortion, 0.75 = normal, 1.0 = very high distortion
     * @var double
     */
    private $perturbation = 0.85;
    /**
     * How many lines to draw over the captcha code to increase security
     * @var int
     */
    private $num_lines    = 5;
    public $warping = 1;
    /**
     * The level of noise (random dots) to place on the image, 0-10
     * @var int
     */
    private $noise_level  = 2;

    /**
     * The signature text to draw on the bottom corner of the image
     * @var string
     */
    private $image_signature = '';
    /**
     * The color of the signature text
     * @var Securimage_Color
     */
    private $signature_color = '#707070';
    /**
     * The path to the ttf font file to use for the signature text, defaults to $ttf_file (AHGBold.ttf)
     * @var string
     */
    private $signature_font;

    /**
     * The type of captcha to create, either alphanumeric, or a math problem<br />
     * Securimage::SI_CAPTCHA_STRING or Securimage::SI_CAPTCHA_MATHEMATIC
     * @var int
     */
    private $captcha_type  = self::SI_CAPTCHA_STRING; // or self::SI_CAPTCHA_MATHEMATIC;

    /**
     * The font file to use to draw the captcha code, leave blank for default font AHGBold.ttf
     * @var string
     */
    private $ttf_file;
    /**
     * The path to the wordlist file to use, leave blank for default words/words.txt
     * @var string
     */
    private $wordlist_file;
    /**
     * The directory to scan for background images, if set a random background will be chosen from this folder
     * @var string
     */
    private $background_directory;

    protected $im;
    protected $tmpimg;
    protected $bgimg;
    protected $iscale = 5;

    /**  Root path */
    public $securimage_path = null;

    /**
     * The captcha challenge value (either the case-sensitive/insensitive word captcha, or the solution to the math captcha)
     *
     * @var string Captcha challenge value
     */
    protected $code;
    
    /**
     * The display value of the captcha to draw on the image (the word captcha, or the math equation to present to the user)
     *
     * @var string Captcha display value to draw on the image
     */
    protected $code_display;

    // gd color resources that are allocated for drawing the image
    protected $gdbgcolor;
    protected $gdtextcolor;
    protected $gdlinecolor;
    protected $gdnoisecolor;
    protected $gdsignaturecolor;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
    	$this->securimage_path = SECUREIMG_ROOT;
    	
    }
    
    /**
     * 输出
     */
    public function display()
    {
    	require_once $this->securimage_path.'Securimage_Color.php';
    	
    	$this->image_bg_color  = $this->_initColor($this->image_bg_color,  '#ffffff');
    	$this->text_color      = $this->_initColor($this->text_color,      '#616161');
    	$this->line_color      = $this->_initColor($this->line_color,      '#616161');
    	$this->noise_color     = $this->_initColor($this->noise_color,     '#616161');
    	$this->signature_color = $this->_initColor($this->signature_color, '#616161');
    	
    	if (is_null($this->ttf_file)) {
    		$this->ttf_file = $this->securimage_path . 'AHGBold.ttf';
    	}
    	
    	if (is_null($this->perturbation) || !is_numeric($this->perturbation)) {
    		$this->perturbation = 0.75;
    	}
    	
    	$this->signature_font = $this->ttf_file;
    	$this->image_width = $this->width;
    	$this->image_height = $this->height;
    	
    	$this->_background();
    	
    	$this->_doImage();
    }
    
    /**
     * 生成验证码
     */
    public function get_code()
    {
    	PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
    	
    	if (is_null($this->wordlist_file)) {
    		$this->wordlist_file = $this->securimage_path . 'words/words.txt';
    	}
    	
    	$this->code_length = $this->length;
    	 
    	! $this->code && $this->_createCode();
    	 
    	return $this->code;
    }
    
    /**
     * 获得背景图
     */
    private function _background()
    {
    	$this->background_directory = $this->securimage_path.'background/';
    	
    	if ($this->background) {
    		$backgrounds = array();
    		if( $handle = @opendir($this->background_directory) ) {
    			while( $bgfile = @readdir($handle) ) {
    				if(preg_match('/\.(jpg|gif|png)$/i', $bgfile)) {
    					$backgrounds[] = $this->background_directory.$bgfile;
    				}
    			}
    			@closedir($handle);
    		}
    		
    		if($backgrounds) {
    			$this->bgimg = $backgrounds[array_rand($backgrounds)];
    		}
    	}
    }
    
    /**
     * The main image drawing routing, responsible for constructing the entire image and serving it
     */
    protected function _doImage()
    {
    	if( ($this->use_transparent_text == true || $this->bgimg != '') && function_exists('imagecreatetruecolor')) {
    		$imagecreate = 'imagecreatetruecolor';
    	} else {
    		$imagecreate = 'imagecreate';
    	}
    
    	$this->im     = $imagecreate($this->image_width, $this->image_height);
    	$this->tmpimg = $imagecreate($this->image_width * $this->iscale, $this->image_height * $this->iscale);
    
    	$this->_allocateColors();
    	imagepalettecopy($this->tmpimg, $this->im);
    
    	$this->_setBackground(); 
    
    	if ($this->color && $this->noise_level > 0) {
    		$this->_drawNoise();
    	}
    
    	$this->_drawWord();
    
    	if ($this->perturbation > 0 && is_readable($this->ttf_file)) {
    		$this->_distortedCopy();
    	}
    
    	if ($this->warping && $this->num_lines > 0) {
    		$this->_drawLines();
    	}
    
    	if (trim($this->image_signature) != '') {
    		$this->_addSignature();
    	}
    
    	$this->_output();
    }
    
    /**
     * Allocate the colors to be used for the image
     */
    protected function _allocateColors()
    {
    	// allocate bg color first for imagecreate
    	$this->gdbgcolor = imagecolorallocate($this->im,
    			$this->image_bg_color->r,
    			$this->image_bg_color->g,
    			$this->image_bg_color->b);
    
    	$alpha = intval($this->text_transparency_percentage / 100 * 127);
    
    	if ($this->use_transparent_text == true) {
    		$this->gdtextcolor = imagecolorallocatealpha($this->im,
    				$this->text_color->r,
    				$this->text_color->g,
    				$this->text_color->b,
    				$alpha);
    		$this->gdlinecolor = imagecolorallocatealpha($this->im,
    				$this->line_color->r,
    				$this->line_color->g,
    				$this->line_color->b,
    				$alpha);
    		$this->gdnoisecolor = imagecolorallocatealpha($this->im,
    				$this->noise_color->r,
    				$this->noise_color->g,
    				$this->noise_color->b,
    				$alpha);
    	} else {
    		$this->gdtextcolor = imagecolorallocate($this->im,
    				$this->text_color->r,
    				$this->text_color->g,
    				$this->text_color->b);
    		$this->gdlinecolor = imagecolorallocate($this->im,
    				$this->line_color->r,
    				$this->line_color->g,
    				$this->line_color->b);
    		$this->gdnoisecolor = imagecolorallocate($this->im,
    				$this->noise_color->r,
    				$this->noise_color->g,
    				$this->noise_color->b);
    	}
    
    	$this->gdsignaturecolor = imagecolorallocate($this->im,
    			$this->signature_color->r,
    			$this->signature_color->g,
    			$this->signature_color->b);
    
    }
    
    /**
     * The the background color, or background image to be used
     */
    protected function _setBackground()
    {
    	// set background color of image by drawing a rectangle since imagecreatetruecolor doesn't set a bg color
    	imagefilledrectangle($this->im, 0, 0,
    	$this->image_width, $this->image_height,
    	$this->gdbgcolor);
    	imagefilledrectangle($this->tmpimg, 0, 0,
    	$this->image_width * $this->iscale, $this->image_height * $this->iscale,
    	$this->gdbgcolor);
    
    	if ($this->bgimg == '') {
    		if ($this->background_directory != null &&
    		is_dir($this->background_directory) &&
    		is_readable($this->background_directory))
    		{
    			$img = $this->_getBackgroundFromDirectory();
    			if ($img != false) {
    				$this->bgimg = $img;
    			}
    		}
    	}
    
    	if ($this->bgimg == '') {
    		return;
    	}
    
    	$dat = @getimagesize($this->bgimg);
    	if($dat == false) {
    		return;
    	}
    
    	switch($dat[2]) {
    		case 1:  $newim = @imagecreatefromgif($this->bgimg); break;
    		case 2:  $newim = @imagecreatefromjpeg($this->bgimg); break;
    		case 3:  $newim = @imagecreatefrompng($this->bgimg); break;
    		default: return;
    	}
    
    	if(!$newim) return;
    
    	imagecopyresized($this->im, $newim, 0, 0, 0, 0,
    	$this->image_width, $this->image_height,
    	imagesx($newim), imagesy($newim));
    }
    
    /**
     * Scan the directory for a background image to use
     */
    protected function _getBackgroundFromDirectory()
    {
    	$images = array();
    
    	if ( ($dh = opendir($this->background_directory)) !== false) {
    		while (($file = readdir($dh)) !== false) {
    			if (preg_match('/(jpg|gif|png)$/i', $file)) $images[] = $file;
    		}
    
    		closedir($dh);
    
    		if (sizeof($images) > 0) {
    			return rtrim($this->background_directory, '/') . '/' . $images[0]; //mt_rand(0, sizeof($images)-1)
    		}
    	}
    
    	return false;
    }
    
    /**
     * Draws random noise on the image
     */
    protected function _drawNoise()
    {
        if ($this->noise_level > 10) {
            $noise_level = 10;
        } else {
            $noise_level = $this->noise_level;
        }

        $t0 = microtime(true);

        $noise_level *= 125; // an arbitrary number that works well on a 1-10 scale

        $points = $this->image_width * $this->image_height * $this->iscale;
        $height = $this->image_height * $this->iscale;
        $width  = $this->image_width * $this->iscale;
        for ($i = 0; $i < $noise_level; ++$i) {
            $x = mt_rand(10, $width);
            $y = mt_rand(10, $height);
            $size = mt_rand(7, 10);
            if ($x - $size <= 0 && $y - $size <= 0) continue; // dont cover 0,0 since it is used by imagedistortedcopy
            imagefilledarc($this->tmpimg, $x, $y, $size, $size, 0, 360, $this->gdnoisecolor, IMG_ARC_PIE);
        }

        $t1 = microtime(true);

        $t = $t1 - $t0;
    }
    
    /**
     * Draws the captcha code on the image
     */
    protected function _drawWord()
    {
    	$width2  = $this->image_width * $this->iscale;
    	$height2 = $this->image_height * $this->iscale;
    
    	if (!is_readable($this->ttf_file)) {
    		imagestring($this->im, 4, 10, ($this->image_height / 2) - 5, 'Failed to load TTF font file!', $this->gdtextcolor);
    	} else {
    		if ($this->perturbation > 0) {
    			$font_size = $height2 * .4;
    			$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
    			$tx = $bb[4] - $bb[0];
    			$ty = $bb[5] - $bb[1];
    			$x  = floor($width2 / 2 - $tx / 2 - $bb[0]);
    			$y  = round($height2 / 2 - $ty / 2 - $bb[1]);
    
    			imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
    		} else {
    			$font_size = $this->image_height * .4;
    			$bb = imageftbbox($font_size, 0, $this->ttf_file, $this->code_display);
    			$tx = $bb[4] - $bb[0];
    			$ty = $bb[5] - $bb[1];
    			$x  = floor($this->image_width / 2 - $tx / 2 - $bb[0]);
    			$y  = round($this->image_height / 2 - $ty / 2 - $bb[1]);
    
    			imagettftext($this->im, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $this->code_display);
    		}
    	}
    }
    
    /**
     * Copies the captcha image to the final image with distortion applied
     */
    protected function _distortedCopy()
    {
    	$numpoles = 3; // distortion factor
    	// make array of poles AKA attractor points
    	for ($i = 0; $i < $numpoles; ++ $i) {
    		$px[$i]  = mt_rand($this->image_width  * 0.2, $this->image_width  * 0.8);
    		$py[$i]  = mt_rand($this->image_height * 0.2, $this->image_height * 0.8);
    		$rad[$i] = mt_rand($this->image_height * 0.2, $this->image_height * 0.8);
    		$tmp     = ((- $this->_frand()) * 0.15) - .15;
    		$amp[$i] = $this->perturbation * $tmp;
    	}
    
    	$bgCol = imagecolorat($this->tmpimg, 0, 0);
    	$width2 = $this->iscale * $this->image_width;
    	$height2 = $this->iscale * $this->image_height;
    	imagepalettecopy($this->im, $this->tmpimg); // copy palette to final image so text colors come across
    	// loop over $img pixels, take pixels from $tmpimg with distortion field
    	for ($ix = 0; $ix < $this->image_width; ++ $ix) {
    		for ($iy = 0; $iy < $this->image_height; ++ $iy) {
    			$x = $ix;
    			$y = $iy;
    			for ($i = 0; $i < $numpoles; ++ $i) {
    				$dx = $ix - $px[$i];
    				$dy = $iy - $py[$i];
    				if ($dx == 0 && $dy == 0) {
    					continue;
    				}
    				$r = sqrt($dx * $dx + $dy * $dy);
    				if ($r > $rad[$i]) {
    					continue;
    				}
    				$rscale = $amp[$i] * sin(3.14 * $r / $rad[$i]);
    				$x += $dx * $rscale;
    				$y += $dy * $rscale;
    			}
    			$c = $bgCol;
    			$x *= $this->iscale;
    			$y *= $this->iscale;
    			if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2) {
    				$c = imagecolorat($this->tmpimg, $x, $y);
    			}
    			if ($c != $bgCol) { // only copy pixels of letters to preserve any background image
    				imagesetpixel($this->im, $ix, $iy, $c);
    			}
    		}
    	}
    }
    
    /**
     * Draws distorted lines on the image
     */
    protected function _drawLines()
    {
    	for ($line = 0; $line < $this->num_lines; ++ $line) {
    		$x = $this->image_width * (1 + $line) / ($this->num_lines + 1);
    		$x += (0.5 - $this->_frand()) * $this->image_width / $this->num_lines;
    		$y = mt_rand($this->image_height * 0.1, $this->image_height * 0.9);
    
    		$theta = ($this->_frand() - 0.5) * M_PI * 0.7;
    		$w = $this->image_width;
    		$len = mt_rand($w * 0.4, $w * 0.7);
    		$lwid = mt_rand(0, 2);
    
    		$k = $this->_frand() * 0.6 + 0.2;
    		$k = $k * $k * 0.5;
    		$phi = $this->_frand() * 6.28;
    		$step = 0.5;
    		$dx = $step * cos($theta);
    		$dy = $step * sin($theta);
    		$n = $len / $step;
    		$amp = 1.5 * $this->_frand() / ($k + 5.0 / $len);
    		$x0 = $x - 0.5 * $len * cos($theta);
    		$y0 = $y - 0.5 * $len * sin($theta);
    
    		$ldx = round(- $dy * $lwid);
    		$ldy = round($dx * $lwid);
    
    		for ($i = 0; $i < $n; ++ $i) {
    			$x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
    			$y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
    			imagefilledrectangle($this->im, $x, $y, $x + $lwid, $y + $lwid, $this->gdlinecolor);
    		}
    	}
    }
    
    /**
     * Print signature text on image
     */
    protected function _addSignature()
    {
    	$bbox = imagettfbbox(10, 0, $this->signature_font, $this->image_signature);
    	$textlen = $bbox[2] - $bbox[0];
    	$x = $this->image_width - $textlen - 5;
    	$y = $this->image_height - 3;
    
    	imagettftext($this->im, 10, 0, $x, $y, $this->gdsignaturecolor, $this->signature_font, $this->image_signature);
    }
    
    /**
     * Sends the appropriate image and cache headers and outputs image to the browser
     */
    protected function _output()
    {
    	// only send the content-type headers if no headers have been output
    	// this will ease debugging on misconfigured servers where warnings
    	// may have been output which break the image and prevent easily viewing
    	// source to see the error.
    	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
    	header("Cache-Control: no-store, no-cache, must-revalidate");
    	header("Cache-Control: post-check=0, pre-check=0", false);
    	header("Pragma: no-cache");
    	
    	switch ($this->image_type) {
    		case self::SI_IMAGE_JPEG:
    			header("Content-Type: image/jpeg");
    			imagejpeg($this->im, null, 90);
    			break;
    		case self::SI_IMAGE_GIF:
    			header("Content-Type: image/gif");
    			imagegif($this->im);
    			break;
    		default:
    			header("Content-Type: image/png");
    			imagepng($this->im);
    			break;
    		}
    
    	imagedestroy($this->im);
    }
    
    /**
     * Return a random float between 0 and 0.9999
     *
     * @return float Random float between 0 and 0.9999
     */
    function _frand()
    {
    	return 0.0001 * mt_rand(0,9999);
    }
    
    /**
     * Generates the code or math problem and saves the value to the session
     */
    private function _createCode()
    {
    	$this->code = false;
    
    	switch($this->captcha_type) {
    		case self::SI_CAPTCHA_MATHEMATIC:
    			{
    				do {
    					$signs = array('+', '-', 'x');
    					$left  = mt_rand(1, 10);
    					$right = mt_rand(1, 5);
    					$sign  = $signs[mt_rand(0, 2)];
    
    					switch($sign) {
    						case 'x': $c = $left * $right; break;
    						case '-': $c = $left - $right; break;
    						default:  $c = $left + $right; break;
    					}
    				} while ($c <= 0); // no negative #'s or 0
    
    				$this->code         = $c;
    				$this->code_display = "$left $sign $right";
    				break;
    			}
    
    		case self::SI_CAPTCHA_WORDS:
    			$words = $this->_readCodeFromFile(2);
    			$this->code = implode(' ', $words);
    			$this->code_display = $this->code;
    			break;
    
    		default:
    			{
    				if ( ($this->code_length < 1 OR $this->use_wordlist) && is_readable($this->wordlist_file) ) {
    					$this->code = $this->_readCodeFromFile();
    				}
    
    				if ($this->code == false || ($this->code_length && strlen($this->code) < $this->code_length)) {
    					$this->code = $this->_generateCode($this->code_length);
    				}
    				$this->code_display = $this->code;
    				break;
    			} // default
    	}
    }
    
    /**
     * Gets a captcha code from a wordlist
     */
    protected function _readCodeFromFile($numWords = 1)
    {
    	$fp = fopen($this->wordlist_file, 'rb');
    	if (!$fp) return false;
    
    	$fsize = filesize($this->wordlist_file);
    	if ($fsize < 128) return false; // too small of a list to be effective
    
    	if ((int)$numWords < 1 || (int)$numWords > 5) $numWords = 1;
    
    	$words = array();
    	$i = 0;
    	do {
    		fseek($fp, mt_rand(0, $fsize - 64), SEEK_SET); // seek to a random position of file from 0 to filesize-64
    		$data = fread($fp, 64); // read a chunk from our random position
    		$data = preg_replace("/\r?\n/", "\n", $data);
    
    		$start = @strpos($data, "\n", mt_rand(0, 56)) + 1; // random start position
    		$end   = @strpos($data, "\n", $start);          // find end of word
    
    		if ($start === false) {
    			// picked start position at end of file
    			continue;
    		} else if ($end === false) {
    			$end = strlen($data);
    		}
    
    		$word = strtolower(substr($data, $start, $end - $start)); // return a line of the file
    		$words[] = $word;
    	} while (++$i < $numWords);
    
    	fclose($fp);
    
    	if ($numWords < 2) {
    		return $words[0];
    	} else {
    		return $words;
    	}
    }
    
    /**
     * Generates a random captcha code from the set character set
     */
    protected function _generateCode()
    {
    	$code = '';
    
    	if (function_exists('mb_strlen')) {
    		for($i = 1, $cslen = mb_strlen($this->charset); $i <= $this->code_length; ++$i) {
    			$code .= mb_substr($this->charset, mt_rand(0, $cslen - 1), 1, 'UTF-8');
    		}
    	} else {
    		for($i = 1, $cslen = strlen($this->charset); $i <= $this->code_length; ++$i) {
    			$code .= substr($this->charset, mt_rand(0, $cslen - 1), 1);
    		}
    	}
    
    	return $code;
    }
    
    /**
     * Convert an html color code to a Securimage_Color
     * @param string $color
     * @param Securimage_Color $default The defalt color to use if $color is invalid
     */
    protected function _initColor($color, $default)
    {
    	if ($color == null) {
    		return new Securimage_Color($default);
    	} else if (is_string($color)) {
    		try {
    			return new Securimage_Color($color);
    		} catch(Exception $e) {
    			return new Securimage_Color($default);
    		}
    	} else if (is_array($color) && sizeof($color) == 3) {
    		return new Securimage_Color($color[0], $color[1], $color[2]);
    	} else {
    		return new Securimage_Color($default);
    	}
    }
}

/* End of file Captcha_securimage.php */
/* Location: ./application/libraries/Captcha/drivers/Captcha_securimage.php */