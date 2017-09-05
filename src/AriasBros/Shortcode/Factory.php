<?php

namespace AriasBros\Shortcode;

use AriasBros\Shortcode\Contracts\Factory as FactoryContract;

/**
 * Class Factory
 *
 * @package AriasBros\Shortcode
 * @since 1.0.0
 */
class Factory implements FactoryContract
{   
    /**
     * @since 1.0.0
     */
    protected $app = null;

    /**
     * @since 1.0.0
     */
    protected $shortcodes = [];

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->app = app();
    }

    /**
     * @deprecated
     * @since 1.0.0
     *
     * @param string|array $shortcodeTag The tag for the shortcode.
     * @param string $callback The composer class of the shortcode.
     */
    public function composer($shortcodeTag, $callback = null)
    {
    	$this->bind($shortcodeTag, $callback);
    }

    /**
     * @since 1.0.0
     *
     * @param string|array $shortcodeTag The tag for the shortcode.
     * @param string $callback The composer class of the shortcode.
     */
    public function bind($shortcodeTag, $callback = null)
    {
    	if (is_array($shortcodeTag)) {
			foreach ($shortcodeTag as $key => $value) {
				if (is_string($key)) {
					if (is_array($value)) {
						foreach ($value as $tag) {
							$this->bindShortcode($tag, $key);
						}
					} else {
						$this->bindShortcode($value, $key);
					}
				} else {
					$this->bindShortcode($value, $callback);
				}
			}
	    } elseif (is_string($shortcodeTag)) {
			$this->bindShortcode($shortcodeTag, $callback);
	    }
    }

	/**
	 * @since 1.0.0
	 *
	 * @param string|array $shortcodeTag The tag for the shortcode.
	 * @param string $callback The composer class of the shortcode.
	 */
    private function bindShortcode($shortcodeTag, $callback = null)
    {
	    if ($callback === null) {
		    $callback = Composer::class;
	    }

	    $this->shortcodes[$shortcodeTag] = $callback;

	    $this->app->singleton("shortcode-{$shortcodeTag}", function ($app) use ($shortcodeTag, $callback) {
		    return new $callback($shortcodeTag);
	    });
    }
    
    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function tags()
    {
        return array_keys($this->shortcodes);
    }
    
    /**
     * @param  string  $tag The callback associated to a shortcode tag.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function callbackForTag($tag)
    {
        if (isset($this->shortcodes[$tag])) {
            return $this->shortcodes[$tag];
        }
        
        return null;
    }
    
    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function make($content)
    {
        $shortcodes = $this->tags();
    
        if (strpos($content, '[') === false) {
            return $content;
        }
    
        if (empty($shortcodes) || !is_array($shortcodes)) {
            return $content;
        }
    
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect($shortcodes, $matches[1]);
    
        if (empty($tagnames)) {
            return $content;
        }
    
        $ignore_html = true;
        $content = do_shortcodes_in_html_tags($content, $ignore_html, $tagnames);
        $pattern = get_shortcode_regex($tagnames);
        $content = preg_replace_callback("/$pattern/", 'do_shortcode_tag', $content);
        $content = unescape_invalid_shortcodes($content);
    
        return $content;
    }
    
    /**
     * @access private
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function makeTag($m)
    {
        // allow [[foo]] syntax for escaping a tag
        if ( $m[1] == '[' && $m[6] == ']' ) {
            return substr($m[0], 1, -1);
        }
    
        $tag = $m[2];    
        $attrs = shortcode_parse_atts($m[3]);
        $shortcode = app()->make("shortcode-{$tag}");

	    $attrs = !is_array($attrs) ? [] : $attrs;
        $attrs["content"] = isset( $m[5] ) ? $m[5] : null;

        return $shortcode->compose($attrs);
    }
}