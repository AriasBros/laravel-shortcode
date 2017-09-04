<?php

namespace AriasBros\Shortcode\Contracts;

/**
 * Interface Factory
 *
 * @package AriasBros\Shortcode\Contracts
 * @since 1.0.0
 */
interface Factory
{
	/**
	 * @since 1.0.0
	 *
	 * @param string|array $shortcodeTag The tag for the shortcode.
	 * @param string $callback The composer class of the shortcode.
	 */
    public function bind($shortcodeTag, $callback = null);

    /**
     * @deprecated
	 * @since 1.0.0
	 *
	 * @param string|array $shortcodeTag The tag for the shortcode.
	 * @param string $callback The composer class of the shortcode.
	 */
    public function composer($shortcodeTag, $callback = null);
    
    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function tags();

    /**
     * @param  string  $tag The callback associated to a shortcode tag.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function callbackForTag($tag);
    
    /**
     * @param  string  $content The content in which search and parse shortcodes.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function make($content);
}