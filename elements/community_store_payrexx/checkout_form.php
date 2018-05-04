<?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>

<script>
    $(window).on('load', function () {

        $(".store-btn-complete-order").click(function (e) {
            e.preventDefault();

            var form = $('#store-checkout-form-group-payment');
            var submitButton = form.find("[data-payment-method-id=\"<?= $pmID; ?>\"] .store-btn-complete-order");
            submitButton.prop('disabled', true);

            $('#store-checkout-form-group-payment').submit();
        });

    });
</script>
