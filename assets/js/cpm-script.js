jQuery(document).ready(function ($) {
    $('#product_image').on('change', function () {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#image-preview img').attr('src', e.target.result).show();
        };

        if (this.files && this.files[0]) {
            reader.readAsDataURL(this.files[0]);
        }
    });
});
