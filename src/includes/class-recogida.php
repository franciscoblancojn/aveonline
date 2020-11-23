<?php
function AVSHME_add_recogida_aveonline_option_page($admin_bar)
{
    global $pagenow;
    $admin_bar->add_menu(
        array(
            'id' => 'recogida_aveonline',
            'title' => 'Recogidas Aveonline',
            'href' => get_site_url().'/wp-admin/options-general.php?page=recogida_aveonline'
        )
    );
}

function AVSHME_recogida_aveonline_option_page()
{
    add_options_page(
        'Recogida Aveonline',
        'Recogidas Aveonline',
        'manage_options',
        'recogida_aveonline',
        'AVSHME_recogida_aveonline_page'
    );
}

function AVSHME_recogida_aveonline_page()
{
    $rd_args_total = array(
        'meta_key'      => 'enable_recogida',
        'meta_compare'  => 'EXISTS',
        'return'        => 'ids',
        'status'        => 'processing',
    );
    $customer_orders_total = wc_get_orders($rd_args_total);

    $paged = (isset($_GET['paged'])) ? intval($_GET['paged']) : 1;
    $n_page = 10;
    $rd_args = array(
        'meta_key'      => 'enable_recogida',
        'meta_compare'  => 'EXISTS',
        'return'        => 'ids',
        'status'        => 'processing',

        'nopaging'                  => false,
        'paged'                     => '1',
        'posts_per_page'            => $n_page,
        'posts_per_archive_page'    => $n_page,
        'offset'                    => $n_page * ($paged - 1),
    );
    $customer_orders = wc_get_orders($rd_args);
    //pre($customer_orders_total);
    ?>
    <h2 class="screen-reader-text">Orders</h2>
    <script>
        function validarFechaMenorActual(date) {
            var fecha = date.split("-");
            var ndate = new Date(date);
            var today = new Date();
            val_x = (parseInt(fecha[0])) + (parseInt(fecha[1]) / 100) + (parseInt(fecha[2]) / 10000)
            val_today = (today.getFullYear()) + ((today.getMonth() + 1) / 100) + (today.getDate() / 10000)
            if (ndate.getDay() == 6) {
                alert("No se permite el domingo")
                return false;
            } else if (val_today > val_x) {
                alert("Fecha inferiror al actual")
                return false;
            } else if (ndate.getDay() == 5 && val_today == val_x) {
                alert("Para solicitar recogida los sabados, Debes solicitarla mas tarar el Viener")
                return false;
            } else if (today.getHours() > 12 && val_today == val_x) {
                alert("Despues de las 12pm no se puede solicitar recogida el mismo dia")
                return false;
            }
            return true;
        }

        function validate_fecha() {
            fecha_recogida = document.getElementById('fecha_recogida')

            if (fecha_recogida.value == "") {
                alert("Ingrese fecha de recogida");
                return false;
            }
            if (!validarFechaMenorActual(fecha_recogida.value)) {
                return false;
            }

            return true;
        }

        function refes_order(order_id, result) {
            if (result == null || result == "error") {
                alert('Error')
                return
            }
            if (result == "no change") {
                console.log(order_id, result)
                return
            }
            order = document.getElementById(`post-${order_id}`)
            order.outerHTML = result
        }
        async function generar_recogida(e) {
            if (!validate_fecha()) return;
            order_id = e.getAttribute('order_id')
            var myHeaders = new Headers();
            myHeaders.append("Cookie", "__cfduid=d23155ce328a4759efd2b35fde15da2211600376510");

            var formdata = new FormData();

            fecha_recogida = document.getElementById('fecha_recogida')
            notas = document.getElementById('notas')
            formdata.append("order_id", order_id);
            formdata.append("generar_recogida", 1);
            formdata.append("fecha_recogida", fecha_recogida.value.split('-').join('/'));
            formdata.append("notas", notas.value);

            var requestOptions = {
                method: 'POST',
                headers: myHeaders,
                body: formdata,
                redirect: 'follow'
            };

            await fetch("<?= plugin_dir_url(__FILE__) ?>class-recogida.php", requestOptions)
                .then(response => response.text())
                .then(result => refes_order(order_id, result))
                .catch(error => console.log('error', error));
        }
        async function generar_multiple() {
            if (!validate_fecha()) return;
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
            formdata.append("fecha_recogida", fecha_recogida.value.split('-').join('/'));
            formdata.append("notas", notas.value);

            var requestOptions = {
                method: 'POST',
                headers: myHeaders,
                body: formdata,
                redirect: 'follow'
            };
            //window.location.reload()
            await fetch("<?= plugin_dir_url(__FILE__) ?>class-recogida.php", requestOptions)
                .then(response => response.text())
                .then(result => console.log(result))
                .catch(error => console.log('error', error));
        }
    </script>
    <div class="wp-core-ui">

        <?php
        // $tempp = json_decode(requestToken());
        // echo '<pre>';
        // var_dump($tempp->token);
        // echo '</pre>';
        ?>
        <p>
            <button onclick="generar_multiple()" class="button">
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
                    <input id="cb-select-all-1" type="checkbox">
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
            for ($i = 0; $i < count($customer_orders); $i++) {
                AVSHME_show_order_by_table_recogida($customer_orders[$i]);
            }
            ?>
        </tbody>
    </table>
    <div class="tablenav bottom">
        <div class="alignleft actions">
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num"><?= count($customer_orders_total) ?> items</span>
            <span class="pagination-links">
                <?php
                $url_base = "/wp-admin/options-general.php?page=recogida_aveonline&paged=";
                $url_void = "javascript:void(0)";
                $paged_all = ceil(count($customer_orders_total) / $n_page);

                $next = ($paged - 1 > 0) ? $paged - 1 : 1;
                $prev = ($paged + 1 < $paged_all) ? $paged + 1 : $paged_all;

                $url_base_first = $url_base . "1";
                $url_base_prev  = $url_base . $next;
                $url_base_next  = $url_base . $prev;
                $url_base_last  = $url_base . $paged_all;

                if ($paged == 1) {
                    $url_base_first = $url_void;
                    $url_base_prev  = $url_void;
                }
                if ($paged == $paged_all) {
                    $url_base_next  = $url_void;
                    $url_base_last  = $url_void;
                }

                ?>
                <a class="first-page button <?= ($paged == 1) ? "disabled" : ""; ?>" href="<?= $url_base_first ?>">
                    <span class="screen-reader-text">First page</span><span aria-hidden="true">«</span>
                </a>
                <a class="prev-page button <?= ($paged == 1) ? "disabled" : ""; ?>" href="<?= $url_base_prev ?>">
                    <span class="screen-reader-text">Prev page</span><span aria-hidden="true">‹</span>
                </a>
                <span class="screen-reader-text">Current Page</span>
                <span id="table-paging" class="paging-input">
                    <span class="tablenav-paging-text">
                        <?= $paged ?> of <span class="total-pages"><?= $paged_all ?></span>
                    </span>
                </span>
                <a class="next-page button <?= ($paged == $paged_all) ? "disabled" : ""; ?>" href="<?= $url_base_next ?>">
                    <span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span>
                </a>
                <a class="last-page button <?= ($paged == $paged_all) ? "disabled" : ""; ?>" href="<?= $url_base_last ?>">
                    <span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span>
                </a>
            </span>
        </div>
        <br class="clear">
    </div>
<?php
}

