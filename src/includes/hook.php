<?php

function AVSHME_add_JS_CSS_footer() {
    ?>
    <style>
        body:not(.wc_contraentrega_on) [id*="wc_contraentrega_on"],
        body:not(.wc_contraentrega_on) [id*="wc_contraentrega_on"] + label{
            display: none !important;
        }
        body.wc_contraentrega_on [id*="wc_contraentrega_off"],
        body.wc_contraentrega_on [id*="wc_contraentrega_off"] + label{
            display: none !important;
        }
    </style>
    <script>
        function load_img(){
            shipping_method = document.documentElement.querySelectorAll('[for*="shipping_method"], [class*="shipped"]')
            for(var i = 0 ; i < shipping_method.length ; i++ ){
                if( shipping_method[i].innerText.indexOf('url_img') > -1 ){
                    url = shipping_method[i].innerText.split('url_img')[1]
                    shipping_method[i].innerHTML = shipping_method[i].innerHTML.split('url_img' + url + 'url_img').join("")
                    shipping_method[i].innerHTML = `
                        <img src="${url}" width="30"/>
                    ` +shipping_method[i].innerHTML 
                }
            }
        }
        load_img()
        function contraentrega_change(_checked) {
            if(_checked){
                document.body.classList.add('wc_contraentrega_on')
            }else{
                document.body.classList.remove('wc_contraentrega_on')
            }
            e = document.documentElement.querySelector('.shipping_method:checked')
            
            id = ""
            if(!_checked){
                id = e.id.replace("wc_contraentrega_on","wc_contraentrega_off")
                r = document.documentElement.querySelectorAll('[id*="wc_contraentrega_off"]')[0]
            }else{
                id = e.id.replace("wc_contraentrega_off","wc_contraentrega_on")
                r = document.documentElement.querySelectorAll('[id*="wc_contraentrega_on"]')[0]
            }
            p = document.getElementById(id)
            if(p == null || p == undefined){
                if(r != null & r != undefined)
                    r.click()
            }else{
                p.click()
            }
        }
        function init_WC_contraentrega() {
            payment_method = document.getElementsByName('payment_method')
            for (var i = 0; i < payment_method.length; i++) {
                payment_method[i].onchange = (e) => contraentrega_change(e.target.id == "payment_method_Contraentrega");
            }
        }
        init_WC_contraentrega()
        jQuery(document.body).on('updated_checkout', function () {
            load_img()
            init_WC_contraentrega()
        });
        
        contraentrega_payment = document.getElementById('payment_method_Contraentrega')
        if(contraentrega_payment!=null && contraentrega_payment!=undefined)
            contraentrega_change(contraentrega_payment.checked)
    </script>
    <?php
}
add_action( 'wp_footer', 'AVSHME_add_JS_CSS_footer' );