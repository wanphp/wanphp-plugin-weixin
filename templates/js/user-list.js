let userListDataTables;
$(function () {
  $('body').on('click', '#wx-user-list #tags button[data-id]', function (e) {
    if ($(this).hasClass('btn-outline-info')) {
      $('.card-header button[data-id].btn-outline-success').removeClass('btn-outline-success').addClass('btn-outline-info');
      $(this).removeClass('btn-outline-info').addClass('btn-outline-success');
      userListDataTables.ajax.url(basePath + '/admin/weixin/user?tag_id=' + e.target.dataset.id).load();
    }
  }).on('click', '#wx-user-list #userData tbody .dropdown-menu input', function (event) {
    const row = userListDataTables.row($(this).closest('tr'));
    const user = row.data();
    const tagId = event.target.value;
    //console.log(tagId);
    if (event.target.checked) {
      if (!user.tagid_list || !user.tagid_list.includes(tagId)) {
        // 添加标签
        $.ajax({
          url: basePath + '/admin/weixin/user/tag',
          type: 'POST',
          headers: {"X-HTTP-Method-Override": "PATCH"},
          data: {uid: user.id, tagId: tagId},
          dataType: 'json',
          success: (json) => {
            console.log(json, user.tagid_list);
            if (user.tagid_list) user.tagid_list.push(tagId);
            else user.tagid_list = [tagId];
            row.data(user).draw(false);
          },
          error: errorDialog
        });
      }
    } else {
      // 删除标签
      $.ajax({
        url: basePath + '/admin/weixin/user/tag',
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "DELETE"},
        data: {uid: user.id, tagId: tagId},
        dataType: 'json',
        success: function () {
          user.tagid_list = user.tagid_list.filter(item => item !== tagId);
          row.data(user).draw(false);
        },
        error: errorDialog
      });
    }
  }).on('click', '#wx-user-list #userData tbody button', function () {
    const row = userListDataTables.row($(this).closest('tr'));
    const data = row.data();
    //console.log(data);
    if ($(this).hasClass('edit')) {
      $('#wx-user-list #modal-editUser #userForm').attr('action', basePath + '/admin/weixin/user/' + data.id).attr('method', 'PATCH');
      $("#wx-user-list #modal-editUser #userForm input[name='name']").val(data.name);
      $("#wx-user-list #modal-editUser #userForm input[name='tel']").val(data.tel);
      $("#wx-user-list #modal-editUser #userForm input[name='remark']").val(data.remark);
      $('#wx-user-list #modal-editUser').modal('show');
    } else if ($(this).hasClass('subscribe')) {
      $.ajax({
        url: basePath + '/admin/weixin/user/' + data.id,
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "PUT"},
        data: {openid: data.openid},
        dataType: 'json',
        success: (json) => {
          if (json && json.subscribe) {
            data.subscribe = json.subscribe;
            data.tagid_list = json.tagid_list;
          }
          row.data(data).draw(false);
        },
        error: errorDialog
      });
    }

  }).on('submit', '#wx-user-list #modal-editUser #userForm', function (e) {
    if (e.target.checkValidity()) {
      const fromData = new FormData(e.target);
      $.ajax({
        url: $(e.target).attr('action'),
        data: fromData,
        type: 'POST',
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-HTTP-Method-Override", $(e.target).attr('method'));
        },
        success: function (json) {
          //console.log(data);
          if ($(e).attr('method') === 'PUT') {
            const id = $(e).attr('action').split('/').pop();
            const data = userListDataTables.row('#' + id).data();
            data['name'] = $("#userForm input[name='name']").val();
            data['tel'] = $("#userForm input[name='tel']").val();
            userListDataTables.row('#' + id).data(data);
          } else {
            userListDataTables.row.add(json).draw(false);
          }
          $('#modal-editUser').modal('hide');
        },
        error: errorDialog
      });
    }
  });
});