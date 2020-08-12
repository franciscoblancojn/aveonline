function init_WC_contraentrega() {
    WC_contraentrega = document.getElementById('payment_method_WC_contraentrega')
    if (WC_contraentrega == null) return;

    WC_contraentrega.rebug = function () {
        console.log(this.checked);
        if (this.checked) {
            document.body.classList.add('wc_contraentrega_on')
        } else {
            document.body.classList.remove('wc_contraentrega_on')
        }
        active = document.documentElement.querySelector('.shipping_method[checked="checked"][id*="wc_contraentrega"]')
        if (active == null) return;
        if (this.checked) {
            document.getElementById(active.id.split("wc_contraentrega_off").join("wc_contraentrega_on")).click()
        } else {
            document.getElementById(active.id.split("wc_contraentrega_on").join("wc_contraentrega_off")).click()
        }
    }
    WC_contraentrega.rebug()

    wc_payment_method = document.documentElement.querySelectorAll('.wc_payment_method')
    for (var i = 0; i < wc_payment_method.length; i++) {
        wc_payment_method[i].onclick = function () {
            WC_contraentrega.rebug()
        }
    }
    order_review = document.getElementById('order_review')
    order_review.onchage = function () {
        init_WC_contraentrega()
    }
}

window.onload = function () {
    init_WC_contraentrega()
    const panoptico = new MutationObserver((mutaciones, observer) => init_WC_contraentrega());
    panoptico.observe(document.getElementById('order_review'), { attributes: true, childList: true, subtree: true })

}