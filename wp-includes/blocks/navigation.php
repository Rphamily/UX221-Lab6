<?php
 if ( defined( 'IS_GUTENBERG_PLUGIN' ) && IS_GUTENBERG_PLUGIN ) { function block_core_navigation_get_menu_items_at_location( $location ) { if ( empty( $location ) ) { return; } $locations = get_nav_menu_locations(); if ( ! isset( $locations[ $location ] ) ) { return; } $menu = wp_get_nav_menu_object( $locations[ $location ] ); if ( ! $menu || is_wp_error( $menu ) ) { return; } $menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) ); _wp_menu_item_classes_by_context( $menu_items ); return $menu_items; } function block_core_navigation_sort_menu_items_by_parent_id( $menu_items ) { $sorted_menu_items = array(); foreach ( (array) $menu_items as $menu_item ) { $sorted_menu_items[ $menu_item->menu_order ] = $menu_item; } unset( $menu_items, $menu_item ); $menu_items_by_parent_id = array(); foreach ( $sorted_menu_items as $menu_item ) { $menu_items_by_parent_id[ $menu_item->menu_item_parent ][] = $menu_item; } return $menu_items_by_parent_id; } } function block_core_navigation_add_directives_to_submenu( $w, $block_attributes ) { while ( $w->next_tag( array( 'tag_name' => 'LI', 'class_name' => 'has-child', ) ) ) { $w->set_attribute( 'data-wp-interactive', true ); $w->set_attribute( 'data-wp-context', '{ "core": { "navigation": { "submenuOpenedBy": {}, "type": "submenu" } } }' ); $w->set_attribute( 'data-wp-effect', 'effects.core.navigation.initMenu' ); $w->set_attribute( 'data-wp-on--focusout', 'actions.core.navigation.handleMenuFocusout' ); $w->set_attribute( 'data-wp-on--keydown', 'actions.core.navigation.handleMenuKeydown' ); $w->set_attribute( 'tabindex', '-1' ); if ( ! isset( $block_attributes['openSubmenusOnClick'] ) || false === $block_attributes['openSubmenusOnClick'] ) { $w->set_attribute( 'data-wp-on--mouseenter', 'actions.core.navigation.openMenuOnHover' ); $w->set_attribute( 'data-wp-on--mouseleave', 'actions.core.navigation.closeMenuOnHover' ); } if ( $w->next_tag( array( 'tag_name' => 'BUTTON', 'class_name' => 'wp-block-navigation-submenu__toggle', ) ) ) { $w->set_attribute( 'data-wp-on--click', 'actions.core.navigation.toggleMenuOnClick' ); $w->set_attribute( 'data-wp-bind--aria-expanded', 'selectors.core.navigation.isMenuOpen' ); } if ( $w->next_tag( array( 'tag_name' => 'UL', 'class_name' => 'wp-block-navigation__submenu-container', ) ) ) { $w->set_attribute( 'data-wp-on--focus', 'actions.core.navigation.openMenuOnFocus' ); } block_core_navigation_add_directives_to_submenu( $w, $block_attributes ); } return $w->get_updated_html(); } function block_core_navigation_build_css_colors( $attributes ) { $colors = array( 'css_classes' => array(), 'inline_styles' => '', 'overlay_css_classes' => array(), 'overlay_inline_styles' => '', ); $has_named_text_color = array_key_exists( 'textColor', $attributes ); $has_custom_text_color = array_key_exists( 'customTextColor', $attributes ); if ( $has_custom_text_color || $has_named_text_color ) { $colors['css_classes'][] = 'has-text-color'; } if ( $has_named_text_color ) { $colors['css_classes'][] = sprintf( 'has-%s-color', $attributes['textColor'] ); } elseif ( $has_custom_text_color ) { $colors['inline_styles'] .= sprintf( 'color: %s;', $attributes['customTextColor'] ); } $has_named_background_color = array_key_exists( 'backgroundColor', $attributes ); $has_custom_background_color = array_key_exists( 'customBackgroundColor', $attributes ); if ( $has_custom_background_color || $has_named_background_color ) { $colors['css_classes'][] = 'has-background'; } if ( $has_named_background_color ) { $colors['css_classes'][] = sprintf( 'has-%s-background-color', $attributes['backgroundColor'] ); } elseif ( $has_custom_background_color ) { $colors['inline_styles'] .= sprintf( 'background-color: %s;', $attributes['customBackgroundColor'] ); } $has_named_overlay_text_color = array_key_exists( 'overlayTextColor', $attributes ); $has_custom_overlay_text_color = array_key_exists( 'customOverlayTextColor', $attributes ); if ( $has_custom_overlay_text_color || $has_named_overlay_text_color ) { $colors['overlay_css_classes'][] = 'has-text-color'; } if ( $has_named_overlay_text_color ) { $colors['overlay_css_classes'][] = sprintf( 'has-%s-color', $attributes['overlayTextColor'] ); } elseif ( $has_custom_overlay_text_color ) { $colors['overlay_inline_styles'] .= sprintf( 'color: %s;', $attributes['customOverlayTextColor'] ); } $has_named_overlay_background_color = array_key_exists( 'overlayBackgroundColor', $attributes ); $has_custom_overlay_background_color = array_key_exists( 'customOverlayBackgroundColor', $attributes ); if ( $has_custom_overlay_background_color || $has_named_overlay_background_color ) { $colors['overlay_css_classes'][] = 'has-background'; } if ( $has_named_overlay_background_color ) { $colors['overlay_css_classes'][] = sprintf( 'has-%s-background-color', $attributes['overlayBackgroundColor'] ); } elseif ( $has_custom_overlay_background_color ) { $colors['overlay_inline_styles'] .= sprintf( 'background-color: %s;', $attributes['customOverlayBackgroundColor'] ); } return $colors; } function block_core_navigation_build_css_font_sizes( $attributes ) { $font_sizes = array( 'css_classes' => array(), 'inline_styles' => '', ); $has_named_font_size = array_key_exists( 'fontSize', $attributes ); $has_custom_font_size = array_key_exists( 'customFontSize', $attributes ); if ( $has_named_font_size ) { $font_sizes['css_classes'][] = sprintf( 'has-%s-font-size', $attributes['fontSize'] ); } elseif ( $has_custom_font_size ) { $font_sizes['inline_styles'] = sprintf( 'font-size: %spx;', $attributes['customFontSize'] ); } return $font_sizes; } function block_core_navigation_render_submenu_icon() { return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>'; } function block_core_navigation_filter_out_empty_blocks( $parsed_blocks ) { $filtered = array_filter( $parsed_blocks, static function ( $block ) { return isset( $block['blockName'] ); } ); return array_values( $filtered ); } function block_core_navigation_block_contains_core_navigation( $inner_blocks ) { foreach ( $inner_blocks as $block ) { if ( 'core/navigation' === $block->name ) { return true; } if ( $block->inner_blocks && block_core_navigation_block_contains_core_navigation( $block->inner_blocks ) ) { return true; } } return false; } function block_core_navigation_get_fallback_blocks() { $page_list_fallback = array( array( 'blockName' => 'core/page-list', ), ); $registry = WP_Block_Type_Registry::get_instance(); $fallback_blocks = $registry->is_registered( 'core/page-list' ) ? $page_list_fallback : array(); if ( class_exists( 'WP_Navigation_Fallback' ) ) { $navigation_post = WP_Navigation_Fallback::get_fallback(); } else { $navigation_post = Gutenberg_Navigation_Fallback::get_fallback(); } if ( $navigation_post ) { $parsed_blocks = parse_blocks( $navigation_post->post_content ); $maybe_fallback = block_core_navigation_filter_out_empty_blocks( $parsed_blocks ); $fallback_blocks = ! empty( $maybe_fallback ) ? $maybe_fallback : $fallback_blocks; } return apply_filters( 'block_core_navigation_render_fallback', $fallback_blocks ); } function block_core_navigation_get_post_ids( $inner_blocks ) { $post_ids = array_map( 'block_core_navigation_from_block_get_post_ids', iterator_to_array( $inner_blocks ) ); return array_unique( array_merge( ...$post_ids ) ); } function block_core_navigation_from_block_get_post_ids( $block ) { $post_ids = array(); if ( $block->inner_blocks ) { $post_ids = block_core_navigation_get_post_ids( $block->inner_blocks ); } if ( 'core/navigation-link' === $block->name || 'core/navigation-submenu' === $block->name ) { if ( $block->attributes && isset( $block->attributes['kind'] ) && 'post-type' === $block->attributes['kind'] && isset( $block->attributes['id'] ) ) { $post_ids[] = $block->attributes['id']; } } return $post_ids; } function render_block_core_navigation( $attributes, $content, $block ) { static $seen_menu_names = array(); $is_fallback = false; $nav_menu_name = $attributes['ariaLabel'] ?? ''; if ( isset( $attributes['rgbTextColor'] ) && empty( $attributes['textColor'] ) ) { $attributes['customTextColor'] = $attributes['rgbTextColor']; } if ( isset( $attributes['rgbBackgroundColor'] ) && empty( $attributes['backgroundColor'] ) ) { $attributes['customBackgroundColor'] = $attributes['rgbBackgroundColor']; } unset( $attributes['rgbTextColor'], $attributes['rgbBackgroundColor'] ); $has_old_responsive_attribute = ! empty( $attributes['isResponsive'] ) && $attributes['isResponsive']; $is_responsive_menu = isset( $attributes['overlayMenu'] ) && 'never' !== $attributes['overlayMenu'] || $has_old_responsive_attribute; $inner_blocks = $block->inner_blocks; if ( array_key_exists( 'navigationMenuId', $attributes ) ) { $attributes['ref'] = $attributes['navigationMenuId']; } if ( defined( 'IS_GUTENBERG_PLUGIN' ) && IS_GUTENBERG_PLUGIN && array_key_exists( '__unstableLocation', $attributes ) && ! array_key_exists( 'ref', $attributes ) && ! empty( block_core_navigation_get_menu_items_at_location( $attributes['__unstableLocation'] ) ) ) { $menu_items = block_core_navigation_get_menu_items_at_location( $attributes['__unstableLocation'] ); if ( empty( $menu_items ) ) { return ''; } $menu_items_by_parent_id = block_core_navigation_sort_menu_items_by_parent_id( $menu_items ); $parsed_blocks = block_core_navigation_parse_blocks_from_menu_items( $menu_items_by_parent_id[0], $menu_items_by_parent_id ); $inner_blocks = new WP_Block_List( $parsed_blocks, $attributes ); } if ( array_key_exists( 'ref', $attributes ) ) { $navigation_post = get_post( $attributes['ref'] ); if ( ! isset( $navigation_post ) ) { return ''; } if ( 'publish' === $navigation_post->post_status ) { $nav_menu_name = $navigation_post->post_title; if ( isset( $seen_menu_names[ $nav_menu_name ] ) ) { ++$seen_menu_names[ $nav_menu_name ]; } else { $seen_menu_names[ $nav_menu_name ] = 1; } $parsed_blocks = parse_blocks( $navigation_post->post_content ); $compacted_blocks = block_core_navigation_filter_out_empty_blocks( $parsed_blocks ); $inner_blocks = new WP_Block_List( $compacted_blocks, $attributes ); } } if ( empty( $inner_blocks ) ) { $is_fallback = true; $fallback_blocks = block_core_navigation_get_fallback_blocks(); if ( empty( $fallback_blocks ) || ! is_array( $fallback_blocks ) ) { return ''; } $inner_blocks = new WP_Block_List( $fallback_blocks, $attributes ); } if ( block_core_navigation_block_contains_core_navigation( $inner_blocks ) ) { return ''; } $inner_blocks = apply_filters( 'block_core_navigation_render_inner_blocks', $inner_blocks ); $layout_justification = array( 'left' => 'items-justified-left', 'right' => 'items-justified-right', 'center' => 'items-justified-center', 'space-between' => 'items-justified-space-between', ); $layout_class = ''; if ( isset( $attributes['layout']['justifyContent'] ) && isset( $layout_justification[ $attributes['layout']['justifyContent'] ] ) ) { $layout_class .= $layout_justification[ $attributes['layout']['justifyContent'] ]; } if ( isset( $attributes['layout']['orientation'] ) && 'vertical' === $attributes['layout']['orientation'] ) { $layout_class .= ' is-vertical'; } if ( isset( $attributes['layout']['flexWrap'] ) && 'nowrap' === $attributes['layout']['flexWrap'] ) { $layout_class .= ' no-wrap'; } $text_decoration = $attributes['style']['typography']['textDecoration'] ?? null; $text_decoration_class = sprintf( 'has-text-decoration-%s', $text_decoration ); $colors = block_core_navigation_build_css_colors( $attributes ); $font_sizes = block_core_navigation_build_css_font_sizes( $attributes ); $classes = array_merge( $colors['css_classes'], $font_sizes['css_classes'], $is_responsive_menu ? array( 'is-responsive' ) : array(), $layout_class ? array( $layout_class ) : array(), $is_fallback ? array( 'is-fallback' ) : array(), $text_decoration ? array( $text_decoration_class ) : array() ); $post_ids = block_core_navigation_get_post_ids( $inner_blocks ); if ( $post_ids ) { _prime_post_caches( $post_ids, false, false ); } $list_item_nav_blocks = array( 'core/navigation-link', 'core/home-link', 'core/site-title', 'core/site-logo', 'core/navigation-submenu', ); $needs_list_item_wrapper = array( 'core/site-title', 'core/site-logo', ); $block_styles = isset( $attributes['styles'] ) ? $attributes['styles'] : ''; $style = $block_styles . $colors['inline_styles'] . $font_sizes['inline_styles']; $class = implode( ' ', $classes ); if ( isset( $seen_menu_names[ $nav_menu_name ] ) && $seen_menu_names[ $nav_menu_name ] > 1 ) { $count = $seen_menu_names[ $nav_menu_name ]; $nav_menu_name = $nav_menu_name . ' ' . ( $count ); } $wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $class, 'style' => $style, 'aria-label' => $nav_menu_name, ) ); $container_attributes = get_block_wrapper_attributes( array( 'class' => 'wp-block-navigation__container ' . $class, 'style' => $style, ) ); $inner_blocks_html = ''; $is_list_open = false; $has_submenus = false; foreach ( $inner_blocks as $inner_block ) { $is_list_item = in_array( $inner_block->name, $list_item_nav_blocks, true ); if ( $is_list_item && ! $is_list_open ) { $is_list_open = true; $inner_blocks_html .= sprintf( '<ul %1$s>', $container_attributes ); } if ( ! $is_list_item && $is_list_open ) { $is_list_open = false; $inner_blocks_html .= '</ul>'; } $inner_block_content = $inner_block->render(); $p = new WP_HTML_Tag_Processor( $inner_block_content ); if ( $p->next_tag( array( 'name' => 'LI', 'class_name' => 'has-child', ) ) ) { $has_submenus = true; } if ( ! empty( $inner_block_content ) ) { if ( in_array( $inner_block->name, $needs_list_item_wrapper, true ) ) { $inner_blocks_html .= '<li class="wp-block-navigation-item">' . $inner_block_content . '</li>'; } else { $inner_blocks_html .= $inner_block_content; } } } if ( $is_list_open ) { $inner_blocks_html .= '</ul>'; } $should_load_view_script = ( $has_submenus && ( $attributes['openSubmenusOnClick'] || $attributes['showSubmenuIcon'] ) ) || $is_responsive_menu; $view_js_file = 'wp-block-navigation-view'; if ( ! wp_script_is( $view_js_file ) ) { $script_handles = $block->block_type->view_script_handles; if ( ! $should_load_view_script && in_array( $view_js_file, $script_handles, true ) ) { $block->block_type->view_script_handles = array_diff( $script_handles, array( $view_js_file ) ); } if ( $should_load_view_script && ! in_array( $view_js_file, $script_handles, true ) ) { $block->block_type->view_script_handles = array_merge( $script_handles, array( $view_js_file ) ); } } if ( $has_submenus && $should_load_view_script ) { $w = new WP_HTML_Tag_Processor( $inner_blocks_html ); $inner_blocks_html = block_core_navigation_add_directives_to_submenu( $w, $attributes ); } $modal_unique_id = wp_unique_id( 'modal-' ); if ( ! $is_responsive_menu ) { return sprintf( '<nav %1$s>%2$s</nav>', $wrapper_attributes, $inner_blocks_html ); } $is_hidden_by_default = isset( $attributes['overlayMenu'] ) && 'always' === $attributes['overlayMenu']; $responsive_container_classes = array( 'wp-block-navigation__responsive-container', $is_hidden_by_default ? 'hidden-by-default' : '', implode( ' ', $colors['overlay_css_classes'] ), ); $open_button_classes = array( 'wp-block-navigation__responsive-container-open', $is_hidden_by_default ? 'always-shown' : '', ); $should_display_icon_label = isset( $attributes['hasIcon'] ) && true === $attributes['hasIcon']; $toggle_button_icon = '<svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5" /><rect x="4" y="15" width="16" height="1.5" /></svg>'; if ( isset( $attributes['icon'] ) ) { if ( 'menu' === $attributes['icon'] ) { $toggle_button_icon = '<svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 5v1.5h14V5H5zm0 7.8h14v-1.5H5v1.5zM5 19h14v-1.5H5V19z" /></svg>'; } } $toggle_button_content = $should_display_icon_label ? $toggle_button_icon : __( 'Menu' ); $toggle_close_button_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>'; $toggle_close_button_content = $should_display_icon_label ? $toggle_close_button_icon : __( 'Close' ); $toggle_aria_label_open = $should_display_icon_label ? 'aria-label="' . __( 'Open menu' ) . '"' : ''; $toggle_aria_label_close = $should_display_icon_label ? 'aria-label="' . __( 'Close menu' ) . '"' : ''; $nav_element_directives = ''; $open_button_directives = ''; $responsive_container_directives = ''; $responsive_dialog_directives = ''; $close_button_directives = ''; if ( $should_load_view_script ) { $nav_element_context = wp_json_encode( array( 'core' => array( 'navigation' => array( 'overlayOpenedBy' => array(), 'type' => 'overlay', 'roleAttribute' => '', 'ariaLabel' => __( 'Menu' ), ), ), ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP ); $nav_element_directives = '
			data-wp-interactive
			data-wp-context=\'' . $nav_element_context . '\'
		'; $open_button_directives = '
			data-wp-on--click="actions.core.navigation.openMenuOnClick"
			data-wp-on--keydown="actions.core.navigation.handleMenuKeydown"
		'; $responsive_container_directives = '
			data-wp-class--has-modal-open="selectors.core.navigation.isMenuOpen"
			data-wp-class--is-menu-open="selectors.core.navigation.isMenuOpen"
			data-wp-effect="effects.core.navigation.initMenu"
			data-wp-on--keydown="actions.core.navigation.handleMenuKeydown"
			data-wp-on--focusout="actions.core.navigation.handleMenuFocusout"
			tabindex="-1"
		'; $responsive_dialog_directives = '
			data-wp-bind--aria-modal="selectors.core.navigation.ariaModal"
			data-wp-bind--aria-label="selectors.core.navigation.ariaLabel"
			data-wp-bind--role="selectors.core.navigation.roleAttribute"
			data-wp-effect="effects.core.navigation.focusFirstElement"
		'; $close_button_directives = '
			data-wp-on--click="actions.core.navigation.closeMenuOnClick"
		'; } $responsive_container_markup = sprintf( '<button aria-haspopup="true" %3$s class="%6$s" %10$s>%8$s</button>
			<div class="%5$s" style="%7$s" id="%1$s" %11$s>
				<div class="wp-block-navigation__responsive-close" tabindex="-1">
					<div class="wp-block-navigation__responsive-dialog" %12$s>
							<button %4$s class="wp-block-navigation__responsive-container-close" %13$s>%9$s</button>
						<div class="wp-block-navigation__responsive-container-content" id="%1$s-content">
							%2$s
						</div>
					</div>
				</div>
			</div>', esc_attr( $modal_unique_id ), $inner_blocks_html, $toggle_aria_label_open, $toggle_aria_label_close, esc_attr( implode( ' ', $responsive_container_classes ) ), esc_attr( implode( ' ', $open_button_classes ) ), esc_attr( safecss_filter_attr( $colors['overlay_inline_styles'] ) ), $toggle_button_content, $toggle_close_button_content, $open_button_directives, $responsive_container_directives, $responsive_dialog_directives, $close_button_directives ); return sprintf( '<nav %1$s %3$s>%2$s</nav>', $wrapper_attributes, $responsive_container_markup, $nav_element_directives ); } function register_block_core_navigation() { register_block_type_from_metadata( __DIR__ . '/navigation', array( 'render_callback' => 'render_block_core_navigation', ) ); } add_action( 'init', 'register_block_core_navigation' ); function block_core_navigation_typographic_presets_backcompatibility( $parsed_block ) { if ( 'core/navigation' === $parsed_block['blockName'] ) { $attribute_to_prefix_map = array( 'fontStyle' => 'var:preset|font-style|', 'fontWeight' => 'var:preset|font-weight|', 'textDecoration' => 'var:preset|text-decoration|', 'textTransform' => 'var:preset|text-transform|', ); foreach ( $attribute_to_prefix_map as $style_attribute => $prefix ) { if ( ! empty( $parsed_block['attrs']['style']['typography'][ $style_attribute ] ) ) { $prefix_len = strlen( $prefix ); $attribute_value = &$parsed_block['attrs']['style']['typography'][ $style_attribute ]; if ( 0 === strncmp( $attribute_value, $prefix, $prefix_len ) ) { $attribute_value = substr( $attribute_value, $prefix_len ); } if ( 'textDecoration' === $style_attribute && 'strikethrough' === $attribute_value ) { $attribute_value = 'line-through'; } } } } return $parsed_block; } add_filter( 'render_block_data', 'block_core_navigation_typographic_presets_backcompatibility' ); function block_core_navigation_ensure_interactivity_dependency() { global $wp_scripts; if ( isset( $wp_scripts->registered['wp-block-navigation-view'] ) && ! in_array( 'wp-interactivity', $wp_scripts->registered['wp-block-navigation-view']->deps, true ) ) { $wp_scripts->registered['wp-block-navigation-view']->deps[] = 'wp-interactivity'; } } add_action( 'wp_print_scripts', 'block_core_navigation_ensure_interactivity_dependency' ); function block_core_navigation_parse_blocks_from_menu_items( $menu_items, $menu_items_by_parent_id ) { _deprecated_function( __FUNCTION__, '6.3.0', 'WP_Navigation_Fallback::parse_blocks_from_menu_items' ); if ( empty( $menu_items ) ) { return array(); } $blocks = array(); foreach ( $menu_items as $menu_item ) { $class_name = ! empty( $menu_item->classes ) ? implode( ' ', (array) $menu_item->classes ) : null; $id = ( null !== $menu_item->object_id && 'custom' !== $menu_item->object ) ? $menu_item->object_id : null; $opens_in_new_tab = null !== $menu_item->target && '_blank' === $menu_item->target; $rel = ( null !== $menu_item->xfn && '' !== $menu_item->xfn ) ? $menu_item->xfn : null; $kind = null !== $menu_item->type ? str_replace( '_', '-', $menu_item->type ) : 'custom'; $block = array( 'blockName' => isset( $menu_items_by_parent_id[ $menu_item->ID ] ) ? 'core/navigation-submenu' : 'core/navigation-link', 'attrs' => array( 'className' => $class_name, 'description' => $menu_item->description, 'id' => $id, 'kind' => $kind, 'label' => $menu_item->title, 'opensInNewTab' => $opens_in_new_tab, 'rel' => $rel, 'title' => $menu_item->attr_title, 'type' => $menu_item->object, 'url' => $menu_item->url, ), ); $block['innerBlocks'] = isset( $menu_items_by_parent_id[ $menu_item->ID ] ) ? block_core_navigation_parse_blocks_from_menu_items( $menu_items_by_parent_id[ $menu_item->ID ], $menu_items_by_parent_id ) : array(); $block['innerContent'] = array_map( 'serialize_block', $block['innerBlocks'] ); $blocks[] = $block; } return $blocks; } function block_core_navigation_get_classic_menu_fallback() { _deprecated_function( __FUNCTION__, '6.3.0', 'WP_Navigation_Fallback::get_classic_menu_fallback' ); $classic_nav_menus = wp_get_nav_menus(); if ( $classic_nav_menus && ! is_wp_error( $classic_nav_menus ) ) { $locations = get_nav_menu_locations(); if ( isset( $locations['primary'] ) ) { $primary_menu = wp_get_nav_menu_object( $locations['primary'] ); if ( $primary_menu ) { return $primary_menu; } } foreach ( $classic_nav_menus as $classic_nav_menu ) { if ( 'primary' === $classic_nav_menu->slug ) { return $classic_nav_menu; } } usort( $classic_nav_menus, static function ( $a, $b ) { return $b->term_id - $a->term_id; } ); return $classic_nav_menus[0]; } } function block_core_navigation_get_classic_menu_fallback_blocks( $classic_nav_menu ) { _deprecated_function( __FUNCTION__, '6.3.0', 'WP_Navigation_Fallback::get_classic_menu_fallback_blocks' ); $menu_items = wp_get_nav_menu_items( $classic_nav_menu->term_id, array( 'update_post_term_cache' => false ) ); _wp_menu_item_classes_by_context( $menu_items ); $sorted_menu_items = array(); foreach ( (array) $menu_items as $menu_item ) { $sorted_menu_items[ $menu_item->menu_order ] = $menu_item; } unset( $menu_items, $menu_item ); $menu_items_by_parent_id = array(); foreach ( $sorted_menu_items as $menu_item ) { $menu_items_by_parent_id[ $menu_item->menu_item_parent ][] = $menu_item; } $inner_blocks = block_core_navigation_parse_blocks_from_menu_items( isset( $menu_items_by_parent_id[0] ) ? $menu_items_by_parent_id[0] : array(), $menu_items_by_parent_id ); return serialize_blocks( $inner_blocks ); } function block_core_navigation_maybe_use_classic_menu_fallback() { _deprecated_function( __FUNCTION__, '6.3.0', 'WP_Navigation_Fallback::create_classic_menu_fallback' ); $classic_nav_menu = block_core_navigation_get_classic_menu_fallback(); if ( ! $classic_nav_menu ) { return; } $classic_nav_menu_blocks = block_core_navigation_get_classic_menu_fallback_blocks( $classic_nav_menu ); if ( empty( $classic_nav_menu_blocks ) ) { return; } $wp_insert_post_result = wp_insert_post( array( 'post_content' => $classic_nav_menu_blocks, 'post_title' => $classic_nav_menu->name, 'post_name' => $classic_nav_menu->slug, 'post_status' => 'publish', 'post_type' => 'wp_navigation', ), true ); if ( is_wp_error( $wp_insert_post_result ) ) { return; } return block_core_navigation_get_most_recently_published_navigation(); } function block_core_navigation_get_most_recently_published_navigation() { _deprecated_function( __FUNCTION__, '6.3.0', 'WP_Navigation_Fallback::get_most_recently_published_navigation' ); $parsed_args = array( 'post_type' => 'wp_navigation', 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false, 'order' => 'DESC', 'orderby' => 'date', 'post_status' => 'publish', 'posts_per_page' => 1, ); $navigation_post = new WP_Query( $parsed_args ); if ( count( $navigation_post->posts ) > 0 ) { return $navigation_post->posts[0]; } return null; } 