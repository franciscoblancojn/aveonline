<?php
function add_recogida_aveonline_option_page( $admin_bar ){
    global $pagenow;
    $admin_bar->add_menu( 
        array( 
            'id'=>'recogida_aveonline',
            'title'=>'Recogidas Aveonline',
            'href'=>'/wp-admin/options-general.php?page=recogida_aveonline' 
        ) 
    );
}

function recogida_aveonline_option_page(){
    add_options_page( 
        'Recogida Aveonline', 
        'Recogidas Aveonline',
        'manage_options',
        'recogida_aveonline', 
        'recogida_aveonline_page');
}

function recogida_aveonline_page(){
    $customer_orders = wc_get_orders( array(
        'numberposts' => -1,
        'return' => 'ids',
        'status' => 'processing',
    ) );
    ?>
    <h2 class="screen-reader-text">Orders</h2>
    <script>
        function refes_order(order_id , result){
            if(result == null || result == "error"){
                alert('Error')
                return
            }
            order =  document.getElementById(`post-${order_id}`)
            order.outerHTML = result
        }
        function generar_recogida(e){
            order_id = e.getAttribute('order_id')
            var myHeaders = new Headers();
            myHeaders.append("Cookie", "__cfduid=d23155ce328a4759efd2b35fde15da2211600376510");

            var formdata = new FormData();
            formdata.append("order_id", order_id);
            formdata.append("generar_recogida", 1);

            var requestOptions = {
                method: 'POST',
                headers: myHeaders,
                body: formdata,
                redirect: 'follow'
            };

            fetch("<?=plugin_dir_url( __FILE__ )?>class-aveonline-recogida.php", requestOptions)
            .then(response => response.text())
            .then(result => refes_order(order_id , result))
            .catch(error => console.log('error', error));
        }
        function generar_multiple(){
            select = document.documentElement.querySelectorAll("[id*='cb-select']:not([id='cb-select-all-1']):checked")
            for (let i = 0; i < select.length; i++) {
                e = select[i];
                generar_recogida(e)
            }
        }
    </script>

    <div class="wp-core-ui" >
        <p>
            <button 
            onclick="generar_multiple()"
            class="button">
                Generar Recogidas Seleccionadas
            </button> 
        </p>
    </div>
    <table class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column" data-children-count="1">
                    <label class="screen-reader-text" for="cb-select-all-1">Select All
                    </label>
                    <input id="cb-select-all-1"type="checkbox">
                </td>
                <th scope="col" id="order" class="manage-column column-order column-primary">Orden</th>
                <th scope="col" id="guia" class="manage-column column-guia">Guia</th>
                <th scope="col" id="rotulo" class="manage-column column-rotulo">Rotulo</th>
                <th scope="col" id="estado" class="manage-column column-estado">Estado</th>
                <th scope="col" id="date" class="manage-column column-date">Fecha</th>
                <th scope="col" id="recogida" class="manage-column column-recogida">Generar Recogida</th>
            </tr>
        </thead>

        <tbody id="the-list">
            <?php
            for ($i=0; $i < count($customer_orders) ; $i++) { 
                show_order_by_table_recogida($customer_orders[$i]);
            }
            ?>
        </tbody>
    </table>
    <?php
}

function show_order_by_table_recogida($order_id){
    if ($order_id == null )  return;

    $order = wc_get_order( $order_id );
    if ($order == null )  return;

    $e = get_post_meta( $order_id, 'order_pdf', true );
    if (! $e )  return;
    if(count($e) <1 ) return;

    $solicitar_recogida = get_post_meta( $order_id, 'solicitar_recogida', true );
    if (! $solicitar_recogida )  return;

    $estado_recogida = get_post_meta( $order_id, 'estado_recogida', true );

    ?>
    <tr id="post-<?=$order_id?>"
        class="<?=($estado_recogida == null)?"no_generado_recogida":""?>
        iedit author-self level-0 post-<?=$order_id?> type-post status-publish format-standard hentry category-uncategorized">
        <th scope="row" class="check-column" data-children-count="1">
            <label class="screen-reader-text" for="cb-select-<?=$order_id?>"></label>
            <input 
            id="cb-select-<?=$order_id?>" 
            order_id="<?=$order_id?>"
            type="checkbox" 
            name="post[]" 
            value="<?=$order_id?>"
            >
            <div class="locked-indicator">
                <span class="locked-indicator-icon" aria-hidden="true"></span>
                <span class="screen-reader-text"></span>
            </div>
        </th>
        <td class="title column-title has-row-actions column-primary page-order" data-colname="Title">
            <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
            <strong>
                <a class="row-title"
                    href="/wp-admin/post.php?post=<?=$order_id?>&action=edit">
                    #<?=$order_id?>
                </a>
            </strong>
        </td>
       
        <td class="author column-guia" data-colname="Guia">
            <?php
                for ($i=0; $i < count($e); $i++) { 
                    echo $e[$i]['guias'].'<br>';
                }
            ?>
        </td>
        <td class="author column-rotulo" data-colname="Rotulo">
            <?php
                for ($i=0; $i < count($e); $i++) { 
                    echo $e[$i]['rotulos'].'<br>';
                }
            ?>
        </td>
        <td class="author column-estado" data-colname="Estado">
            <?php
                if($estado_recogida == null){
                    echo "No generada";
                }else{
                    echo $estado_recogida;
                }
            ?>
        </td>
        <td class="date column-date" data-colname="Date">
            <span><?=$order->get_date_created()->format ('d-m-y');?></span>
        </td>
        <td class="author column-recogida wp-core-ui" data-colname="Recogida">
            <?php
                if($estado_recogida == null){
                    ?>
                    <p>
                        <button 
                        order_id="<?=$order_id?>"
                        onclick="generar_recogida(this)"
                        class="button">
                            Generar
                        </button> 
                    </p>
                    <?php
                }
            ?>
        </td>
    </tr>
    <?php
}

if(isset($_POST) && isset($_POST['generar_recogida'])){
    require_once '../../../../../wp-blog-header.php';
    $order_id = $_POST['order_id'];

    $solicitar_recogida = get_post_meta( $order_id, 'solicitar_recogida', true );
    if($solicitar_recogida == null){
        show_order_by_table_recogida($order_id);
        exit;
    }
    $solicitar_recogida = json_decode(base64_decode($solicitar_recogida));

    $token = $solicitar_recogida->token;
    $token = json_decode(base64_decode($token));

    require_once './class-aveonline-api.php';
    $api = new AveonlineAPI(array(),false);
    $token = $api->get_token(array(
        'user'      => $token->user,
        'password'  => $token->password,
    ));

    $solicitar_recogida->token = $token;
    $solicitar_recogida = json_encode($solicitar_recogida);

    $recogida = $api->solicitar_recogida($solicitar_recogida);
    if($recogida->status == "ok"){
        update_post_meta( $order_id, 'estado_recogida', null );
        show_order_by_table_recogida($order_id);
    }else{
        echo "error";
    }
    exit;
}

add_action('admin_bar_menu', 'add_recogida_aveonline_option_page', 100);

add_action('admin_menu', 'recogida_aveonline_option_page'); 