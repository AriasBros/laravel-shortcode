<?php

/**
 * @access public
 * @since 1.0.0
 */
function shortcode()
{
    return app()["AriasBros\Shortcode\Contracts\Factory"];   
}

/**
 * @access public
 * @since 1.0.0
 */
function get_shortcodes()
{
    return shortcode()->tags();
}

/**
 * @access public
 * @since 1.0.0
 */
function do_shortcode($content)
{
    return shortcode()->make($content);
}

/**
 * @access private
 * @since 1.0.0
 */
function do_shortcode_tag($m)
{
    return shortcode()->makeTag($m);
}

/**
 * @access private
 * @since 1.0.0
 */
function unescape_invalid_shortcodes($content)
{
        // Clean up entire string, avoids re-parsing HTML.
        $trans = array( '&#91;' => '[', '&#93;' => ']' );
        $content = strtr( $content, $trans );

        return $content;
}

/**
 * @access private
 * @since 1.0.0
 */
function shortcode_parse_atts($text)
{
    $atts = array();
    $pattern = get_shortcode_atts_regex();
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
        foreach ($match as $m) {
            if (!empty($m[1]))
                $atts[strtolower($m[1])] = stripcslashes($m[2]);
            elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
            elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
            elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
            elseif (isset($m[8]))
                $atts[] = stripcslashes($m[8]);
        }

        // Reject any unclosed HTML elements
        foreach( $atts as &$value ) {
            if ( false !== strpos( $value, '<' ) ) {
                if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                    $value = '';
                }
            }
        }
    } else {
        $atts = ltrim($text);
    }
    return $atts;
}

/**
 * @access private
 * @since 1.0.0
 */
function get_shortcode_atts_regex() {
    return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
}

/**
 * @access public
 * @since 1.0.0
 */
function has_shortcode($shortcode_name, $content)
{
    if (strpos($content, '[') === false) {
        return false;
    }

    if (shortcode_exists($shortcode_name)) {
        preg_match_all('/' . get_shortcode_regex([$shortcode_name]) . '/', $content, $matches, PREG_SET_ORDER);
        
        if (empty($matches)) {
            return false;
        }

        foreach ( $matches as $shortcode ) {
            if ($shortcode_name === $shortcode[2]) {
                return true;
            }
            elseif (!empty($shortcode[5]) && has_shortcode($shortcode[5], $shortcode_name)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @access public
 * @since 1.0.0
 * @todo Implement this
 */
function shortcode_exists($shortcode_name)
{
    return true;
}

/**
 * Se usa la función de Wordpress personalizada un poco. En Wordpress el parámetro
 * $tagnames es opcional. Aquí por ahora es obligatorio (al menos hasta que exista
 * un método para registrar shortcodes, si se hace...)
 *
 * La expresión regular no se ha cambiado nada. Es la de la versión 4.5.3 de Wordpress.
 *
 * @access private
 * @since 1.0.0
 * @see https://core.trac.wordpress.org/browser/tags/4.5.3/src/wp-includes/shortcodes.php?order=name#L253
 */
function get_shortcode_regex($tagnames)
{
    $tagregexp = join('|', array_map('preg_quote', $tagnames));

    // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
    // Also, see shortcode_unautop() and shortcode.js.
    return
           '\\['                              // Opening bracket
         . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
         . "($tagregexp)"                     // 2: Shortcode name
         . '(?![\\w-])'                       // Not followed by word character or hyphen
         . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
         .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
         .     '(?:'
         .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
         .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
         .     ')*?'
         . ')'
         . '(?:'
         .     '(\\/)'                        // 4: Self closing tag ...
         .     '\\]'                          // ... and closing bracket
         . '|'
         .     '\\]'                          // Closing bracket
         .     '(?:'
         .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
         .             '[^\\[]*+'             // Not an opening bracket
         .             '(?:'
         .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
         .                 '[^\\[]*+'         // Not an opening bracket
         .             ')*+'
         .         ')'
         .         '\\[\\/\\2\\]'             // Closing shortcode tag
         .     ')?'
         . ')'
         . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
}

/**
 * @access private
 * @since 1.0.0
 */
function do_shortcodes_in_html_tags($content, $ignore_html, $tagnames)
{
    // Normalize entities in unfiltered HTML before adding placeholders.
    $trans = array( '&#91;' => '&#091;', '&#93;' => '&#093;' );
    $content = strtr( $content, $trans );
    $trans = array( '[' => '&#91;', ']' => '&#93;' );

    $pattern = get_shortcode_regex($tagnames);
    $textarr = wp_html_split( $content );

    foreach ( $textarr as &$element ) {
        if ( '' == $element || '<' !== $element[0] ) {
            continue;
        }

        $noopen = false === strpos( $element, '[' );
        $noclose = false === strpos( $element, ']' );
        if ( $noopen || $noclose ) {
            // This element does not contain shortcodes.
            if ( $noopen xor $noclose ) {
                // Need to encode stray [ or ] chars.
                $element = strtr( $element, $trans );
            }
            continue;
        }

        if ( $ignore_html || '<!--' === substr( $element, 0, 4 ) || '<![CDATA[' === substr( $element, 0, 9 ) ) {
            // Encode all [ and ] chars.
            $element = strtr( $element, $trans );
            continue;
        }

        $attributes = wp_kses_attr_parse( $element );
        if ( false === $attributes ) {
            // Some plugins are doing things like [name] <[email]>.
            if ( 1 === preg_match( '%^<\s*\[\[?[^\[\]]+\]%', $element ) ) {
                $element = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $element );
            }

            // Looks like we found some crazy unfiltered HTML.  Skipping it for sanity.
            $element = strtr( $element, $trans );
            continue;
        }

        // Get element name
        $front = array_shift( $attributes );
        $back = array_pop( $attributes );
        $matches = array();
        preg_match('%[a-zA-Z0-9]+%', $front, $matches);
        $elname = $matches[0];

        // Look for shortcodes in each attribute separately.
        foreach ( $attributes as &$attr ) {
            $open = strpos( $attr, '[' );
            $close = strpos( $attr, ']' );
            if ( false === $open || false === $close ) {
                continue; // Go to next attribute.  Square braces will be escaped at end of loop.
            }
            $double = strpos( $attr, '"' );
            $single = strpos( $attr, "'" );
            if ( ( false === $single || $open < $single ) && ( false === $double || $open < $double ) ) {
                // $attr like '[shortcode]' or 'name = [shortcode]' implies unfiltered_html.
                // In this specific situation we assume KSES did not run because the input
                // was written by an administrator, so we should avoid changing the output
                // and we do not need to run KSES here.
                $attr = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $attr );
            } else {
                // $attr like 'name = "[shortcode]"' or "name = '[shortcode]'"
                // We do not know if $content was unfiltered. Assume KSES ran before shortcodes.
                $count = 0;
                $new_attr = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $attr, -1, $count );
                if ( $count > 0 ) {
                    // Sanitize the shortcode output using KSES.
                    //$new_attr = wp_kses_one_attr( $new_attr, $elname );
                    if ( '' !== trim( $new_attr ) ) {
                        // The shortcode is safe to use now.
                        $attr = $new_attr;
                    }
                }
            }
        }
        $element = $front . implode( '', $attributes ) . $back;

        // Now encode any remaining [ or ] chars.
        $element = strtr( $element, $trans );
    }

    $content = implode( '', $textarr );

    return $content;
}