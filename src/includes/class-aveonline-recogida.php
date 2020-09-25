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
add_action('admin_bar_menu', 'add_recogida_aveonline_option_page', 100);

function recogida_aveonline_option_page(){
    add_options_page( 
        'Recogida Aveonline', 
        'Recogidas Aveonline',
        'manage_options',
        'recogida_aveonline', 
        'recogida_aveonline_page');
}

function recogida_aveonline_page(){
    ?>
    <h2 class="screen-reader-text">Orders</h2>
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
                <th scope="col" id="date" class="manage-column column-date">Flecha</th>
                <th scope="col" id="recogida" class="manage-column column-recogida">Generar Recogida</th>
            </tr>
        </thead>

        <tbody id="the-list">
            <?php
                show_order_by_table_recogida(876);
            ?>
        </tbody>
    </table>
    <?php
}
add_action('admin_menu', 'recogida_aveonline_option_page'); 

function show_order_by_table_recogida($order_id){
    ?>
    <tr id="post-<?=$order_id?>"
        class="iedit author-self level-0 post-<?=$order_id?> type-post status-publish format-standard hentry category-uncategorized">
        <th scope="row" class="check-column" data-children-count="1">
            <label class="screen-reader-text" for="cb-select-<?=$order_id?>"></label>
            <input id="cb-select-<?=$order_id?>" type="checkbox" name="post[]" value="<?=$order_id?>">
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
            <a href="#">Guia number</a>
        </td>
        <td class="author column-rotulo" data-colname="Rotulo">
            <a href="#">Rotulo number</a>
        </td>
        <td class="author column-estado" data-colname="Estado">
            Estado e
        </td>
        <td class="date column-date" data-colname="Date">
            <span title="2020/08/17 5:47:44 pm">2020/08/17</span>
        </td>
        <td class="author column-recogida" data-colname="Recogida">
            <button>
                Generar
            </button> 
        </td>
    </tr>
    <?php
}