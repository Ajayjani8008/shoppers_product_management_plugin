<?php

if (!defined('ABSPATH')) exit;


function cpm_ajax_save_product()
{
    check_ajax_referer('cpm_product_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    $product_data = [
        'post_title'   => sanitize_text_field($_POST['product_name']),
        'post_content' => wp_kses_post($_POST['product_description']),
        'post_status'  => 'publish',
        'post_type'    => 'product',
    ];


    if ($product_id) {
        $product_data['ID'] = $product_id;
    }


    $product_id = wp_insert_post($product_data);

    if (!is_wp_error($product_id)) {
        $product = wc_get_product($product_id);


        $product->set_regular_price(floatval($_POST['product_regular_price']));
        $product->set_sale_price(floatval($_POST['product_sale_price']));


        if (!empty($_POST['product_category'])) {
            wp_set_object_terms($product_id, intval($_POST['product_category']), 'product_cat');
        }


        $product->set_stock_status(sanitize_text_field($_POST['stock_status']));


        if (!empty($_FILES['product_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $image_id = media_handle_upload('product_image', $product_id);

            if (!is_wp_error($image_id)) {
                set_post_thumbnail($product_id, $image_id);
            }
        }


        $product->save();


        ob_start();
        cpm_display_product_list();
        $product_list_html = ob_get_clean();

        wp_send_json_success([
            'message'        => 'Product saved successfully.',
            'product_list'   => $product_list_html,
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to save product.']);
    }
}

add_action('wp_ajax_cpm_save_product', 'cpm_ajax_save_product');


function cpm_ajax_delete_product()
{
    check_ajax_referer('cpm_product_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id && wp_delete_post($product_id, true)) {

        ob_start();
        cpm_display_product_list();
        $product_list_html = ob_get_clean();

        wp_send_json_success([
            'message' => 'Product deleted successfully.',
            'product_list' => $product_list_html,
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to delete product.']);
    }
}
add_action('wp_ajax_cpm_delete_product', 'cpm_ajax_delete_product');


function cpm_ajax_fetch_product()
{
    check_ajax_referer('cpm_product_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
    }

    $product = wc_get_product($product_id);

    if ($product) {
        $response = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'description' => $product->get_description(),
            'stock_status' => $product->get_stock_status(),
            'category' => wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']),
            'image' => has_post_thumbnail($product_id) ? get_the_post_thumbnail_url($product_id, 'medium') : wc_placeholder_img_src(),
        ];
        wp_send_json_success(['product' => $response]);
    } else {
        wp_send_json_error(['message' => 'Product not found.']);
    }
}
add_action('wp_ajax_cpm_fetch_product', 'cpm_ajax_fetch_product');


function cpm_ajax_refresh_product_list()
{
    check_ajax_referer('cpm_product_nonce', 'nonce');

    ob_start();
    cpm_display_product_list();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_cpm_refresh_product_list', 'cpm_ajax_refresh_product_list');




function handle_ajax_pagination()
{
    check_ajax_referer('cpm_product_nonce', 'nonce');
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : '';
    $posts_per_page = 10;

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
    );
    if ($category_id) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $category_id,
                'operator' => 'IN',
            ),
        );
    }

    $products = new WP_Query($args);

    ob_start();

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());

            $tags = get_the_terms(get_the_ID(), 'product_tag');
            $tag_list = $tags && !is_wp_error($tags) ? implode(', ', wp_list_pluck($tags, 'name')) : 'No Tags';
?>
            <tr id="product-row-<?php echo $product->get_id(); ?>">
                <td class="product-image"><?php echo $product->get_image('small_product_thumbnail'); ?></td>
                <td class="product-name"><?php echo esc_html($product->get_name()); ?></td>
                <td class="product-regular-price"><?php echo wc_price($product->get_regular_price()); ?></td>
                <td class="product-sale-price"><?php echo wc_price($product->get_sale_price()); ?></td>
                <td class="product-tags"><?php echo esc_html($tag_list); ?></td>
                <td class="product-stock"><?php echo ucfirst(esc_html($product->get_stock_status())); ?></td>
                <td class="product-actions">
                    <a href="#" class="cpm-edit-product" data-product-id="<?php echo $product->get_id(); ?>">Edit</a>
                    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="cpm-view-product" target="_blank">View</a>
                    <a href="#" class="cpm-delete-product" data-product-id="<?php echo $product->get_id(); ?>">Delete</a>
                </td>
            </tr>
<?php
        }
    }

    wp_reset_postdata();

    $output = ob_get_clean();


    ob_start();

    echo '<div class="ajax-pagination">';
    $total_pages = $products->max_num_pages;
    $range = 2;
    $start = max(1, $paged - $range);
    $end = min($total_pages, $paged + $range);

  
    if ($paged > 1) {
        echo '<a href="#" data-page="1" class="page-number first">First</a>';
        echo '<a href="#" data-page="' . ($paged - 1) . '" class="page-number prev">Previous</a>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active_class = ($i == $paged) ? 'active' : '';
        echo '<a href="#" class="page-number ' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
    }

    if ($end < $total_pages - 1) {
        echo '<span class="ellipsis">...</span>';
    }

    if ($paged < $total_pages) {
        echo '<a href="#" data-page="' . ($paged + 1) . '" class="page-number next">Next</a>';
        echo '<a href="#" data-page="' . $total_pages . '" class="page-number last">Last</a>';
    }
    echo "</div>";

    $pagination_html = ob_get_clean();

    wp_send_json_success([
        'content' => $output,
        'pagination' => $pagination_html,
    ]);


    wp_die();
}

add_action('wp_ajax_handle_ajax_pagination', 'handle_ajax_pagination');
