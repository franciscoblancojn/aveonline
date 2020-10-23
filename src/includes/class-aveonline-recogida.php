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
    $rd_args_total = array(
        'meta_key'      => 'solicitar_recogida',
        'meta_compare'  => 'EXISTS',
        'return'        => 'ids',
        'status'        => 'processing',
    );
    $customer_orders_total = wc_get_orders( $rd_args_total );

    $paged = (isset($_GET['paged']))?intval($_GET['paged']):1;
    $n_page = 10;
    $rd_args = array(
        'meta_key'      => 'solicitar_recogida',
        'meta_compare'  => 'EXISTS',
        'return'        => 'ids',
        'status'        => 'processing',

        'nopaging'                  => false,
        'paged'                     => '1',
        'posts_per_page'            => $n_page,
        'posts_per_archive_page'    => $n_page,
        'offset'                    => $n_page *($paged - 1),
    );
    $customer_orders = wc_get_orders( $rd_args );
    ?>
    <h2 class="screen-reader-text">Orders</h2>
    <script>
        function validarFechaMenorActual(date){
            var fecha = date.split("-");
            var ndate = new Date(date);
            var today = new Date();
            val_x       = (parseInt(fecha[0]))+(parseInt(fecha[1])/100)+(parseInt(fecha[2])/10000)
            val_today   = (today.getFullYear())+((today.getMonth()+1)/100)+(today.getDate()/10000)
            if(ndate.getDay() == 6){
                alert("No se permite el domingo")
                return false;
            }else if (val_today > val_x){
                alert("Fecha inferiror al actual")
                return false;
            }else if(ndate.getDay() == 5 && val_today == val_x){
                alert("Para solicitar recogida los sabados, Debes solicitarla mas tarar el Viener")
                return false;
            }else if(today.getHours() > 12 && val_today == val_x){
                alert("Despues de las 12pm no se puede solicitar recogida el mismo dia")
                return false;
            }
            return true;
        }
        function validate_fecha(){
            fecha_recogida = document.getElementById('fecha_recogida')
            
            if(fecha_recogida.value == ""){
                alert("Ingrese fecha de recogida");
                return false;
            }
            if(!validarFechaMenorActual(fecha_recogida.value)){
                return false;
            }

            return true;
        }
        function refes_order(order_id , result){
            if(result == null || result == "error"){
                alert('Error')
                return
            }
            if(result ==  "no change"){
                console.log(order_id , result)
                return
            }
            order =  document.getElementById(`post-${order_id}`)
            order.outerHTML = result
        }
        async function generar_recogida(e){
            if(!validate_fecha())return;
            order_id = e.getAttribute('order_id')
            var myHeaders = new Headers();
            myHeaders.append("Cookie", "__cfduid=d23155ce328a4759efd2b35fde15da2211600376510");

            var formdata = new FormData();
            
            fecha_recogida = document.getElementById('fecha_recogida')
            notas = document.getElementById('notas')
            formdata.append("order_id", order_id);
            formdata.append("generar_recogida", 1);
            formdata.append("fecha_recogida", fecha_recogida.value);
            formdata.append("notas", notas.value);

            var requestOptions = {
                method: 'POST',
                headers: myHeaders,
                body: formdata,
                redirect: 'follow'
            };

            await fetch("<?=plugin_dir_url( __FILE__ )?>class-aveonline-recogida.php", requestOptions)
            .then(response => response.text())
            .then(result => refes_order(order_id , result))
            .catch(error => console.log('error', error));
        }
        async function generar_multiple(){
            if(!validate_fecha())return;
            select = document.documentElement.querySelectorAll("[id*='cb-select']:not([id='cb-select-all-1']):checked")
            ids = []
            for (let i = 0; i < select.length; i++) {
                e = select[i];
                ids[i] = e.getAttribute('order_id')
            }

            var myHeaders = new Headers();
            myHeaders.append("Cookie", "__cfduid=d23155ce328a4759efd2b35fde15da2211600376510");

            var formdata = new FormData();
            
            fecha_recogida = document.getElementById('fecha_recogida')
            notas = document.getElementById('notas')
            formdata.append("order_ids", ids);
            formdata.append("generar_recogida_multiple", 1);
            formdata.append("fecha_recogida", fecha_recogida.value);
            formdata.append("notas", notas.value);

            var requestOptions = {
                method: 'POST',
                headers: myHeaders,
                body: formdata,
                redirect: 'follow'
            };

            await fetch("<?=plugin_dir_url( __FILE__ )?>class-aveonline-recogida.php", requestOptions)
            .then(response => response.text())
            .then(result => window.location.reload())
            .catch(error => console.log('error', error));
        }
    </script>
    <div class="wp-core-ui" >
        <p>
            <button 
            onclick="generar_multiple()"
            class="button">
                Generar Recogidas Seleccionadas
            </button> 
            <label for="">
                Fecha de recogida
                <input type="date" id="fecha_recogida" name="fecha_recogida">
            </label>
            <label for="">
                Notas de Recogida
                <input type="text" name="notas" id="notas" />
            </label>
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
                <th scope="col" id="date" class="manage-column column-date">Paquete</th>
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
    <div class="tablenav bottom">
        <div class="alignleft actions">
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num"><?=count($customer_orders_total)?> items</span>
            <span class="pagination-links">
                <?php
                    $url_base = "/wp-admin/options-general.php?page=recogida_aveonline&paged=";
                    $url_void = "javascript:void(0)";
                    $paged_all = ceil(count($customer_orders_total)/$n_page);

                    $next = ($paged-1 > 0)?$paged-1:1;
                    $prev = ($paged+1 < $paged_all)?$paged+1:$paged_all;

                    $url_base_first = $url_base."1";
                    $url_base_prev  = $url_base.$next;
                    $url_base_next  = $url_base.$prev;
                    $url_base_last  = $url_base.$paged_all;

                    if($paged == 1){
                        $url_base_first = $url_void;
                        $url_base_prev  = $url_void;
                    }
                    if($paged == $paged_all){
                        $url_base_next  = $url_void;
                        $url_base_last  = $url_void;
                    }
                    
                ?>
                <a class="first-page button <?=($paged == 1)?"disabled":"";?>" href="<?=$url_base_first?>">
                    <span class="screen-reader-text">First page</span><span aria-hidden="true">«</span>
                </a>
                <a class="prev-page button <?=($paged == 1)?"disabled":"";?>" href="<?=$url_base_prev?>">
                    <span class="screen-reader-text">Prev page</span><span aria-hidden="true">‹</span>
                </a>
                <span class="screen-reader-text">Current Page</span>
                <span id="table-paging" class="paging-input">
                    <span class="tablenav-paging-text">
                        <?=$paged?> of <span class="total-pages"><?=$paged_all?></span>
                    </span>
                </span>
                <a class="next-page button <?=($paged == $paged_all)?"disabled":"";?>" href="<?=$url_base_next?>">
                    <span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span>
                </a>
                <a class="last-page button <?=($paged == $paged_all)?"disabled":"";?>" href="<?=$url_base_last?>">
                    <span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span>
                </a>
            </span>
        </div>
        <br class="clear">
    </div>
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
        <td class="date column-paquete" data-colname="paquete">
            <?php
            $paquete = get_post_meta( $order_id, 'paquete_final', true );
            if($paquete!=null){
                echo $paquete->length."x".$paquete->width."x".$paquete->height;
                echo "<br>";
                echo "#N: ".$paquete->numeroPaquetes;
            }
            ?>
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

    $estado_recogida = get_post_meta( $order_id, 'estado_recogida', true );
    $solicitar_recogida = get_post_meta( $order_id, 'solicitar_recogida', true );
    if("Generada" == $estado_recogida || $solicitar_recogida == null){
        echo "no change";
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
    $solicitar_recogida->fecharecogida = $_POST['fecha_recogida'];
    $solicitar_recogida = json_encode($solicitar_recogida);
    // var_dump($solicitar_recogida);
    // exit;
    $recogida = $api->solicitar_recogida($solicitar_recogida);
    if($recogida->status == "ok"){
        update_post_meta( $order_id, 'estado_recogida', "Generada" );
        //update_post_meta( $order_id, 'estado_recogida', null );
        show_order_by_table_recogida($order_id);
    }else{
        echo "error";
    }
    exit;
}

