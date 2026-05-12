<div class="table-top form-group">
    <form id="wpcfe-search" class="float-md-none float-lg-right" action="<?php echo get_permalink( wpcumanage_users_page() ); ?>" method="get">
        <div class="form-sm">
            <label for="search-shipment" class="sr-only">Buscar Usuario</label>
            <input type="text" class="form-control form-control-sm" name="_user" id="search-shipment" placeholder="Buscar Usuario" value="<?php echo $searched_user; ?>">
            <button type="submit" class="btn btn-primary btn-sm mx-md-0 ml-2">Buscar Usuarios</button>
        </div>
    </form>
</div>