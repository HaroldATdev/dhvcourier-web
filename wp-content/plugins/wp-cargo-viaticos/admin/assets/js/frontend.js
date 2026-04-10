/* WP Cargo Viáticos – Frontend AJAX */
(function($){
  'use strict';

  function showError(form, msg) {
    form.find('.wpcv-error').remove();
    var $err = $('<div class="alert alert-danger alert-dismissible fade show wpcv-error" role="alert">' +
      '<strong>Error:</strong> ' + msg +
      '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
    form.prepend($err);
    form[0].scrollIntoView({ behavior:'smooth', block:'start' });
  }

  function setBusy(btn, busy) {
    if (busy) btn.data('orig', btn.html()).html('<i class="fa fa-spinner fa-spin mr-1"></i> Guardando…').prop('disabled', true);
    else btn.html(btn.data('orig')).prop('disabled', false);
  }

  /* ── Form viático ────────────────────────────────────────── */
  $(document).on('submit', '#wpcv-form', function(e){
    e.preventDefault();
    var form = $(this);
    var btn  = form.find('[type=submit]');
    form.find('.wpcv-error').remove();
    setBusy(btn, true);

    $.post(wpcv_ajax.url, {
      action          : 'wpcv_guardar',
      nonce           : wpcv_ajax.nonce,
      id              : form.find('[name=id]').val() || 0,
      transportista_id: form.find('[name=transportista_id]').val(),
      ruta            : form.find('[name=ruta]').val(),
      monto_asignado  : form.find('[name=monto_asignado]').val(),
      fecha_asignacion: form.find('[name=fecha_asignacion]').val(),
      notas           : form.find('[name=notas]').val(),
    })
    .done(function(res){
      if (res.success) window.location.href = res.data.redirect;
      else { setBusy(btn, false); showError(form, res.data.msg || 'Error.'); }
    })
    .fail(function(){ setBusy(btn, false); showError(form, 'Error de conexión.'); });
  });

  /* ── Form gasto ──────────────────────────────────────────── */
  $(document).on('submit', '#wpcv-gasto-form', function(e){
    e.preventDefault();
    var form    = $(this);
    var btn     = form.find('[type=submit]');
    var formData = new FormData(form[0]);
    formData.append('action', 'wpcv_gasto_guardar');
    formData.append('nonce',  wpcv_ajax.nonce);
    form.find('.wpcv-error').remove();
    setBusy(btn, true);

    $.ajax({
      url        : wpcv_ajax.url,
      type       : 'POST',
      data       : formData,
      processData: false,
      contentType: false,
    })
    .done(function(res){
      if (res.success) window.location.href = res.data.redirect;
      else { setBusy(btn, false); showError(form, res.data.msg || 'Error.'); }
    })
    .fail(function(){ setBusy(btn, false); showError(form, 'Error de conexión.'); });
  });

  /* ── Cerrar viático ──────────────────────────────────────── */
  $(document).on('click', '.wpcv-btn-cerrar', function(e){
    e.preventDefault();
    if (!confirm('¿Cerrar este viático? No podrás agregar más gastos.')) return;
    var btn = $(this);
    btn.html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events','none');
    $.post(wpcv_ajax.url, {
      action : 'wpcv_cerrar',
      nonce  : wpcv_ajax.nonce,
      id     : btn.data('id'),
    }).done(function(res){
      if (res.success) window.location.href = res.data.redirect;
    });
  });

  /* ── Eliminar gasto ──────────────────────────────────────── */
  $(document).on('click', '.wpcv-btn-del-gasto', function(e){
    e.preventDefault();
    if (!confirm('¿Eliminar este gasto?')) return;
    var btn = $(this);
    var viatico_id = btn.data('viatico');
    btn.html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events','none');
    $.post(wpcv_ajax.url, {
      action    : 'wpcv_gasto_eliminar',
      nonce     : wpcv_ajax.nonce,
      gasto_id  : btn.data('gasto'),
      viatico_id: viatico_id,
    }).done(function(res){
      if (res.success) window.location.href = res.data.redirect;
    });
  });

})(jQuery);
