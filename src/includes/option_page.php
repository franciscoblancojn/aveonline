<?php
add_action( 'admin_menu', 'option_page_customCheckout' );
 
function option_page_customCheckout() {
	add_menu_page(
		'option_aveonline', // page <title>Title</title>
		'option_aveonline', // menu link text
		'manage_options', // capability to access the page
		'option_aveonline-slug', // page URL slug
		'admin_option_aveonline', // callback function /w content
		'dashicons-editor-textcolor', // menu icon
		5 // priority
	);
 
}
add_action( 'admin_init',  'misha_register_setting' );
 
function misha_register_setting(){
 
	register_setting(
		'option_aveonline_settings', // settings group name
		'input_option_aveonline_settings', // option name
		'sanitize_text_field' // sanitization function
	);
 
	add_settings_section(
		'option_aveonline_settings_section_id', // section ID
		'', // title (if needed)
		'', // callback function (if needed)
		'option_aveonline-slug' // page slug
	);
 
	add_settings_field(
		'input_option_aveonline_settings',
		'input_option_aveonline_settings',
		'option_aveonline_text_field_html', // function which prints the field
		'option_aveonline-slug', // page slug
		'option_aveonline_settings_section_id', // section ID
		array( 
			'label_for' => 'input_option_aveonline_settings',
			//'class' => 'misha-class', // for <tr> element
		)
    );
}
function option_aveonline_text_field_html(){
 
	$text = get_option( 'input_option_aveonline_settings' );
 
	printf(
		'<input type="text" id="input_option_aveonline_settings" name="input_option_aveonline_settings" value="%s" />',
		esc_attr( $text )
	);
 
}
function admin_option_aveonline(){
    ?> 
    <div class="wrap">
	    <h1>Aveonline</h1>
        <div id="content">
        <table class="form-table">            
            <tbody>
		        <tr class="tag_amazing">
                    <td>
                        API KEY                
                    </td>
                    <td></td>
                </tr>
            	<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="user"> 
                            Usuario
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="text" 
                        name="user" 
                        id="user" 
                        />
                    </td>
		        </tr>
				<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="password">
                            Contraseña
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="password" 
                        name="password" 
                        id="password"
                        />
                    </td>
                </tr>
                <tr class="tag_amazing">
                    <td>
                        Remitente                
                    </td>
                    <td></td>
                </tr>
            	<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="dsnitre">
                            NIT Remitente 
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="text" 
                        name="dsnitre" 
                        id="dsnitre"
                        />
                    </td>
                </tr>
				<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="dsdirre">
                            Direccion Remitente
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="text" 
                        name="dsdirre" 
                        id="dsdirre"
                        />
                    </td>
                </tr>
				<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="dstelre">
                            Teléfono Remitente 
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="tel" 
                        name="dstelre"
                        id="dstelre" 
                        />
                    </td>
                </tr>
				<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="dscelularre">
                            Celular Remitente 
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="tel" 
                        name="dscelularre" 
                        id="dscelularre"
                        />
                     </td>
                </tr>
				<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="dscorreopre">
                            Correo Remitente 
                        </label>
                    </th>
                    <td class="forminp">
                        <input 
                        class="input-text regular-input input_settings" 
                        type="email" 
                        name="dscorreopre" 
                        id="dscorreopre">
                    </td>
                </tr>
		        <tr class="tag_amazing">
                    <td>
                        Cuenta                
                    </td>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="select_cuenta">Seleccione Cuenta </label>
                    </th>
                    <td class="forminp">
                        <select 
                        class="select input_settings" 
                        name="select_cuenta" 
                        id="select_cuenta">
                            <option value="" selected="true" disabled="disabled">Seleccione Cuenta</option>
                        </select>
                        <button 
                            class="button-secondary " 
                            type="button" 
                            name="btn_select_account" 
                            id="btn_select_account" style="">
                            Cargar Cuenta                   
                        </button>
                    </td>
                </tr>
		        <tr class="tag_amazing">
                    <td>
                        Agentes                
                    </td>
                    <td></td>
                </tr>
            	<tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="select_agentes">Seleccione Agentes </label>
                    </th>
                    <td class="forminp">
                        <select 
                        class="select input_settings" 
                        name="select_agentes" 
                        id="select_agentes">
                            <option value="" selected="true" disabled="disabled">Seleccione Agente</option>
                        </select>
                        <button 
                            class="button-secondary " 
                            type="button" 
                            name="btn_select_agent" 
                            id="btn_select_agent" style="">
                            Cargar Agentes                   
                        </button>
                    </td>
                </tr>
		        <tr class="tag_amazing">
                    <td>
                        Paquetes
                    </td>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="table_package">
                            Lista de Paquetes                    
                        </label>
                    </th>
                    <td class="forminp">
                        <input type="text" id="table_package_input"  class="input_settings" hidden>
                        <button 
                        class="button-secondary " 
                        type="button" 
                        name="table_package" 
                        id="table_package_btn" style="">
                            Add package                        
                        </button>
                    </td>
                </tr>
            <tr id="table_package"></tr>
            </tbody></table>
        </div>
	    <form method="post" action="options.php">
            <?php
                settings_fields( 'option_aveonline_settings' ); 
                do_settings_sections( 'option_aveonline-slug' ); 
                submit_button();
            ?>
	    </form>
        <style>
            .misha-class{
                display:none;
            }

            .tag_amazing{
                background-color: #23282d;
                color: #fff;
                width: 100%;
                box-shadow: -50px 0 #23282d, 50px 0 #23282d;
            }
            .tag_amazing.tag_amazing *{
                font-size: 30px;
                font-weight: 700;
                color: #fff;
                padding: 5px 0;
            }
        </style>
        <script>
            input               = document.getElementById('table_package_input')
            btn                 = document.getElementById('table_package_btn')
            table               = document.getElementById('table_package')
            submit              = document.getElementById('submit')
            user                = document.getElementById('user')
            password            = document.getElementById('password')
            btn_select_agent    = document.getElementById('btn_select_agent')
            btn_select_account  = document.getElementById('btn_select_account')
            n = 0
            var data 
            function add_tr(data = null){
                cR = `
                    type="number"
                    min="1"
                    style="width: 30%;"
                    required
                ` 
                e = document.createElement("tr");
                e.id=`package_${n}`
                e.style = `
                    width: 100%;
                    min-width: 700px;
                    display: block;
                `
                e.innerHTML = `
                    <td>
                        <input 
                        id="Length_${n}"    
                        name="Length"
                        placeholder="Length"
                        ${cR}
                        ${(data!=null)?'value="'+data.length+'"':""}
                        />

                        <input 
                        id="Width_${n}"    
                        name="Width"
                        placeholder="Width"
                        ${cR}
                        ${(data!=null)?'value="'+data.width+'"':""}
                        />

                        <input 
                        id="Height_${n}"    
                        name="Height"
                        placeholder="Height"
                        ${cR}
                        ${(data!=null)?'value="'+data.height+'"':""}
                        />
                        cm
                    </td>
                    <td>
                        <button
                            id="delete_${n}"
                            id_delete="package_${n}"
                        >
                            Delete
                        </button>
                    </td>
                `
                table.appendChild(e)
                d = document.getElementById(`delete_${n}`)
                d.onclick = function(event){
                    event.preventDefault()
                    id = this.getAttribute('id_delete')
                    ele = document.getElementById(id)
                    ele.outerHTML = ""
                    sabe_table_package()
                }
                l = document.getElementById(`Length_${n}`)
                w = document.getElementById(`Width_${n}`)
                h = document.getElementById(`Height_${n}`)
                change_input(l)
                change_input(w)
                change_input(h)
                n++
            }
            function load_data(){
                if(input.value == ""){
                    input.value = "{}"
                }
                data = JSON.parse(input.value)
                for (let i = 0; i < data.length; i++) {
                    add_tr(data[i])
                }
            }
            function sabe_table_package(){
                data = []
                l = document.documentElement.querySelectorAll('[id*="Length_"]')
                w = document.documentElement.querySelectorAll('[id*="Width_"]')
                h = document.documentElement.querySelectorAll('[id*="Height_"]')
                for (let i = 0; i < l.length; i++) {
                    data[i] = {
                        length: l[i].value,
                        width: w[i].value,
                        height: h[i].value,
                    }
                }
                input.value = JSON.stringify(data)
            }
            function change_input(e){
                e.onchange = function(){
                    sabe_table_package()
                }
            }
            btn.onclick = function(){
                add_tr()
            }
            submit.onclick = function(){
                save_aveonline()
            }
            input_option_aveonline_settings  = document.getElementById('input_option_aveonline_settings')
            function save_aveonline() {
                value = {}
                input_settings = document.documentElement.querySelectorAll('.input_settings')
                for ( i = 0; i < input_settings.length; i++) {
                    e = input_settings[i];
                    v = e.value
                    if(e.id == "table_package_input"){
                        v = JSON.parse(v)
                    }
                    value[e.id] = v
                }
                input_option_aveonline_settings.value = JSON.stringify(value)
            }
            function load_aveonline(){
                value = input_option_aveonline_settings.value
                if(value == "") value = "{}"
                value = JSON.parse(value)
                for ([key, v] of Object.entries(value)) {
                    e = document.getElementById(key)
                    if(e!=null && e!=undefined){
                        if(typeof v == "object"){
                            v = JSON.stringify(v)
                            console.log(v)
                        }
                        e.value = v
                    }
                }
            }
            load_aveonline()
            load_data()

            btn_select_account.onclick = function(){
                //validations
                if(user.value == "" || password.value ==""){
                    alert("Usuario y Clave requerido")
                    return
                }
            }
            btn_select_agent.onclick = function(){
                
            }
        </script>
    </div>
    <?php
}