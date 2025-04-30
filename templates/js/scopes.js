let scopesDataTables;
$(function () {
  $('body').on('click', '#wx-scopes #scopesData tbody button', function () {
    const data = scopesDataTables.row($(this).parents('tr')).data();
    if ($(this).hasClass('edit')) {
      console.log('/admin/client/scopes/' + data.id);
      $('#wx-scopes #scopeForm').attr('action', '/admin/client/scopes/' + data.id).attr('method', 'PUT');
      $("#wx-scopes #scopeForm input[name='identifier']").val(data.identifier);
      $("#wx-scopes #scopeForm input[name='name']").val(data.name);
      $("#wx-scopes #scopeForm textarea[name='description']").val(data.description);
      $("#wx-scopes #scopeForm textarea[name='scopeRules']").val(data.scopeRules.join("\n"));
      $('#wx-scopes #modal-addScope').modal('show');
    }
    if ($(this).hasClass('del')) {
      const row = $(this).parents('tr');
      dialog('删除授权', '是否确认删除授权范围', function () {
        $.ajax({
          url: basePath + '/admin/client/scopes/' + data.id,
          type: 'POST',
          headers: {"X-HTTP-Method-Override": "DELETE"},
          dataType: 'json',
          success: function (data) {
            scopesDataTables.row(row).remove().draw(false);
            Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
          },
          error: errorDialog
        });
      });
    }
  }).on('submit', '#wx-scopes #scopeForm', function (e) {
    if (e.target.checkValidity()) {
      const fromData = new FormData(e.target);
      $.ajax({
        url: $(e.target).attr('action'),
        data: fromData,
        type: 'POST',
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-HTTP-Method-Override", $(e.target).attr('method'));
        },
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (json) {
          if ($(e.target).attr('method') === 'PUT') {
            const data = scopesDataTables.row($("tr[id='" + $(e.target).attr('action').split('/').pop() + "']")).data();
            data.identifier = fromData.get('identifier');
            data.name = fromData.get('name');
            data.description = fromData.get('description');
            data.scopeRules = fromData.get('scopeRules').split("\n");
            scopesDataTables.row($("tr[id='" + data.id + "']")).data(data);
          } else {
            scopesDataTables.row.add(json).draw(false);
          }
          Toast.fire({icon: 'success', position: 'top', title: '已更新'});
          $('#modal-addScope').modal('hide');
        },
        error: errorDialog
      });
    }
  }).on('hidden.bs.modal', '#wx-scopes #modal-addScope', function () {
    $('#wx-scopes #scopeForm').attr('action', '/admin/client/scopes').attr('method', 'POST').removeClass('was-validated')[0].reset();
  });
});