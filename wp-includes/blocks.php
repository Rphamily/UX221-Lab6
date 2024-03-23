<?php
 function remove_block_asset_path_prefix( $asset_handle_or_path ) { $path_prefix = 'file:'; if ( ! str_starts_with( $asset_handle_or_path, $path_prefix ) ) { return $asset_handle_or_path; } $path = substr( $asset_handle_or_path, strlen( $path_prefix ) ); if ( str_starts_with( $path, './' ) ) { $path = substr( $path, 2 ); } return $path; } function generate_block_asset_handle( $block_name, $field_name, $index = 0 ) { if ( str_starts_with( $block_name, 'core/' ) ) { $asset_handle = str_replace( 'core/', 'wp-block-', $block_name ); if ( str_starts_with( $field_name, 'editor' ) ) { $asset_handle .= '-editor'; } if ( str_starts_with( $field_name, 'view' ) ) { $asset_handle .= '-view'; } if ( $index > 0 ) { $asset_handle .= '-' . ( $index + 1 ); } return $asset_handle; } $field_mappings = array( 'editorScript' => 'editor-script', 'script' => 'script', 'viewScript' => 'view-script', 'editorStyle' => 'editor-style', 'style' => 'style', ); $asset_handle = str_replace( '/', '-', $block_name ) . '-' . $field_mappings[ $field_name ]; if ( $index > 0 ) { $asset_handle .= '-' . ( $index + 1 ); } return $asset_handle; } function get_block_asset_url( $path ) { if ( empty( $path ) ) { return false; } static $wpinc_path_norm = ''; if ( ! $wpinc_path_norm ) { $wpinc_path_norm = wp_normalize_path( realpath( ABSPATH . WPINC ) ); } if ( str_starts_with( $path, $wpinc_path_norm ) ) { return includes_url( str_replace( $wpinc_path_norm, '', $path ) ); } static $template_paths_norm = array(); $template = get_template(); if ( ! isset( $template_paths_norm[ $template ] ) ) { $template_paths_norm[ $template ] = wp_normalize_path( get_template_directory() ); } if ( str_starts_with( $path, trailingslashit( $template_paths_norm[ $template ] ) ) ) { return get_theme_file_uri( str_replace( $template_paths_norm[ $template ], '', $path ) ); } if ( is_child_theme() ) { $stylesheet = get_stylesheet(); if ( ! isset( $template_paths_norm[ $stylesheet ] ) ) { $template_paths_norm[ $stylesheet ] = wp_normalize_path( get_stylesheet_directory() ); } if ( str_starts_with( $path, trailingslashit( $template_paths_norm[ $stylesheet ] ) ) ) { return get_theme_file_uri( str_replace( $template_paths_norm[ $stylesheet ], '', $path ) ); } } return plugins_url( basename( $path ), $path ); } function register_block_script_handle( $metadata, $field_name, $index = 0 ) { if ( empty( $metadata[ $field_name ] ) ) { return false; } $script_handle = $metadata[ $field_name ]; if ( is_array( $script_handle ) ) { if ( empty( $script_handle[ $index ] ) ) { return false; } $script_handle = $script_handle[ $index ]; } $script_path = remove_block_asset_path_prefix( $script_handle ); if ( $script_handle === $script_path ) { return $script_handle; } $path = dirname( $metadata['file'] ); $script_asset_raw_path = $path . '/' . substr_replace( $script_path, '.asset.php', - strlen( '.js' ) ); $script_handle = generate_block_asset_handle( $metadata['name'], $field_name, $index ); $script_asset_path = wp_normalize_path( realpath( $script_asset_raw_path ) ); if ( empty( $script_asset_path ) ) { _doing_it_wrong( __FUNCTION__, sprintf( __( 'The asset file (%1$s) for the "%2$s" defined in "%3$s" block definition is missing.' ), $script_asset_raw_path, $field_name, $metadata['name'] ), '5.5.0' ); return false; } $script_path_norm = wp_normalize_path( realpath( $path . '/' . $script_path ) ); $script_uri = get_block_asset_url( $script_path_norm ); $script_args = array(); if ( 'viewScript' === $field_name && $script_uri ) { $script_args['strategy'] = 'defer'; } $script_asset = require $script_asset_path; $script_dependencies = isset( $script_asset['dependencies'] ) ? $script_asset['dependencies'] : array(); $result = wp_register_script( $script_handle, $script_uri, $script_dependencies, isset( $script_asset['version'] ) ? $script_asset['version'] : false, $script_args ); if ( ! $result ) { return false; } if ( ! empty( $metadata['textdomain'] ) && in_array( 'wp-i18n', $script_dependencies, true ) ) { wp_set_script_translations( $script_handle, $metadata['textdomain'] ); } return $script_handle; } function register_block_style_handle( $metadata, $field_name, $index = 0 ) { if ( empty( $metadata[ $field_name ] ) ) { return false; } $style_handle = $metadata[ $field_name ]; if ( is_array( $style_handle ) ) { if ( empty( $style_handle[ $index ] ) ) { return false; } $style_handle = $style_handle[ $index ]; } $style_handle_name = generate_block_asset_handle( $metadata['name'], $field_name, $index ); if ( wp_style_is( $style_handle_name, 'registered' ) ) { return $style_handle_name; } static $wpinc_path_norm = ''; if ( ! $wpinc_path_norm ) { $wpinc_path_norm = wp_normalize_path( realpath( ABSPATH . WPINC ) ); } $is_core_block = isset( $metadata['file'] ) && str_starts_with( $metadata['file'], $wpinc_path_norm ); if ( $is_core_block && ! wp_should_load_separate_core_block_assets() ) { return false; } $style_path = remove_block_asset_path_prefix( $style_handle ); $is_style_handle = $style_handle === $style_path; if ( $is_core_block && ! $is_style_handle ) { return false; } if ( $is_style_handle && ! ( $is_core_block && 0 === $index ) ) { return $style_handle; } $suffix = SCRIPT_DEBUG ? '' : '.min'; if ( $is_core_block ) { $style_path = ( 'editorStyle' === $field_name ) ? "editor{$suffix}.css" : "style{$suffix}.css"; } $style_path_norm = wp_normalize_path( realpath( dirname( $metadata['file'] ) . '/' . $style_path ) ); $style_uri = get_block_asset_url( $style_path_norm ); $version = ! $is_core_block && isset( $metadata['version'] ) ? $metadata['version'] : false; $result = wp_register_style( $style_handle_name, $style_uri, array(), $version ); if ( ! $result ) { return false; } if ( $style_uri ) { wp_style_add_data( $style_handle_name, 'path', $style_path_norm ); if ( $is_core_block ) { $rtl_file = str_replace( "{$suffix}.css", "-rtl{$suffix}.css", $style_path_norm ); } else { $rtl_file = str_replace( '.css', '-rtl.css', $style_path_norm ); } if ( is_rtl() && file_exists( $rtl_file ) ) { wp_style_add_data( $style_handle_name, 'rtl', 'replace' ); wp_style_add_data( $style_handle_name, 'suffix', $suffix ); wp_style_add_data( $style_handle_name, 'path', $rtl_file ); } } return $style_handle_name; } function get_block_metadata_i18n_schema() { static $i18n_block_schema; if ( ! isset( $i18n_block_schema ) ) { $i18n_block_schema = wp_json_file_decode( __DIR__ . '/block-i18n.json' ); } return $i18n_block_schema; } function register_block_type_from_metadata( $file_or_folder, $args = array() ) { static $core_blocks_meta; if ( ! $core_blocks_meta ) { $core_blocks_meta = require ABSPATH . WPINC . '/blocks/blocks-json.php'; } $metadata_file = ( ! str_ends_with( $file_or_folder, 'block.json' ) ) ? trailingslashit( $file_or_folder ) . 'block.json' : $file_or_folder; $is_core_block = str_starts_with( $file_or_folder, ABSPATH . WPINC ); if ( ! $is_core_block && ! file_exists( $metadata_file ) ) { return false; } $metadata = false; if ( $is_core_block ) { $core_block_name = str_replace( ABSPATH . WPINC . '/blocks/', '', $file_or_folder ); if ( ! empty( $core_blocks_meta[ $core_block_name ] ) ) { $metadata = $core_blocks_meta[ $core_block_name ]; } } if ( ! $metadata ) { $metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) ); } if ( ! is_array( $metadata ) || empty( $metadata['name'] ) ) { return false; } $metadata['file'] = wp_normalize_path( realpath( $metadata_file ) ); $metadata = apply_filters( 'block_type_metadata', $metadata ); if ( ! empty( $metadata['name'] ) && str_starts_with( $metadata['name'], 'core/' ) ) { $block_name = str_replace( 'core/', '', $metadata['name'] ); if ( ! isset( $metadata['style'] ) ) { $metadata['style'] = "wp-block-$block_name"; } if ( current_theme_supports( 'wp-block-styles' ) && wp_should_load_separate_core_block_assets() ) { $metadata['style'] = (array) $metadata['style']; $metadata['style'][] = "wp-block-{$block_name}-theme"; } if ( ! isset( $metadata['editorStyle'] ) ) { $metadata['editorStyle'] = "wp-block-{$block_name}-editor"; } } $settings = array(); $property_mappings = array( 'apiVersion' => 'api_version', 'title' => 'title', 'category' => 'category', 'parent' => 'parent', 'ancestor' => 'ancestor', 'icon' => 'icon', 'description' => 'description', 'keywords' => 'keywords', 'attributes' => 'attributes', 'providesContext' => 'provides_context', 'usesContext' => 'uses_context', 'selectors' => 'selectors', 'supports' => 'supports', 'styles' => 'styles', 'variations' => 'variations', 'example' => 'example', ); $textdomain = ! empty( $metadata['textdomain'] ) ? $metadata['textdomain'] : null; $i18n_schema = get_block_metadata_i18n_schema(); foreach ( $property_mappings as $key => $mapped_key ) { if ( isset( $metadata[ $key ] ) ) { $settings[ $mapped_key ] = $metadata[ $key ]; if ( $textdomain && isset( $i18n_schema->$key ) ) { $settings[ $mapped_key ] = translate_settings_using_i18n_schema( $i18n_schema->$key, $settings[ $key ], $textdomain ); } } } $script_fields = array( 'editorScript' => 'editor_script_handles', 'script' => 'script_handles', 'viewScript' => 'view_script_handles', ); foreach ( $script_fields as $metadata_field_name => $settings_field_name ) { if ( ! empty( $metadata[ $metadata_field_name ] ) ) { $scripts = $metadata[ $metadata_field_name ]; $processed_scripts = array(); if ( is_array( $scripts ) ) { for ( $index = 0; $index < count( $scripts ); $index++ ) { $result = register_block_script_handle( $metadata, $metadata_field_name, $index ); if ( $result ) { $processed_scripts[] = $result; } } } else { $result = register_block_script_handle( $metadata, $metadata_field_name ); if ( $result ) { $processed_scripts[] = $result; } } $settings[ $settings_field_name ] = $processed_scripts; } } $style_fields = array( 'editorStyle' => 'editor_style_handles', 'style' => 'style_handles', ); foreach ( $style_fields as $metadata_field_name => $settings_field_name ) { if ( ! empty( $metadata[ $metadata_field_name ] ) ) { $styles = $metadata[ $metadata_field_name ]; $processed_styles = array(); if ( is_array( $styles ) ) { for ( $index = 0; $index < count( $styles ); $index++ ) { $result = register_block_style_handle( $metadata, $metadata_field_name, $index ); if ( $result ) { $processed_styles[] = $result; } } } else { $result = register_block_style_handle( $metadata, $metadata_field_name ); if ( $result ) { $processed_styles[] = $result; } } $settings[ $settings_field_name ] = $processed_styles; } } if ( ! empty( $metadata['blockHooks'] ) ) { $position_mappings = array( 'before' => 'before', 'after' => 'after', 'firstChild' => 'first_child', 'lastChild' => 'last_child', ); $settings['block_hooks'] = array(); foreach ( $metadata['blockHooks'] as $anchor_block_name => $position ) { if ( $metadata['name'] === $anchor_block_name ) { _doing_it_wrong( __METHOD__, __( 'Cannot hook block to itself.' ), '6.4.0' ); continue; } if ( ! isset( $position_mappings[ $position ] ) ) { continue; } $settings['block_hooks'][ $anchor_block_name ] = $position_mappings[ $position ]; } } if ( ! empty( $metadata['render'] ) ) { $template_path = wp_normalize_path( realpath( dirname( $metadata['file'] ) . '/' . remove_block_asset_path_prefix( $metadata['render'] ) ) ); if ( $template_path ) { $settings['render_callback'] = static function ( $attributes, $content, $block ) use ( $template_path ) { ob_start(); require $template_path; return ob_get_clean(); }; } } $settings = apply_filters( 'block_type_metadata_settings', array_merge( $settings, $args ), $metadata ); return WP_Block_Type_Registry::get_instance()->register( $metadata['name'], $settings ); } function register_block_type( $block_type, $args = array() ) { if ( is_string( $block_type ) && file_exists( $block_type ) ) { return register_block_type_from_metadata( $block_type, $args ); } return WP_Block_Type_Registry::get_instance()->register( $block_type, $args ); } function unregister_block_type( $name ) { return WP_Block_Type_Registry::get_instance()->unregister( $name ); } function has_blocks( $post = null ) { if ( ! is_string( $post ) ) { $wp_post = get_post( $post ); if ( ! $wp_post instanceof WP_Post ) { return false; } $post = $wp_post->post_content; } return str_contains( (string) $post, '<!-- wp:' ); } function has_block( $block_name, $post = null ) { if ( ! has_blocks( $post ) ) { return false; } if ( ! is_string( $post ) ) { $wp_post = get_post( $post ); if ( $wp_post instanceof WP_Post ) { $post = $wp_post->post_content; } } if ( ! str_contains( $block_name, '/' ) ) { $block_name = 'core/' . $block_name; } $has_block = str_contains( $post, '<!-- wp:' . $block_name . ' ' ); if ( ! $has_block ) { $serialized_block_name = strip_core_block_namespace( $block_name ); if ( $serialized_block_name !== $block_name ) { $has_block = str_contains( $post, '<!-- wp:' . $serialized_block_name . ' ' ); } } return $has_block; } function get_dynamic_block_names() { $dynamic_block_names = array(); $block_types = WP_Block_Type_Registry::get_instance()->get_all_registered(); foreach ( $block_types as $block_type ) { if ( $block_type->is_dynamic() ) { $dynamic_block_names[] = $block_type->name; } } return $dynamic_block_names; } function get_hooked_blocks() { $block_types = WP_Block_Type_Registry::get_instance()->get_all_registered(); $hooked_blocks = array(); foreach ( $block_types as $block_type ) { if ( ! ( $block_type instanceof WP_Block_Type ) || ! is_array( $block_type->block_hooks ) ) { continue; } foreach ( $block_type->block_hooks as $anchor_block_type => $relative_position ) { if ( ! isset( $hooked_blocks[ $anchor_block_type ] ) ) { $hooked_blocks[ $anchor_block_type ] = array(); } if ( ! isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ) { $hooked_blocks[ $anchor_block_type ][ $relative_position ] = array(); } $hooked_blocks[ $anchor_block_type ][ $relative_position ][] = $block_type->name; } } return $hooked_blocks; } function make_before_block_visitor( $hooked_blocks, $context ) { return function ( &$block, &$parent_block = null, $prev = null ) use ( $hooked_blocks, $context ) { _inject_theme_attribute_in_template_part_block( $block ); $markup = ''; if ( $parent_block && ! $prev ) { $relative_position = 'first_child'; $anchor_block_type = $parent_block['blockName']; $hooked_block_types = isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ? $hooked_blocks[ $anchor_block_type ][ $relative_position ] : array(); $hooked_block_types = apply_filters( 'hooked_block_types', $hooked_block_types, $relative_position, $anchor_block_type, $context ); foreach ( $hooked_block_types as $hooked_block_type ) { $markup .= get_comment_delimited_block_content( $hooked_block_type, array(), '' ); } } $relative_position = 'before'; $anchor_block_type = $block['blockName']; $hooked_block_types = isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ? $hooked_blocks[ $anchor_block_type ][ $relative_position ] : array(); $hooked_block_types = apply_filters( 'hooked_block_types', $hooked_block_types, $relative_position, $anchor_block_type, $context ); foreach ( $hooked_block_types as $hooked_block_type ) { $markup .= get_comment_delimited_block_content( $hooked_block_type, array(), '' ); } return $markup; }; } function make_after_block_visitor( $hooked_blocks, $context ) { return function ( &$block, &$parent_block = null, $next = null ) use ( $hooked_blocks, $context ) { $markup = ''; $relative_position = 'after'; $anchor_block_type = $block['blockName']; $hooked_block_types = isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ? $hooked_blocks[ $anchor_block_type ][ $relative_position ] : array(); $hooked_block_types = apply_filters( 'hooked_block_types', $hooked_block_types, $relative_position, $anchor_block_type, $context ); foreach ( $hooked_block_types as $hooked_block_type ) { $markup .= get_comment_delimited_block_content( $hooked_block_type, array(), '' ); } if ( $parent_block && ! $next ) { $relative_position = 'last_child'; $anchor_block_type = $parent_block['blockName']; $hooked_block_types = isset( $hooked_blocks[ $anchor_block_type ][ $relative_position ] ) ? $hooked_blocks[ $anchor_block_type ][ $relative_position ] : array(); $hooked_block_types = apply_filters( 'hooked_block_types', $hooked_block_types, $relative_position, $anchor_block_type, $context ); foreach ( $hooked_block_types as $hooked_block_type ) { $markup .= get_comment_delimited_block_content( $hooked_block_type, array(), '' ); } } return $markup; }; } function serialize_block_attributes( $block_attributes ) { $encoded_attributes = wp_json_encode( $block_attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); $encoded_attributes = preg_replace( '/--/', '\\u002d\\u002d', $encoded_attributes ); $encoded_attributes = preg_replace( '/</', '\\u003c', $encoded_attributes ); $encoded_attributes = preg_replace( '/>/', '\\u003e', $encoded_attributes ); $encoded_attributes = preg_replace( '/&/', '\\u0026', $encoded_attributes ); $encoded_attributes = preg_replace( '/\\\\"/', '\\u0022', $encoded_attributes ); return $encoded_attributes; } function strip_core_block_namespace( $block_name = null ) { if ( is_string( $block_name ) && str_starts_with( $block_name, 'core/' ) ) { return substr( $block_name, 5 ); } return $block_name; } function get_comment_delimited_block_content( $block_name, $block_attributes, $block_content ) { if ( is_null( $block_name ) ) { return $block_content; } $serialized_block_name = strip_core_block_namespace( $block_name ); $serialized_attributes = empty( $block_attributes ) ? '' : serialize_block_attributes( $block_attributes ) . ' '; if ( empty( $block_content ) ) { return sprintf( '<!-- wp:%s %s/-->', $serialized_block_name, $serialized_attributes ); } return sprintf( '<!-- wp:%s %s-->%s<!-- /wp:%s -->', $serialized_block_name, $serialized_attributes, $block_content, $serialized_block_name ); } function serialize_block( $block ) { $block_content = ''; $index = 0; foreach ( $block['innerContent'] as $chunk ) { $block_content .= is_string( $chunk ) ? $chunk : serialize_block( $block['innerBlocks'][ $index++ ] ); } if ( ! is_array( $block['attrs'] ) ) { $block['attrs'] = array(); } return get_comment_delimited_block_content( $block['blockName'], $block['attrs'], $block_content ); } function serialize_blocks( $blocks ) { return implode( '', array_map( 'serialize_block', $blocks ) ); } function traverse_and_serialize_block( $block, $pre_callback = null, $post_callback = null ) { $block_content = ''; $block_index = 0; foreach ( $block['innerContent'] as $chunk ) { if ( is_string( $chunk ) ) { $block_content .= $chunk; } else { $inner_block = $block['innerBlocks'][ $block_index ]; if ( is_callable( $pre_callback ) ) { $prev = 0 === $block_index ? null : $block['innerBlocks'][ $block_index - 1 ]; $block_content .= call_user_func_array( $pre_callback, array( &$inner_block, &$block, $prev ) ); } if ( is_callable( $post_callback ) ) { $next = count( $block['innerBlocks'] ) - 1 === $block_index ? null : $block['innerBlocks'][ $block_index + 1 ]; $post_markup = call_user_func_array( $post_callback, array( &$inner_block, &$block, $next ) ); } $block_content .= traverse_and_serialize_block( $inner_block, $pre_callback, $post_callback ); $block_content .= isset( $post_markup ) ? $post_markup : ''; ++$block_index; } } if ( ! is_array( $block['attrs'] ) ) { $block['attrs'] = array(); } return get_comment_delimited_block_content( $block['blockName'], $block['attrs'], $block_content ); } function traverse_and_serialize_blocks( $blocks, $pre_callback = null, $post_callback = null ) { $result = ''; $parent_block = null; foreach ( $blocks as $index => $block ) { if ( is_callable( $pre_callback ) ) { $prev = 0 === $index ? null : $blocks[ $index - 1 ]; $result .= call_user_func_array( $pre_callback, array( &$block, &$parent_block, $prev ) ); } if ( is_callable( $post_callback ) ) { $next = count( $blocks ) - 1 === $index ? null : $blocks[ $index + 1 ]; $post_markup = call_user_func_array( $post_callback, array( &$block, &$parent_block, $next ) ); } $result .= traverse_and_serialize_block( $block, $pre_callback, $post_callback ); $result .= isset( $post_markup ) ? $post_markup : ''; } return $result; } function filter_block_content( $text, $allowed_html = 'post', $allowed_protocols = array() ) { $result = ''; if ( str_contains( $text, '<!--' ) && str_contains( $text, '--->' ) ) { $text = preg_replace_callback( '%<!--(.*?)--->%', '_filter_block_content_callback', $text ); } $blocks = parse_blocks( $text ); foreach ( $blocks as $block ) { $block = filter_block_kses( $block, $allowed_html, $allowed_protocols ); $result .= serialize_block( $block ); } return $result; } function _filter_block_content_callback( $matches ) { return '<!--' . rtrim( $matches[1], '-' ) . '-->'; } function filter_block_kses( $block, $allowed_html, $allowed_protocols = array() ) { $block['attrs'] = filter_block_kses_value( $block['attrs'], $allowed_html, $allowed_protocols ); if ( is_array( $block['innerBlocks'] ) ) { foreach ( $block['innerBlocks'] as $i => $inner_block ) { $block['innerBlocks'][ $i ] = filter_block_kses( $inner_block, $allowed_html, $allowed_protocols ); } } return $block; } function filter_block_kses_value( $value, $allowed_html, $allowed_protocols = array() ) { if ( is_array( $value ) ) { foreach ( $value as $key => $inner_value ) { $filtered_key = filter_block_kses_value( $key, $allowed_html, $allowed_protocols ); $filtered_value = filter_block_kses_value( $inner_value, $allowed_html, $allowed_protocols ); if ( $filtered_key !== $key ) { unset( $value[ $key ] ); } $value[ $filtered_key ] = $filtered_value; } } elseif ( is_string( $value ) ) { return wp_kses( $value, $allowed_html, $allowed_protocols ); } return $value; } function excerpt_remove_blocks( $content ) { if ( ! has_blocks( $content ) ) { return $content; } $allowed_inner_blocks = array( null, 'core/freeform', 'core/heading', 'core/html', 'core/list', 'core/media-text', 'core/paragraph', 'core/preformatted', 'core/pullquote', 'core/quote', 'core/table', 'core/verse', ); $allowed_wrapper_blocks = array( 'core/columns', 'core/column', 'core/group', ); $allowed_wrapper_blocks = apply_filters( 'excerpt_allowed_wrapper_blocks', $allowed_wrapper_blocks ); $allowed_blocks = array_merge( $allowed_inner_blocks, $allowed_wrapper_blocks ); $allowed_blocks = apply_filters( 'excerpt_allowed_blocks', $allowed_blocks ); $blocks = parse_blocks( $content ); $output = ''; foreach ( $blocks as $block ) { if ( in_array( $block['blockName'], $allowed_blocks, true ) ) { if ( ! empty( $block['innerBlocks'] ) ) { if ( in_array( $block['blockName'], $allowed_wrapper_blocks, true ) ) { $output .= _excerpt_render_inner_blocks( $block, $allowed_blocks ); continue; } foreach ( $block['innerBlocks'] as $inner_block ) { if ( ! in_array( $inner_block['blockName'], $allowed_inner_blocks, true ) || ! empty( $inner_block['innerBlocks'] ) ) { continue 2; } } } $output .= render_block( $block ); } } return $output; } function excerpt_remove_footnotes( $content ) { if ( ! str_contains( $content, 'data-fn=' ) ) { return $content; } return preg_replace( '_<sup data-fn="[^"]+" class="[^"]+">\s*<a href="[^"]+" id="[^"]+">\d+</a>\s*</sup>_', '', $content ); } function _excerpt_render_inner_blocks( $parsed_block, $allowed_blocks ) { $output = ''; foreach ( $parsed_block['innerBlocks'] as $inner_block ) { if ( ! in_array( $inner_block['blockName'], $allowed_blocks, true ) ) { continue; } if ( empty( $inner_block['innerBlocks'] ) ) { $output .= render_block( $inner_block ); } else { $output .= _excerpt_render_inner_blocks( $inner_block, $allowed_blocks ); } } return $output; } function render_block( $parsed_block ) { global $post; $parent_block = null; $pre_render = apply_filters( 'pre_render_block', null, $parsed_block, $parent_block ); if ( ! is_null( $pre_render ) ) { return $pre_render; } $source_block = $parsed_block; $parsed_block = apply_filters( 'render_block_data', $parsed_block, $source_block, $parent_block ); $context = array(); if ( $post instanceof WP_Post ) { $context['postId'] = $post->ID; $context['postType'] = $post->post_type; } $context = apply_filters( 'render_block_context', $context, $parsed_block, $parent_block ); $block = new WP_Block( $parsed_block, $context ); return $block->render(); } function parse_blocks( $content ) { $parser_class = apply_filters( 'block_parser_class', 'WP_Block_Parser' ); $parser = new $parser_class(); return $parser->parse( $content ); } function do_blocks( $content ) { $blocks = parse_blocks( $content ); $output = ''; foreach ( $blocks as $block ) { $output .= render_block( $block ); } $priority = has_filter( 'the_content', 'wpautop' ); if ( false !== $priority && doing_filter( 'the_content' ) && has_blocks( $content ) ) { remove_filter( 'the_content', 'wpautop', $priority ); add_filter( 'the_content', '_restore_wpautop_hook', $priority + 1 ); } return $output; } function _restore_wpautop_hook( $content ) { $current_priority = has_filter( 'the_content', '_restore_wpautop_hook' ); add_filter( 'the_content', 'wpautop', $current_priority - 1 ); remove_filter( 'the_content', '_restore_wpautop_hook', $current_priority ); return $content; } function block_version( $content ) { return has_blocks( $content ) ? 1 : 0; } function register_block_style( $block_name, $style_properties ) { return WP_Block_Styles_Registry::get_instance()->register( $block_name, $style_properties ); } function unregister_block_style( $block_name, $block_style_name ) { return WP_Block_Styles_Registry::get_instance()->unregister( $block_name, $block_style_name ); } function block_has_support( $block_type, $feature, $default_value = false ) { $block_support = $default_value; if ( $block_type instanceof WP_Block_Type ) { if ( is_array( $feature ) && count( $feature ) === 1 ) { $feature = $feature[0]; } if ( is_array( $feature ) ) { $block_support = _wp_array_get( $block_type->supports, $feature, $default_value ); } elseif ( isset( $block_type->supports[ $feature ] ) ) { $block_support = $block_type->supports[ $feature ]; } } return true === $block_support || is_array( $block_support ); } function wp_migrate_old_typography_shape( $metadata ) { if ( ! isset( $metadata['supports'] ) ) { return $metadata; } $typography_keys = array( '__experimentalFontFamily', '__experimentalFontStyle', '__experimentalFontWeight', '__experimentalLetterSpacing', '__experimentalTextDecoration', '__experimentalTextTransform', 'fontSize', 'lineHeight', ); foreach ( $typography_keys as $typography_key ) { $support_for_key = isset( $metadata['supports'][ $typography_key ] ) ? $metadata['supports'][ $typography_key ] : null; if ( null !== $support_for_key ) { _doing_it_wrong( 'register_block_type_from_metadata()', sprintf( __( 'Block "%1$s" is declaring %2$s support in %3$s file under %4$s. %2$s support is now declared under %5$s.' ), $metadata['name'], "<code>$typography_key</code>", '<code>block.json</code>', "<code>supports.$typography_key</code>", "<code>supports.typography.$typography_key</code>" ), '5.8.0' ); _wp_array_set( $metadata['supports'], array( 'typography', $typography_key ), $support_for_key ); unset( $metadata['supports'][ $typography_key ] ); } } return $metadata; } function build_query_vars_from_query_block( $block, $page ) { $query = array( 'post_type' => 'post', 'order' => 'DESC', 'orderby' => 'date', 'post__not_in' => array(), ); if ( isset( $block->context['query'] ) ) { if ( ! empty( $block->context['query']['postType'] ) ) { $post_type_param = $block->context['query']['postType']; if ( is_post_type_viewable( $post_type_param ) ) { $query['post_type'] = $post_type_param; } } if ( isset( $block->context['query']['sticky'] ) && ! empty( $block->context['query']['sticky'] ) ) { $sticky = get_option( 'sticky_posts' ); if ( 'only' === $block->context['query']['sticky'] ) { $query['post__in'] = ! empty( $sticky ) ? $sticky : array( 0 ); $query['ignore_sticky_posts'] = 1; } else { $query['post__not_in'] = array_merge( $query['post__not_in'], $sticky ); } } if ( ! empty( $block->context['query']['exclude'] ) ) { $excluded_post_ids = array_map( 'intval', $block->context['query']['exclude'] ); $excluded_post_ids = array_filter( $excluded_post_ids ); $query['post__not_in'] = array_merge( $query['post__not_in'], $excluded_post_ids ); } if ( isset( $block->context['query']['perPage'] ) && is_numeric( $block->context['query']['perPage'] ) ) { $per_page = absint( $block->context['query']['perPage'] ); $offset = 0; if ( isset( $block->context['query']['offset'] ) && is_numeric( $block->context['query']['offset'] ) ) { $offset = absint( $block->context['query']['offset'] ); } $query['offset'] = ( $per_page * ( $page - 1 ) ) + $offset; $query['posts_per_page'] = $per_page; } if ( ! empty( $block->context['query']['categoryIds'] ) || ! empty( $block->context['query']['tagIds'] ) ) { $tax_query = array(); if ( ! empty( $block->context['query']['categoryIds'] ) ) { $tax_query[] = array( 'taxonomy' => 'category', 'terms' => array_filter( array_map( 'intval', $block->context['query']['categoryIds'] ) ), 'include_children' => false, ); } if ( ! empty( $block->context['query']['tagIds'] ) ) { $tax_query[] = array( 'taxonomy' => 'post_tag', 'terms' => array_filter( array_map( 'intval', $block->context['query']['tagIds'] ) ), 'include_children' => false, ); } $query['tax_query'] = $tax_query; } if ( ! empty( $block->context['query']['taxQuery'] ) ) { $query['tax_query'] = array(); foreach ( $block->context['query']['taxQuery'] as $taxonomy => $terms ) { if ( is_taxonomy_viewable( $taxonomy ) && ! empty( $terms ) ) { $query['tax_query'][] = array( 'taxonomy' => $taxonomy, 'terms' => array_filter( array_map( 'intval', $terms ) ), 'include_children' => false, ); } } } if ( isset( $block->context['query']['order'] ) && in_array( strtoupper( $block->context['query']['order'] ), array( 'ASC', 'DESC' ), true ) ) { $query['order'] = strtoupper( $block->context['query']['order'] ); } if ( isset( $block->context['query']['orderBy'] ) ) { $query['orderby'] = $block->context['query']['orderBy']; } if ( isset( $block->context['query']['author'] ) ) { if ( is_array( $block->context['query']['author'] ) ) { $query['author__in'] = array_filter( array_map( 'intval', $block->context['query']['author'] ) ); } elseif ( is_string( $block->context['query']['author'] ) ) { $query['author__in'] = array_filter( array_map( 'intval', explode( ',', $block->context['query']['author'] ) ) ); } elseif ( is_int( $block->context['query']['author'] ) && $block->context['query']['author'] > 0 ) { $query['author'] = $block->context['query']['author']; } } if ( ! empty( $block->context['query']['search'] ) ) { $query['s'] = $block->context['query']['search']; } if ( ! empty( $block->context['query']['parents'] ) && is_post_type_hierarchical( $query['post_type'] ) ) { $query['post_parent__in'] = array_filter( array_map( 'intval', $block->context['query']['parents'] ) ); } } return apply_filters( 'query_loop_block_query_vars', $query, $block, $page ); } function get_query_pagination_arrow( $block, $is_next ) { $arrow_map = array( 'none' => '', 'arrow' => array( 'next' => '→', 'previous' => '←', ), 'chevron' => array( 'next' => '»', 'previous' => '«', ), ); if ( ! empty( $block->context['paginationArrow'] ) && array_key_exists( $block->context['paginationArrow'], $arrow_map ) && ! empty( $arrow_map[ $block->context['paginationArrow'] ] ) ) { $pagination_type = $is_next ? 'next' : 'previous'; $arrow_attribute = $block->context['paginationArrow']; $arrow = $arrow_map[ $block->context['paginationArrow'] ][ $pagination_type ]; $arrow_classes = "wp-block-query-pagination-$pagination_type-arrow is-arrow-$arrow_attribute"; return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>"; } return null; } function build_comment_query_vars_from_block( $block ) { $comment_args = array( 'orderby' => 'comment_date_gmt', 'order' => 'ASC', 'status' => 'approve', 'no_found_rows' => false, ); if ( is_user_logged_in() ) { $comment_args['include_unapproved'] = array( get_current_user_id() ); } else { $unapproved_email = wp_get_unapproved_comment_author_email(); if ( $unapproved_email ) { $comment_args['include_unapproved'] = array( $unapproved_email ); } } if ( ! empty( $block->context['postId'] ) ) { $comment_args['post_id'] = (int) $block->context['postId']; } if ( get_option( 'thread_comments' ) ) { $comment_args['hierarchical'] = 'threaded'; } else { $comment_args['hierarchical'] = false; } if ( get_option( 'page_comments' ) === '1' || get_option( 'page_comments' ) === true ) { $per_page = get_option( 'comments_per_page' ); $default_page = get_option( 'default_comments_page' ); if ( $per_page > 0 ) { $comment_args['number'] = $per_page; $page = (int) get_query_var( 'cpage' ); if ( $page ) { $comment_args['paged'] = $page; } elseif ( 'oldest' === $default_page ) { $comment_args['paged'] = 1; } elseif ( 'newest' === $default_page ) { $max_num_pages = (int) ( new WP_Comment_Query( $comment_args ) )->max_num_pages; if ( 0 !== $max_num_pages ) { $comment_args['paged'] = $max_num_pages; } } if ( 0 === $page && isset( $comment_args['paged'] ) && $comment_args['paged'] > 0 ) { set_query_var( 'cpage', $comment_args['paged'] ); } } } return $comment_args; } function get_comments_pagination_arrow( $block, $pagination_type = 'next' ) { $arrow_map = array( 'none' => '', 'arrow' => array( 'next' => '→', 'previous' => '←', ), 'chevron' => array( 'next' => '»', 'previous' => '«', ), ); if ( ! empty( $block->context['comments/paginationArrow'] ) && ! empty( $arrow_map[ $block->context['comments/paginationArrow'] ][ $pagination_type ] ) ) { $arrow_attribute = $block->context['comments/paginationArrow']; $arrow = $arrow_map[ $block->context['comments/paginationArrow'] ][ $pagination_type ]; $arrow_classes = "wp-block-comments-pagination-$pagination_type-arrow is-arrow-$arrow_attribute"; return "<span class='$arrow_classes' aria-hidden='true'>$arrow</span>"; } return null; } function _wp_filter_post_meta_footnotes( $footnotes ) { $footnotes_decoded = json_decode( $footnotes, true ); if ( ! is_array( $footnotes_decoded ) ) { return ''; } $footnotes_sanitized = array(); foreach ( $footnotes_decoded as $footnote ) { if ( ! empty( $footnote['content'] ) && ! empty( $footnote['id'] ) ) { $footnotes_sanitized[] = array( 'id' => sanitize_key( $footnote['id'] ), 'content' => wp_unslash( wp_filter_post_kses( wp_slash( $footnote['content'] ) ) ), ); } } return wp_json_encode( $footnotes_sanitized ); } function _wp_footnotes_kses_init_filters() { add_filter( 'sanitize_post_meta_footnotes', '_wp_filter_post_meta_footnotes' ); } function _wp_footnotes_remove_filters() { remove_filter( 'sanitize_post_meta_footnotes', '_wp_filter_post_meta_footnotes' ); } function _wp_footnotes_kses_init() { _wp_footnotes_remove_filters(); if ( ! current_user_can( 'unfiltered_html' ) ) { _wp_footnotes_kses_init_filters(); } } function _wp_footnotes_force_filtered_html_on_import_filter( $arg ) { if ( $arg ) { _wp_footnotes_kses_init_filters(); } return $arg; } 