<?php

namespace AriasBros\Shortcode\Contracts;

/**
 * @since 1.0.0
 */
interface Factory
{   
    /**
     * @param  string  $shortcodeTag The tag for the shortcode.
     * @param  string  $callback The class of the shortcode.
     *
     * @return Illuminate\View\View
     *
     * @since 1.0.0
     */
    public function composer($shortcodeTag, $callback);
    
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