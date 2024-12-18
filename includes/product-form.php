<?php
if (!defined('ABSPATH')) exit; ?>


<?php
function cpm_display_product_form()
{

    if (!current_user_can('manage_woocommerce')) {
        echo "<p>You do not have permission to manage products.</p>";
        return;
    }
?>


    <?php
    $product_id = isset($_GET['edit_product']) ? intval($_GET['edit_product']) : 0;
    $product = $product_id ? wc_get_product($product_id) : null;

    ?>
    <form id="cpm-product-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('cpm_save_product', 'cpm_product_nonce'); ?>

        <input type="hidden" id="product_id" name="product_id" value="<?php echo $product_id; ?>">

        <label for="product_name">Product Name</label>
        <input type="text" id="product_name" name="product_name" value="<?php echo $product ? esc_attr($product->get_name()) : ''; ?>" required>

        <label for="product_price">Product Regular Price</label>
        <input type="number" id="product_regular_price" step="0.01" min="0" max="99999999" name="product_regular_price"
            value="<?php echo $product ? esc_attr($product->get_regular_price()) : ''; ?>" required>

        <label for="product_price">Sale Price</label>
        <input type="number" id="product_sale_price" step="0.01" min="0" max="99999999" name="product_sale_price"
            value="<?php echo $product ? esc_attr($product->get_sale_price()) : ''; ?>" required>

        <label for="product_description">Description</label>
        <?php
        wp_editor(
            $product ? $product->get_description() : '',
            'product_description',
            array('textarea_name' => 'product_description', 'media_buttons' => true)
        );
        ?>

        <label for="product_category">Category</label>
        <select id="product_category" name="product_category">
            <?php
            $categories = get_terms('product_cat', array('hide_empty' => false));
            function cpm_display_category_hierarchy($categories, $product, $parent_id = 0, $level = 0)
            {
                foreach ($categories as $category) {
                    if ($category->parent == $parent_id) {
                        $selected = $product && has_term($category->term_id, 'product_cat', $product->get_id()) ? 'selected' : '';
                        $indent = str_repeat('&nbsp;&nbsp;', $level);

                        echo '<option value="' . esc_attr($category->term_id) . '"' . $selected . '>';
                        echo $indent . esc_html($category->name);
                        echo '</option>';

                        cpm_display_category_hierarchy($categories, $product, $category->term_id, $level + 1);
                    }
                }
            }
            cpm_display_category_hierarchy($categories, $product);
            ?>
        </select>

        <label for="stock_status">Stock Status</label>
        <select id="stock_status" name="stock_status">
            <option value="instock" <?php echo $product && $product->get_stock_status() === 'instock' ? 'selected' : ''; ?>>In Stock</option>
            <option value="outofstock" <?php echo $product && $product->get_stock_status() === 'outofstock' ? 'selected' : ''; ?>>Out of Stock</option>
        </select>

        <label for="product_image">Product Image</label>
        <input type="file" id="product_image" name="product_image" icon="upload">

        <div id="image-preview">
            <?php
            if ($product && has_post_thumbnail($product_id)) {
                echo '<img src="' . esc_url(get_the_post_thumbnail_url($product_id, 'medium')) . '" alt="Current Product Image">';
            } else {
                echo '<img src="#" alt="Product Image" style="display: none;">';
            }
            ?>
        </div>

        <button type="submit" id="cpm-save-product">
            <?php
            if (isset($product) && !empty($product)) {
                echo 'Update Product';
            } else {
                echo 'Add Product';
            }
            ?>
        </button>

    </form>
    <div id="cpm-message"></div>

    <?php
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'orderby'  => 'name',
        'hide_empty' => false,
    ]);

    if ($categories && !is_wp_error($categories)) {
        echo '<div class="right-element"><select id="category-filter" name="category_filter">';
        echo '<option value="">Select Category</option>';
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
        }
        echo '</select></div>';
    }

    ?>


<?php
}
