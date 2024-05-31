function format(d) {
  return d.template_id + '<div class="row"><div class="col-sm-6">' + d.content + '</div><div class="col-sm-6">' + d.example + '</div></div>';
}

$(function () {
  const wxTemplateDataTables = $('#msgTplData').DataTable({
    serverSide: false,
    ajax: basePath + "/admin/weixin/tplmsg",
    rowId: 'id',
    columns: [
      {
        "class": "details-control text-center",
        "orderable": false,
        "data": null,
        "defaultContent": '<i class="fas fa-plus-circle"></i>'
      },
      {title: '模板ID', data: "template_id_short", defaultContent: ''},
      {title: '模板标题', data: "title"},
      {title: '所属行业', data: "primary_industry"},
      {
        title: '操作',
        data: "op",
        defaultContent: '<button type="button" class="btn btn-tool del"><i class="fas fa-trash-alt"></i></button>'
      }
    ]
  });

  let detailRows = [];

  $('#wx-templates #msgTplData tbody').on('click', 'tr td.details-control', function () {
    var tr = $(this).closest('tr');
    var row = wxTemplateDataTables.row(tr);
    var idx = $.inArray(tr.attr('id'), detailRows);

    if (row.child.isShown()) {
      $(this).html('<i class="fas fa-plus-circle"></i>');
      row.child.hide();

      detailRows.splice(idx, 1);
    } else {
      $(this).html('<i class="fas fa-minus-circle"></i>');
      row.child(format(row.data())).show();
      if (idx === -1) {
        detailRows.push(tr.attr('id'));
      }
    }
  }).on('click', '#wx-templates #msgTplData tbody button', function () {
    const row = $(this).closest('tr');
    const data = wxTemplateDataTables.row(row).data();
    console.log(data);
    dialog('删除消息模板', '是否确认删除消息模板', function () {
      $.ajax({
        url: basePath + '/admin/weixin/tplmsg/' + data.template_id,
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "DELETE"},
        dataType: 'json',
        success: function (data) {
          wxTemplateDataTables.row(row).remove().draw(false);
          Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
        },
        error: errorDialog
      });
    });
  }).on('click', '#wx-templates #addtemplate', function () {
    const tpl_id = $(this).closest('.input-group').children('input').val();
    if (!tpl_id) return false;
    $.ajax({
      url: basePath + '/admin/weixin/tplmsg',
      type: 'POST',
      data: {tmpid: tpl_id},
      dataType: 'json',
      success: function (json) {
        location.reload();
      },
      error: errorDialog
    });
  });

  wxTemplateDataTables.on('draw', function () {
    $.each(detailRows, function (i, id) {
      $('#' + id + ' td.details-control').trigger('click');
    });
  });
});