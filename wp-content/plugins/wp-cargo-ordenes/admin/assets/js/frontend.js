/* WP Cargo Órdenes – Frontend AJAX */
(function($){
  'use strict';

  function showError(form, msg) {
    form.find('.wpco-error').remove();
    var $err = $('<div class="alert alert-danger alert-dismissible fade show wpco-error" role="alert">' +
      '<strong>Error:</strong> ' + msg +
      '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
    form.prepend($err);
    form[0].scrollIntoView({ behavior:'smooth', block:'start' });
  }

  function setBusy(btn, busy) {
    if (busy) btn.data('orig', btn.html()).html('<i class="fa fa-spinner fa-spin mr-1"></i> Guardando…').prop('disabled', true);
    else btn.html(btn.data('orig')).prop('disabled', false);
  }

  $(document).on('submit', '#wpco-form', function(e){
    e.preventDefault();
    var form = $(this);
    var btn  = form.find('[type=submit]');
    form.find('.wpco-error').remove();
    setBusy(btn, true);

    $.post(wpco_ajax.url, {
      action          : 'wpco_guardar',
      nonce           : wpco_ajax.nonce,
      id              : form.find('[name=id]').val() || 0,
      cliente         : form.find('[name=cliente]').val(),
      origen          : form.find('[name=origen]').val(),
      destino         : form.find('[name=destino]').val(),
      peso            : form.find('[name=peso]').val(),
      cantidad        : form.find('[name=cantidad]').val(),
      costo           : form.find('[name=costo]').val(),
      transportista_id: form.find('[name=transportista_id]').val(),
      estado          : form.find('[name=estado]').val(),
      notas           : form.find('[name=notas]').val(),
    })
    .done(function(res){
      if (res.success) window.location.href = res.data.redirect;
      else { setBusy(btn, false); showError(form, res.data.msg || 'Error.'); }
    })
    .fail(function(){ setBusy(btn, false); showError(form, 'Error de conexión.'); });
  });

})(jQuery);
