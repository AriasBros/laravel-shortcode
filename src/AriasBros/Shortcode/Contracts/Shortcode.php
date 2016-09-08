<?php

namespace AriasBros\Shortcode\Contracts;

interface Shortcode 
{   
    /**
     * @return Illuminate\View\View
     */
    public function compose($attrs = null);
}