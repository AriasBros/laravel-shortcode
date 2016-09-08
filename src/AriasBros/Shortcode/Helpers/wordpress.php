<?php

/**
 * @internal
 * @since 1.0.0
 */
function wp_html_split($input)
{
    return preg_split(get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE);
}

/**
 * @internal
 * @since 1.0.0
 */
function get_html_split_regex()
{
    static $regex;
 
    if ( ! isset( $regex ) ) {
        $comments =
              '!'           // Start of comment, after the <.
            . '(?:'         // Unroll the loop: Consume everything until --> is found.
            .     '-(?!->)' // Dash not followed by end of comment.
            .     '[^\-]*+' // Consume non-dashes.
            . ')*+'         // Loop possessively.
            . '(?:-->)?';   // End of comment. If not found, match all input.
 
        $cdata =
              '!\[CDATA\['  // Start of comment, after the <.
            . '[^\]]*+'     // Consume non-].
            . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
            .     '](?!]>)' // One ] not followed by end of comment.
            .     '[^\]]*+' // Consume non-].
            . ')*+'         // Loop possessively.
            . '(?:]]>)?';   // End of comment. If not found, match all input.
 
        $escaped =
              '(?='           // Is the element escaped?
            .    '!--'
            . '|'
            .    '!\[CDATA\['
            . ')'
            . '(?(?=!-)'      // If yes, which type?
            .     $comments
            . '|'
            .     $cdata
            . ')';
 
        $regex =
              '/('              // Capture the entire match.
            .     '<'           // Find start of element.
            .     '(?'          // Conditional expression follows.
            .         $escaped  // Find end of escaped element.
            .     '|'           // ... else ...
            .         '[^>]*>?' // Find end of normal element.
            .     ')'
            . ')/';
    }
 
    return $regex;
}

/**
 * @internal
 * @since 1.0.0
 */
function wp_kses_attr_parse($element)
{
    $valid = preg_match('%^(<\s*)(/\s*)?([a-zA-Z0-9]+\s*)([^>]*)(>?)$%', $element, $matches);
    if ( 1 !== $valid ) {
        return false;
    }
 
    $begin =  $matches[1];
    $slash =  $matches[2];
    $elname = $matches[3];
    $attr =   $matches[4];
    $end =    $matches[5];
 
    if ( '' !== $slash ) {
        // Closing elements do not get parsed.
        return false;
    }
 
    // Is there a closing XHTML slash at the end of the attributes?
    if ( 1 === preg_match( '%\s*/\s*$%', $attr, $matches ) ) {
        $xhtml_slash = $matches[0];
        $attr = substr( $attr, 0, -strlen( $xhtml_slash ) );
    } else {
        $xhtml_slash = '';
    }
     
    // Split it
    $attrarr = wp_kses_hair_parse( $attr );
    if ( false === $attrarr ) {
        return false;
    }
 
    // Make sure all input is returned by adding front and back matter.
    array_unshift( $attrarr, $begin . $slash . $elname );
    array_push( $attrarr, $xhtml_slash . $end );
     
    return $attrarr;
}

/**
 * @internal
 * @since 1.0.0
 */
