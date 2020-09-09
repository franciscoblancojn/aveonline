function contraentrega_change(_checked) {
    checked = _checked ? 1 : 0
    jQuery.ajax({
        type: 'POST',
        url: wc_checkout_params.ajax_url,
        data: {
            'action': 'set_contraentrega',
            'contraentrega': checked,
        },
        success: function (response) {
            console.log('ok', response)
            jQuery(document.body).trigger("update_checkout")
        },
        error: function (error) {
            console.log('error', error);
        }
    });
}
function init_WC_contraentrega() {
    payment_method = document.getElementsByName('payment_method')
    for (var i = 0; i < payment_method.length; i++) {
        payment_method[i].onchange = (e) => contraentrega_change(e.target.id == "payment_method_WC_contraentrega");
    }
}
window.onload = function () {
    init_WC_contraentrega()
    jQuery(document.body).on('updated_checkout', function () {
        init_WC_contraentrega()
    });
    contraentrega_payment = document.getElementById('payment_method_WC_contraentrega')
    contraentrega_change(contraentrega_payment.checked)
}