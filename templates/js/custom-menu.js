$(function () {
  $('body').on('change', '#wx-custom-menu #menuForm select[name="type"]', function () {
    if ($(this).val() === 'miniprogram') $("#wx-custom-menu #menuForm .miniprogram").show();
    else $("#wx-custom-menu #menuForm .miniprogram").hide();
    if ($(this).val() === 'view' || $(this).val() === 'miniprogram') {
      $("#wx-custom-menu #menuForm .url").show();
      $("#wx-custom-menu #menuForm .key").hide();
    } else {
      $("#wx-custom-menu #menuForm .url").hide();
      $("#wx-custom-menu #menuForm .key").show();
    }
  }).on('click', '#wx-custom-menu #tags button[data-id]', function () {
    if ($(this).attr('data-id')) window.location.hash = '/admin/weixin/menu/' + $(this).attr('data-id');
    else window.location.hash = '/admin/weixin/menu';
  }).on('click', '#wx-custom-menu .addMenu', function () {
    if (!$(this).attr('data-menu')) {
      $('#wx-custom-menu #menuTitle').text('添加' + tagTitle + '一级菜单');
    } else {
      const menu = JSON.parse($(this).attr('data-menu'));
      $("#wx-custom-menu #menuForm input[name='parent_id']").val(menu.id);
      $('#wx-custom-menu #menuTitle').text('添加' + tagTitle + '“' + menu.name + '”的二级菜单');
    }
    $('#wx-custom-menu #menuForm').attr('action', '/admin/weixin/menu').attr('method', 'POST')[0].reset();
    $("#wx-custom-menu .btn-outline-danger").hide();
    $("#wx-custom-menu .btn-outline-primary").removeClass('disabled').attr('disabled', false);
  }).on('click', '#wx-custom-menu .btn-outline-danger', function () {
    const id = $(this).parents('tr').attr('data-id');
    dialog('是否确认删除此菜单', '删除后不可以恢复。', function () {
      $.ajax({
        url: basePath + $('#menuForm').attr('action'),
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "DELETE"},
        dataType: 'json',
        success: function (json) {
          setTimeout(() => {
            location.reload();
          }, 1500);
          Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
        },
        error: errorDialog
      });
    });
  }).on('click', '#wx-custom-menu #createMenu', function () {
    $.ajax({
      url: basePath + '/admin/weixin/createMenu',
      type: 'POST',
      data: {tag_id: $("#wx-custom-menu #menuForm input[name='tag_id']").val()},
      dataType: 'json',
      success: function (json) {
        Swal.fire({icon: 'success', title: '生成成功！', showConfirmButton: false, timer: 1500});
      },
      error: errorDialog
    });
  }).on('click', '#wx-custom-menu .editMenu', function () {
    if ($(this).hasClass('dropdown-item')) {
      $('#wx-custom-menu #menuTitle').text('修改' + tagTitle + '二级菜单');
    } else {
      $('#wx-custom-menu #menuTitle').text('修改' + tagTitle + '一级菜单');
    }
    const menu = JSON.parse($(this).attr('data-menu'));
    $('#wx-custom-menu #menuForm').attr('action', '/admin/weixin/menu/' + menu.id).attr('method', 'PUT');
    $("#menuForm input[name='tag_id']").val(menu.tag_id);
    $("#menuForm input[name='name']").val(menu.name);
    $("#menuForm select[name='type']").val(menu.type).change();
    $("#menuForm input[name='parent_id']").val(menu.parent_id);
    $("#menuForm input[name='key']").val(menu.key);
    $("#menuForm input[name='url']").val(menu.url);
    $("#menuForm input[name='appid']").val(menu.appid);
    $("#menuForm input[name='pagepath']").val(menu.pagepath);
    $("#menuForm input[name='sortOrder']").val(menu.sortOrder);
    $(".btn-outline-danger").show();
    $(".btn-outline-primary").removeClass('disabled').attr('disabled', false);
  }).on('submit', '#wx-custom-menu #menuForm', function (e) {
    if (e.target.checkValidity()) {
      const fromData = new FormData(e.target);
      $.ajax({
        url: basePath + $(e.target).attr('action'),
        data: fromData,
        type: 'POST',
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-HTTP-Method-Override", $(e.target).attr('method'));
        },
        success: function () {
          location.reload();
        },
        error: errorDialog
      });
    }
  });
});