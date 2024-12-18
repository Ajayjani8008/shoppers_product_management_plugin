<?php
if (!defined('ABSPATH')) exit;

function cpm_display_product_list()
{
    if (!current_user_can('manage_woocommerce')) {
        echo "<p>You do not have permission to manage products.</p>";
        return;
    }

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $posts_per_page = 10;


    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),

    );

    $products = new WP_Query($args);

    if ($products->have_posts()) {
?>


        <div id="cpm-product-list">
            <div class="table-container">
                <table id="cpm-product-list-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>RegularPrice</th>
                            <th>Sale Price</th>
                            <th>Tags</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <div class="ajax-pagination">
                    <?php
                    $total_pages = $products->max_num_pages ?? 1;
                    $paged = 1;
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
                    ?>
                </div>
            </div>
        </div>

    <?php
    } else {
        echo "<p>No products found.</p>";
    }

    ?>



<?php

    wp_reset_postdata();
}
?>