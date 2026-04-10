/* WP Cargo Carrier – Frontend AJAX */
(function($){
  'use strict';

  /* ── Utilidades ─────────────────────────────────────────── */
  function showError(form, msg) {
    form.find('.wpcc-error').remove();
    form.prepend('<div class="alert alert-danger alert-dismissible fade show wpcc-error" role="alert">' +
      '<strong>Error:</strong> ' + msg +
      '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
    form[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function setBusy(btn, busy) {
    if (busy) {
      btn.data('orig', btn.html()).html('<i class="fa fa-spinner fa-spin mr-1"></i> Guardando…').prop('disabled', true);
    } else {
      btn.html(btn.data('orig')).prop('disabled', false);
    }
  }

  /* ── Formulario guardar transportista ───────────────────── */
  $(document).on('submit', '#wpcc-form', function(e){
    e.preventDefault();
    var form = $(this);
    var btn  = form.find('[type=submit]');
    form.find('.wpcc-error').remove();
    setBusy(btn, true);

    var data = {
      action : 'wpcc_guardar',
      nonce  : wpcc_ajax.nonce,
      id     : form.find('[name=id]').val() || 0,
      nombre : form.find('[name=nombre]').val(),
      dni    : form.find('[name=dni]').val(),
      codigo : form.find('[name=codigo]').val(),
      telefono: form.find('[name=telefono]').val(),
      email  : form.find('[name=email]').val(),
    };

    $.post(wpcc_ajax.url, data)
      .done(function(res){
        if (res.success) {
          window.location.href = res.data.redirect;
        } else {
          setBusy(btn, false);
          showError(form, res.data.msg || 'Error desconocido.');
        }
      })
      .fail(function(){
        setBusy(btn, false);
        showError(form, 'Error de conexión. Intenta nuevamente.');
      });
  });

  /* ── Botón cambiar estado ────────────────────────────────── */
  $(document).on('click', '.wpcc-btn-estado', function(e){
    e.preventDefault();
    var btn    = $(this);
    var id     = btn.data('id');
    var estado = btn.data('estado');
    if (!confirm('¿Confirmar cambio de estado?')) return;

    btn.html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events','none');

    $.post(wpcc_ajax.url, {
      action : 'wpcc_cambiar_estado',
      nonce  : wpcc_ajax.nonce,
      id     : id,
      estado : estado,
    }).done(function(res){
      if (res.success) window.location.href = res.data.redirect;
    });
  });

})(jQuery);
