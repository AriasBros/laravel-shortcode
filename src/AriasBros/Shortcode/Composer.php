<?php

namespace AriasBros\Shortcode;

use AriasBros\Shortcode\Contracts\Shortcode as ComposerContract;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

/**
 * Class Composer
 *
 * @package AriasBros\Shortcode
 * @since 1.0.0
 */
class Composer implements ComposerContract
{
	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $shortcode_tag = null;

	/**
	 * Composer constructor.
	 *
	 * @since 1.0.0
	 * @param string $shortcodeTag
	 */
	public function __construct($shortcodeTag)
	{
		$this->shortcode_tag = $shortcodeTag;
	}

	/**
	 * @since 1.0.0
	 * @param array $attributes
	 * @return View
	 */
	public function compose(array $attributes = [])
	{
		$view = "{$this->root()}.{$this->shortcode_tag}";

		if (!view()->exists($view)) {
			throw new InvalidArgumentException("You must create the view [{$view}] to the shortcode [{$this->shortcode_tag}]");
		}

		return view($view, $attributes);
	}

	/**
	 * @since 1.0.0
	 * @return string
	 */
	protected function root()
	{
		return "shortcodes";
	}
}