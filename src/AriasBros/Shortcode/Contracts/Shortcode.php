<?php

namespace AriasBros\Shortcode\Contracts;

use Illuminate\Contracts\View\View;

/**
 * Interface Shortcode
 *
 * @package AriasBros\Shortcode\Contracts
 * @since 1.0.0
 */
interface Shortcode
{
	/**
	 * @since 1.0.0
	 * @param array $attributes
	 * @return View
	 */
    public function compose(array $attributes = []);
}