let materialDataTables;

function formatData(data) {
  if (data) {
    let image;
    if (data.url) image = data.url;
    if (data.cover_url) image = data.cover_url;

    if (image) {
      return '<div class="card mb-0" data-toggle="tooltip" title="' + data.name + '">\n' +
        '  <div style="background-image:url(' + image.replace('https://mmbiz.qpic.cn', '') + ')" class="card-img-top">\n' +
        '</div>';
    } else {
      return '<div class="card mb-0 p-2">' + data.name + '</div>';
    }
  }
}

$(function () {
  $('body').on('click', '#wx-material #types button', function () {
    if ($(this).hasClass('btn-outline-info')) {
      $('#wx-material #types button[data-type].btn-outline-success').removeClass('btn-outline-success').addClass('btn-outline-info');
      $(this).removeClass('btn-outline-info').addClass('btn-outline-success');
      if ($.fn.DataTable.isDataTable('#tableData')) {
        materialDataTables.destroy();
        $('#wx-material #tableData').empty();
      }
      materialDataTables = $('#wx-material #tableData').DataTable({
        searching: false,
        lengthChange: false,
        bInfo: false,
        pageLength: 20,
        ajax: basePath + "/admin/weixin/material/list/" + $(this).attr('data-type'),
        columns: [
          {data: 0, render: formatData},
          {data: 1, render: formatData},
          {data: 2, render: formatData},
          {data: 3, render: formatData},
          {data: 4, render: formatData}
        ]
      });
    }
    if ($(this).hasClass('btn-outline-primary')) {
      const type = $(this).attr('data-type');
      if (type === 'uploadImage') {
        $.uploadFile({
          'url': basePath + '/admin/files',
          'accept': 'image/jpeg,image/jpg,image/png',
          'ext': 'jpg,png',
          'compress': {maxWidth: 640, maxHeight: 640, quality: .75},// 图片素材进行压缩
          success: function (res) {
            $.post(basePath + '/admin/weixin/material/add/image', {filePath: res.url}, function (res) {
              console.log(res);
              Swal.fire({
                icon: 'success',
                title: '图片文件已成功添加到微信素材库',
                showConfirmButton: false,
                timer: 3000
              });
            })
          },
          error: function (error) {
            Swal.fire({
              icon: 'error',
              title: error.description
            })
          }
        });
      }
      if (type === 'uploadVoice') {
        Swal.fire('音频上传中...');
        Swal.showLoading();
        $.uploadFile({
          'url': basePath + '/admin/files',
          'accept': 'audio/mpeg',
          'ext': 'mp3',
          maxSize: 2,
          success: function (res) {
            $.post(basePath + '/admin/weixin/material/add/voice', {filePath: res.url}, function (res) {
              console.log(res);
              Swal.fire({
                icon: 'success',
                title: '音频文件已成功添加到微信素材库',
                showConfirmButton: false,
                timer: 3000
              });
            })
          },
          error: function (error) {
            Swal.fire({
              icon: 'error',
              title: error.description
            })
          }
        });
      }
      if (type === 'uploadVideo') {
        Swal.fire('视频上传中...');
        Swal.showLoading();
        $.uploadFile({
          'url': basePath + '/admin/files',
          'accept': 'video/mp4',
          'ext': 'mp4',
          maxSize: 10,
          success: function (res) {
            Swal.fire({
              title: '完善视频信息',
              html: '<input id="title" type="text" class="form-control" placeholder="视频素材的标题" autocomplete="off">' +
                '<textarea id="introduction" class="form-control" placeholder="视频素材的描述"></textarea>',
              showCancelButton: false,
              confirmButtonText: '确定',
              showLoaderOnConfirm: true,
              preConfirm: () => {
                const title = $('#title').val();
                const introduction = $('#introduction').val();
                if (!title || !introduction) {
                  Swal.showValidationMessage('视频信息不能为空!');
                } else {
                  return new Promise((resolve, reject) => {
                    $.post(basePath + "/admin/weixin/material/add/video", {
                      filePath: res.url,
                      description: {title: title, introduction: introduction}
                    }, function (data) {
                      resolve(data)
                    }).fail(function (data) {
                      reject(data)
                    })
                  }).catch(error => {
                    return error.responseJSON;
                  });
                }
              },
              allowOutsideClick: false
            }).then((result) => {
              if (result.isConfirmed) {
                console.log(result);
                if (result.value.errMsg) Swal.fire({
                  icon: 'error',
                  title: result.value.errMsg
                });
                else Swal.fire({
                  icon: 'success',
                  title: '素材成功添加到微信素材库',
                  showConfirmButton: false,
                  timer: 3000
                });
              }
            });
          },
          error: function (error) {
            Swal.fire({
              icon: 'error',
              title: error.description
            })
          }
        });
      }
    }
  }).on('click', '#wx-material #tableData .card', function () {
    const data = datatables.row($(this).parents('tr')).data()[$(this).parents('td').index()];
    if (data.url) {// 图片
      modalDialog(data.name, '<img class="img-fluid" src="' + data.url.replace('https://mmbiz.qpic.cn', '') + '">', 'modal-xl',
        '<div>上传时间：' + formatTimestamp(parseInt(data.update_time) * 1000) + '</div>');
    } else if (data.cover_url) {// 视频
      Swal.fire({title: '正在获取视频地址...'})
      Swal.showLoading();
      modalDialog(data.name, '<iframe src="' + basePath + '/admin/weixin/material/video/' + data.media_id + '" style="display: none">', 'modal-md',
        '<div>上传时间：' + formatTimestamp(parseInt(data.update_time) * 1000) + '</div>');
      Swal.close();
    } else {// 音频
      modalDialog(data.name, '<audio src="' + basePath + '/admin/weixin/material/voice/' + data.media_id + '" controls>', 'modal-md',
        '<div>上传时间：' + formatTimestamp(parseInt(data.update_time) * 1000) + '</div>');
    }
  }).on('shown.bs.modal', '#wx-material #modalDialog', function (event) {
    if ($(event.target).find('iframe').length) {
      const iframe = $(event.target).find('iframe');
      iframe[0].onload = function () {
        iframe.replaceWith('<video src="' + iframe.contents().find('video').attr('src') + '" controls style="max-width: 100%"></video>');
      };
    }
  }).on('hidden.bs.modal', '#wx-material #modalDialog', function (event) {
    if ($(event.target).find('audio').length) $(event.target).find('audio')[0].pause();
    if ($(event.target).find('video').length) $(event.target).find('video')[0].pause();
  }).on('click', '#wx-material #tableData tbody button', function () {
    const data = materialDataTables.row($(this).closest('tr')).data();
    console.log(data);
    const row = $(this).parents('tr');
    dialog('删除永久素材', '是否确定要删除永久素材，请谨慎操作', function () {
      $.ajax({
        url: basePath + '/admin/weixin/material/del/' + data.media_id,
        type: 'POST',
        headers: {"X-HTTP-Method-Override": "DELETE"},
        dataType: 'json',
        success: function (data) {
          materialDataTables.row(row).remove().draw(false);
          Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
        },
        error: errorDialog
      });
    });
  });
});