function wp_kses_one_attr($string, $element)
{
    $uris = array('xmlns', 'profile', 'href', 'src', 'cite', 'classid', 'codebase', 'data', 'usemap', 'longdesc', 'action');
    $allowed_html = wp_kses_allowed_html( 'post' );
    $allowed_protocols = wp_allowed_protocols();
    $string = wp_kses_no_null( $string, array( 'slash_zero' => 'keep' ) );
    $string = wp_kses_js_entities( $string );
     
    // Preserve leading and trailing whitespace.
    $matches = array();
    preg_match('/^\s*/', $string, $matches);
    $lead = $matches[0];
    preg_match('/\s*$/', $string, $matches);
    $trail = $matches[0];
    if ( empty( $trail ) ) {
        $string = substr( $string, strlen( $lead ) );
    } else {
        $string = substr( $string, strlen( $lead ), -strlen( $trail ) );
    }
     
    // Parse attribute name and value from input.
    $split = preg_split( '/\s*=\s*/', $string, 2 );
    $name = $split[0];
    if ( count( $split ) == 2 ) {
        $value = $split[1];
 
        // Remove quotes surrounding $value.
        // Also guarantee correct quoting in $string for this one attribute.
        if ( '' == $value ) {
            $quote = '';
        } else {
            $quote = $value[0];
        }
        if ( '"' == $quote || "'" == $quote ) {
            if ( substr( $value, -1 ) != $quote ) {
                return '';
            }
            $value = substr( $value, 1, -1 );
        } else {
            $quote = '"';
        }
 
        // Sanitize quotes, angle braces, and entities.
        $value = esc_attr( $value );
 
        // Sanitize URI values.
        if ( in_array( strtolower( $name ), $uris ) ) {
            $value = wp_kses_bad_protocol( $value, $allowed_protocols );
        }
 
        $string = "$name=$quote$value$quote";
        $vless = 'n';
    } else {
        $value = '';
        $vless = 'y';
    }
     
    // Sanitize attribute by name.
    wp_kses_attr_check( $name, $value, $string, $vless, $element, $allowed_html );
 
    // Restore whitespace.
    return $lead . $string . $trail;
}

/**
 * @internal
 * @since 1.0.0
 */
function wp_kses_hair_parse($attr)
{
    if ( '' === $attr ) {
        return array();
    }
 
    $regex =
      '(?:'
    .     '[-a-zA-Z:]+'   // Attribute name.
    . '|'
    .     '\[\[?[^\[\]]+\]\]?' // Shortcode in the name position implies unfiltered_html.
    . ')'
    . '(?:'               // Attribute value.
    .     '\s*=\s*'       // All values begin with '='
    .     '(?:'
    .         '"[^"]*"'   // Double-quoted
    .     '|'
    .         "'[^']*'"   // Single-quoted
    .     '|'
    .         '[^\s"\']+' // Non-quoted
    .         '(?:\s|$)'  // Must have a space
    .     ')'
    . '|'
    .     '(?:\s|$)'      // If attribute has no value, space is required.
    . ')'
    . '\s*';              // Trailing space is optional except as mentioned above.
 
    // Although it is possible to reduce this procedure to a single regexp,
    // we must run that regexp twice to get exactly the expected result.
 
    $validation = "%^($regex)+$%";
    $extraction = "%$regex%";
 
    if ( 1 === preg_match( $validation, $attr ) ) {
        preg_match_all( $extraction, $attr, $attrarr );
        return $attrarr[0];
    } else {
        return false;
    }
}

function wp_kses_allowed_html( $context = '' )
{
    global $allowedposttags, $allowedtags, $allowedentitynames;
 
    if ( is_array( $context ) ) {
        /**
         * Filters HTML elements allowed for a given context.
         *
         * @since 3.5.0
         *
         * @param string $tags    Allowed tags, attributes, and/or entities.
         * @param string $context Context to judge allowed tags by. Allowed values are 'post',
         *                        'data', 'strip', 'entities', 'explicit', or the name of a filter.
         */
        return apply_filters( 'wp_kses_allowed_html', $context, 'explicit' );
    }
 
    switch ( $context ) {
        case 'post':
            /** This filter is documented in wp-includes/kses.php */
            return apply_filters( 'wp_kses_allowed_html', $allowedposttags, $context );
 
        case 'user_description':
        case 'pre_user_description':
            $tags = $allowedtags;
            $tags['a']['rel'] = true;
            /** This filter is documented in wp-includes/kses.php */
            return apply_filters( 'wp_kses_allowed_html', $tags, $context );
 
        case 'strip':
            /** This filter is documented in wp-includes/kses.php */
            return apply_filters( 'wp_kses_allowed_html', array(), $context );
 
        case 'entities':
            /** This filter is documented in wp-includes/kses.php */
            return apply_filters( 'wp_kses_allowed_html', $allowedentitynames, $context);
 
        case 'data':
        default:
            /** This filter is documented in wp-includes/kses.php */
            return apply_filters( 'wp_kses_allowed_html', $allowedtags, $context );
    }
}