if(isset($_POST) && isset($_POST['generar_recogida_multiple'])){
    require_once '../../../../../wp-blog-header.php';
    $order_ids = $_POST['order_ids'];
    $order_ids = explode("," , $order_ids);
    $order_ids_final = [];

    $unidades = 0;
    $kilos = 0;
    $valordeclarado = 0;
    $dscom = "";

    $solicitar_recogida_init = null;
    for ($i=0; $i < count($order_ids); $i++) { 
        $order_id = $order_ids[$i];
        $estado_recogida = get_post_meta( $order_id, 'estado_recogida', true );
        $solicitar_recogida = get_post_meta( $order_id, 'solicitar_recogida', true );
        if("Generada" == $estado_recogida || $solicitar_recogida == null){
            echo "no change";
        }else{
            $solicitar_recogida = json_decode(base64_decode($solicitar_recogida));
            $order_ids_final[] = $order_id;
            if($solicitar_recogida_init == null){
                $solicitar_recogida_init = $solicitar_recogida;
            }
            $unidades           += intval($solicitar_recogida->unidades);
            $kilos              += floatval($solicitar_recogida->kilos);
            $valordeclarado     += floatval($solicitar_recogida->valordeclarado);
            $dscom              .= $solicitar_recogida->dscom."--";
        }
    }
    $token = $solicitar_recogida_init->token;
    $token = json_decode(base64_decode($token));

    require_once './class-aveonline-api.php';
    $api = new AveonlineAPI(array(),false);
    $token = $api->get_token(array(
        'user'      => $token->user,
        'password'  => $token->password,
    ));

    $solicitar_recogida_init->token = $token;
    $solicitar_recogida_init->fecharecogida = $_POST['fecha_recogida'];
    $solicitar_recogida_init->unidades = $unidades;
    $solicitar_recogida_init->kilos = $kilos;
    $solicitar_recogida_init->valordeclarado = $valordeclarado;
    $solicitar_recogida_init->dscom = $_POST['notas'];
    $tipoenvio = 3;
    if($kilos == 1 && $unidades == 1){
        $tipoenvio = 1;
    }else if($kilos <=8 && $unidades <= 10){
        $tipoenvio = 2;
    }
    

    $solicitar_recogida_init->tipoenvio = $tipoenvio;
    
    $solicitar_recogida_init = json_encode($solicitar_recogida_init);
    // var_dump($solicitar_recogida);
    // exit;
    $recogida = $api->solicitar_recogida($solicitar_recogida_init);
    if($recogida->status == "ok"){
        for ($i=0; $i < count($order_ids_final); $i++) { 
            update_post_meta( $order_ids_final[$i], 'estado_recogida', "Generada" );
        }
        //update_post_meta( $order_id, 'estado_recogida', null );
        //show_order_by_table_recogida($order_id);
    }else{
        echo "error";
    }
    exit;
}
add_action('admin_bar_menu', 'add_recogida_aveonline_option_page', 100);

add_action('admin_menu', 'recogida_aveonline_option_page'); 