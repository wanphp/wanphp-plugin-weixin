let clientsDataTables;
const buttonStr = '<button type="button" class="btn btn-tool edit" data-bs-toggle="tooltip" data-bs-title="修改"><i class="fas fa-edit"></i></button>' +
  '<button type="button" class="btn btn-tool del" data-bs-toggle="tooltip" data-bs-title="删除"><i class="fas fa-trash-alt"></i></button>';
$(function () {
  $('body').on('click', '#wx-clients #clientsData tbody button', function () {
    const data = clientsDataTables.row($(this).parents('tr')).data();
    if ($(this).hasClass('edit')) {
      $('#wx-clients #clientForm').attr('action', '/admin/clients/' + data.id).attr('method', 'PUT');
      $("#wx-clients #clientForm input[name='name']").val(data.name);
      $("#wx-clients #clientForm input[name='client_id']").val(data.client_id);
      $("#wx-clients #clientForm input[name='redirect_uri']").val(data.redirect_uri);
      $("#wx-clients #clientForm input[name='client_ip']").val(data.client_ip.join(','));
      $('#wx-clients #modal-addClient').modal('show');
    }
    if ($(this).hasClass('del')) {
      const row = $(this).parents('tr');
      dialog('删除客户端', '是否确认删除客户端', function () {
        $.ajax({
          url: basePath + '/admin/clients/' + data.id,
          type: 'POST',
          headers: {"X-HTTP-Method-Override": "DELETE"},
          dataType: 'json',
          success: function (data) {
            clientsDataTables.row(row).remove().draw(false);
            Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
          },
          error: errorDialog
        });
      });
    }
  }).on('submit', '#wx-clients #clientForm', function (e) {
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
            const data = clientsDataTables.row($("tr[id='" + $(e.target).attr('action').split('/').pop() + "']")).data();
            data.name = fromData.get('name');
            data.client_id = fromData.get('client_id');
            data.redirect_uri = fromData.get('redirect_uri');
            data.client_ip = fromData.get('client_ip').split(',');
            clientsDataTables.row($("tr[id='" + data.id + "']")).data(data);
          } else {
            json['op'] = buttonStr;
            clientsDataTables.row.add(json).draw(false);
          }
          Toast.fire({icon: 'success', position: 'top', title: '已更新'});
          $('#modal-addClient').modal('hide');
        },
        error: errorDialog
      });
    }
  }).on('hidden.bs.modal', '#wx-clients #modal-addClient', function () {
    $('#wx-clients #clientForm').attr('action', '/admin/clients').attr('method', 'POST').removeClass('was-validated')[0].reset();
  });
});