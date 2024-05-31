let wxTagsDataTables;
$(function () {
  $('body').on('submit', '#wx-tags #tagForm', function (e) {
    if (e.target.checkValidity()) {
      const id = e.target.getAttribute('data-id');
      let url = '/admin/weixin/tags';
      if (id > 0) url = '/admin/weixin/tags/' + id;
      const fromData = new FormData(e.target);
      $.ajax({
        url: basePath + url,
        data: fromData,
        type: 'POST',
        beforeSend: function (xhr) {
          if (id > 0) xhr.setRequestHeader("X-HTTP-Method-Override", "PUT");
        },
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (json) {
          location.reload();
        },
        error: errorDialog
      });
      return false;
    }
  }).on('click', '#wx-tags #tagsData .edit', function () {
    const id = $(this).parents('tr').attr('data-id'), name = $(this).parents('tr').find('td:eq(1)').text();
    $('#wx-tags #tagForm').attr('data-id', id);
    $("#wx-tags #tagForm input[name='name']").val(name);
    $('#wx-tags #modal-addTag').modal('show');
  }).on('click', '#wx-tags #tagsData .del', function () {
    const id = $(this).parents('tr').attr('data-id');
    dialog('删除标签', '是否确认删除用户标签', function () {
      $.ajax({
        url: basePath + '/admin/weixin/tags/' + id,
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "DELETE"},
        dataType: 'json',
        success: function (json) {
          $("#wx-tags #tagsData tr[data-id='" + id + "']").remove();
          Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
        },
        error: errorDialog
      });
    });
  }).on('hidden.bs.modal', '#wx-tags #modal-addTag', function () {
    const form = $('#wx-tags #tagForm')[0];
    if (form.hasAttribute('data-id')) form.removeAttribute('data-id');
    form.reset();
  });
});