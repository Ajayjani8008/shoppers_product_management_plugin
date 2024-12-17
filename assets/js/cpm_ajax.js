jQuery(document).ready(function ($) {

    function resetForm() {
        $('#cpm-product-form')[0].reset();
        $('#product_id').val('');
        $('#image-preview img').attr('src', '#').hide();
        tinymce.get('product_description').setContent('');
    }

    $('#cpm-product-form').on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('action', 'cpm_save_product');
        formData.append('nonce', cpm_ajax.nonce);

        $.ajax({
            url: cpm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                const messageDiv = $('#cpm-message');
                if (response.success) {
                    messageDiv.text(response.data.message).removeClass('error').addClass('success').fadeIn();
                    $('#cpm-product-list').html(response.data.product_list);
                    resetForm();
                    jQuery('#cpm-save-product').text('Add Product');
                } else {
                    messageDiv.text(response.data.message).removeClass('success').addClass('error').fadeIn();
                }
                setTimeout(() => messageDiv.fadeOut(), 2000);
            },
            error: function () {
                const messageDiv = $('#cpm-message');
                messageDiv.text('Something went wrong. Please try again.')
                    .removeClass('success')
                    .addClass('error')
                    .fadeIn();
                setTimeout(() => messageDiv.fadeOut(), 2000);
            }
        });
    });


    $(document).on('click', '.cpm-delete-product', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this product?')) return;

        let productId = $(this).data('product-id');
        let row = $(this).closest('tr');

        $.ajax({
            url: cpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cpm_delete_product',
                nonce: cpm_ajax.nonce,
                product_id: productId,
            },
            success: function (response) {
                const messageDiv = $('#cpm-message');
                if (response.success) {
                    messageDiv.text(response.data.message).removeClass('error').addClass('success').fadeIn();
                    row.remove();
                } else {
                    messageDiv.text(response.data.message).removeClass('success').addClass('error').fadeIn();
                }
                setTimeout(() => messageDiv.fadeOut(), 2000);
            },
            error: function () {
                const messageDiv = $('#cpm-message');
                messageDiv.text('Something went wrong. Please try again.')
                    .removeClass('success')
                    .addClass('error')
                    .fadeIn();
                setTimeout(() => messageDiv.fadeOut(), 2000);
            }
        });
    });

    $(document).on('click', '.cpm-edit-product', function (e) {
        e.preventDefault();

        let productId = $(this).data('product-id');

        $.ajax({
            url: cpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cpm_fetch_product',
                nonce: cpm_ajax.nonce,
                product_id: productId,
            },
            success: function (response) {
                if (response.success) {
                    const product = response.data.product;

                    if (product) {
                        jQuery('#cpm-save-product').text('Update Product');
                    } else {
                        jQuery('#cpm-save-product').text('Add Product');
                    }
                    $('#product_id').val(product.id);
                    $('#product_name').val(product.name);
                    $('#product_regular_price').val(product.regular_price);
                    $('#product_sale_price').val(product.sale_price);
                    $('#product_category').val(product.category);
                    $('#stock_status').val(product.stock_status);
                    $('#image-preview img').attr('src', product.image).show();
                    tinymce.get('product_description').setContent(product.description);
                    $('html, body').animate({ scrollTop: $('#cpm-product-form').offset().top }, 'slow');
                } else {
                    alert(response.data.message || 'Failed to fetch product data.');
                }
            },
            error: function () {
                alert('Something went wrong. Please try again.');
            }
        });
    });


    $('.cpm-add-new').on('click', function (e) {
        e.preventDefault();
        resetForm();
        $('html, body').animate({ scrollTop: $('#cpm-product-form').offset().top }, 'slow');
    });


    $('#category-filter').on('change', function () {
        const categoryId = $(this).val();
        const nonce = cpm_ajax.nonce;
        const page = 1; 

        $.ajax({
            url: cpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'handle_ajax_pagination',
                paged: page,
                category_id: categoryId,
                nonce: nonce
            },
            beforeSend: function () {
                $('#cpm-product-list-table tbody').html('<tr><td colspan="7">Loading...</td></tr>');
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.content.trim() === "") {
                        $('#cpm-product-list-table tbody').html('<tr><td colspan="7">No Product Found</td></tr>');
                    } else {
                        $('#cpm-product-list-table tbody').html(response.data.content);
                        $('.pagination').html(response.data.pagination);
                    }

                } else {
                    alert('Failed to load products.');
                }
            },
            error: function () {
                $('#cpm-product-list-table tbody').html('<tr><td colspan="7">Error loading products.</td></tr>');
            }
        });
    });


    jQuery(document).on('click', '.ajax-pagination a', function (e) {
        e.preventDefault();

        const page = jQuery(this).data('page');
        const nonce = cpm_ajax.nonce;

        jQuery.ajax({
            url: cpm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'handle_ajax_pagination',
                paged: page,
                nonce: nonce
            },
            beforeSend: function () {
                jQuery('#cpm-product-list-table tbody').html('<tr><td colspan="7">Loading...</td></tr>');
            },
            success: function (response) {
                if (response.success) {

                    jQuery('#cpm-product-list-table tbody').html(response.data.content);

                    jQuery('.pagination').html(response.data.pagination);
                } else {
                    alert('Failed to load content.');
                }
            },
            error: function () {
                jQuery('#cpm-product-list-table tbody').html('<tr><td colspan="7">Error loading products.</td></tr>');
            }
        });
    });


});