function AVSHME_show_order_by_table_recogida($order_id , $swt = true)
{
    if ($order_id == null)  return;

    $order = wc_get_order($order_id);
    if ($order == null)  return;

    $guias_rotulos = get_post_meta($order_id, 'guias_rotulos', true);
    if ($guias_rotulos == null )  return;
    $estado_recogida = get_post_meta($order_id, 'estado_recogida', true);
    ?>
    <tr id="post-<?= $order_id ?>" class="<?= ($estado_recogida == null) ? "no_generado_recogida" : "" ?>
        iedit author-self level-0 post-<?= $order_id ?> type-post status-publish format-standard hentry category-uncategorized">
        <th scope="row" class="check-column" data-children-count="1">
            <label class="screen-reader-text" for="cb-select-<?= $order_id ?>"></label>
            <input id="cb-select-<?= $order_id ?>" order_id="<?= $order_id ?>" type="checkbox" name="post[]" value="<?= $order_id ?>" <?php echo ($estado_recogida == null || !$swt)? '' : 'disabled' ?>>
            <div class="locked-indicator">
                <span class="locked-indicator-icon" aria-hidden="true"></span>
                <span class="screen-reader-text"></span>
            </div>
        </th>
        <td class="title column-title has-row-actions column-primary page-order" data-colname="Title">
            <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
            <strong>
                <a class="row-title" href="/wp-admin/post.php?post=<?= $order_id ?>&action=edit">
                    #<?= $order_id ?>
                </a>
            </strong>
        </td>

        <td class="author column-guia" data-colname="Guia">
            <a target="_blank" href="<?=$guias_rotulos->rutaguia;?>">
                <?=$guias_rotulos->mensaje;?>
            </a>
        </td>
        <td class="author column-rotulo" data-colname="Rotulo">
            <a target="_blank" href="<?=$guias_rotulos->rotulo;?>">
                <?=$guias_rotulos->numguia;?>
            </a>
        </td>
        <td class="author column-estado" data-colname="Estado">
            <?php
            if ($estado_recogida == null) {
                echo "No generada";
            } else {
                echo $estado_recogida;
            }
            ?>
        </td>
        <td class="date column-date" data-colname="Date">
            <span><?= $order->get_date_created()->format('d-m-y'); ?></span>
        </td>
        <td class="date column-paquete" data-colname="paquete">
            <?php
            $paquete = get_post_meta($order_id, 'paquete_final', true);
            if ($paquete != null) {
                echo $paquete['length'] . "x" . $paquete['width'] . "x" . $paquete['height'];
                echo "<br>";
                echo "#N: " . $paquete['numeroPaquetes'];
            }
            ?>
        </td>
        <td class="author column-recogida wp-core-ui" data-colname="Recogida">
            <?php
            if ($estado_recogida == null) {
            ?>
                <p>
                    <button order_id="<?= $order_id ?>" onclick="generar_recogida(this)" class="button">
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


if (isset($_POST) && isset($_POST['generar_recogida'])) {
    require_once(preg_replace('/wp-content.*$/','',__DIR__).'wp-load.php');

    $order_id = $_POST['order_id'];


    $estado_recogida = get_post_meta($order_id, 'estado_recogida', true);
    if ("Generada" == $estado_recogida) {
        echo "no change";
        exit;
    }
    $order = wc_get_order( $order_id );
    $settings = AVSHME_get_settings_aveonline();
    $api = new AveonlineAPI($settings);
    foreach ($order->get_items( 'shipping' ) as $item) {
        foreach ($item->get_meta_data() as $data) {
            $e[$data->get_data()["key"]] = json_decode(base64_decode($data->get_data()["value"]),true);
        }
    }
    if(!isset($e["request"])){
        return;
    }
    $request = $e['request'];
    $data = array(
        'idtransportador'   => $request['idtransportador'],
        'unidades'          => $request['paquete_final']['numeroPaquetes'],
        'kilos'             => $request['weight'],
        'valordeclarado'    => $request['valor_declarado'],
        'fecharecogida'     => $_POST['fecha_recogida'],
        'dscom'             => $_POST['notas'],
    );
    
    $recogida = $api->generarRecogida($data);
    
    if ($recogida->status == "ok") {
        update_post_meta($order_id, 'estado_recogida', "Generada");
        AVSHME_show_order_by_table_recogida($order_id);
    } else {
        echo "error";
    }
    exit;
}

if (isset($_POST) && isset($_POST['generar_recogida_multiple'])) {
    
    require_once(preg_replace('/wp-content.*$/','',__DIR__).'wp-load.php');

    $order_ids = $_POST['order_ids'];
    $order_ids = explode(",", $order_ids);

    $tempArray = [];
    for ($i = 0; $i < count($order_ids); $i++) {
        $order_id = $order_ids[$i];
        $order = wc_get_order($order_id);
        $method = $order->get_shipping_method();

        $tempArray[$method][] = $order_id;
    }
    foreach ($tempArray as $key => $value) {
        $ids = $value;
        $order_ids_final = [];
        $arrayGuias = [];

        $unidades = 0;
        $kilos = 0;
        $valordeclarado = 0;
        $idtransportador = 0;

        for ($i = 0; $i < count($ids); $i++) {
            $order = wc_get_order($ids[$i]);
            $order_id = $order_ids[$i];
            $estado_recogida        = get_post_meta($order_id, 'estado_recogida', true);

            $arrayGuias[]           = get_post_meta($ids[$i], 'guias_rotulos', true);

            if ("Generada" == $estado_recogida) {
                echo "no change";
            } else {

                foreach ($order->get_items( 'shipping' ) as $item) {
                    foreach ($item->get_meta_data() as $data) {
                        $e[$data->get_data()["key"]] = json_decode(base64_decode($data->get_data()["value"]),true);
                    }
                }
                $request = $e['request'];
                $order_ids_final[] = $order_id;

                $idtransportador    =  $request['idtransportador'];
                $unidades           += intval($request['paquete_final']['numeroPaquetes']);
                $kilos              += floatval($request['weight']);
                $valordeclarado     += floatval($request['valor_declarado']);
            }
        }
        $settings = get_option( 'woocommerce_wc_aveonline_shipping_settings' ); 
        // pre($settings);
        //exit;
        // $settings = AVSHME_get_settings_aveonline();
        // pre($settings);
        // exit;
        $api = new AveonlineAPI($settings);

        $data = array(
            'idtransportador'   => $idtransportador,
            'unidades'          => $unidades,
            'kilos'             => $kilos,
            'valordeclarado'    => $valordeclarado,
            'fecharecogida'     => $_POST['fecha_recogida'],
            'dscom'             => $_POST['notas'],
        );
        //pre($data);
        $recogida = $api->generarRecogida($data);
        
        // $relacion_envio = $api->relacionEnvios(array(
        //     "transportadora"    => $idtransportador,
        //     "guias"             => implode(',', $order_ids_final)
        // ));
        pre($recogida);
        // pre($relacion_envio);
        //exit;
        if ($recogida->status == "ok" ) {
            for ($i = 0; $i < count($order_ids_final); $i++) {
                update_post_meta($order_ids_final[$i], 'estado_recogida', "Generada");
                update_post_meta($order_ids_final[$i], 'relacion_envio', true);
                update_post_meta($order_ids_final[$i], 'metodo_envio', $key);
                update_post_meta($order_ids_final[$i], 'transportadora', $idtransportador);
            }
        } else {
            echo "error";
        }
    }
    exit;
}
add_action('admin_bar_menu', 'AVSHME_add_recogida_aveonline_option_page', 100);

add_action('admin_menu', 'AVSHME_recogida_aveonline_option_page');
