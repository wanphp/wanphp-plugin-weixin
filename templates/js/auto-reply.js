let autoReplyDataTables;
$(function () {
  $('#modalDialog').on('shown.bs.modal', function (event) {
    if ($(event.target).find('iframe').length) {
      const iframe = $(event.target).find('iframe');
      iframe[0].onload = function () {
        iframe.replaceWith('<video src="' + iframe.contents().find('video').attr('src') + '" controls style="max-width: 100%"></video>');
        Swal.close();
      };
    }
  }).on('hidden.bs.modal', function (event) {
    if ($(event.target).find('video').length) $(event.target).find('video')[0].pause();
  })

  $('body').on('click', '#wx-auto-reply #tableData tbody button', function () {
    const data = autoReplyDataTables.row($(this).closest('tr')).data();
    console.log(data);
    if ($(this).hasClass('video')) {
      showLoading('正在获取视频地址...');
      modalDialog(data.msgContent.Video.Title, '<iframe src="/admin/weixin/material/video/' + data.msgContent.Video.MediaId + '" style="display: none">', 'modal-md',
        '<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>');
    }
    if ($(this).hasClass('edit')) {
      $('#wx-auto-reply #dataForm').attr('action', '/admin/weixin/autoReply/' + data.id).attr('method', 'PUT');
      $("#wx-auto-reply #dataForm input[name='key']").val(data.key);
      $("#wx-auto-reply #dataForm select[name='msgType']").val(data.msgType);
      $("#wx-auto-reply #dataForm .form-group:lt(2)").hide();
      if (data.msgType === 'text') $('#wx-auto-reply #dataForm #key').show();
      if (data.msgContent.Image || data.msgContent.Video || data.msgContent.Voice) data.replyType = 'media';
      $("#wx-auto-reply #dataForm select[name='replyType']").val(data.replyType).change();
      if (data.msgContent.Content) $('#wx-auto-reply #form-message').find('textarea[name="msgContent[Content]"]').val(data.msgContent.Content);
      if (data.msgContent.Image) {// 图片
        $('#wx-auto-reply #form-message').find('.selMaterial').css({width: 'auto', height: 'auto'})
          .html('<img src="/admin/weixin/material/image/' + data.msgContent.Image.MediaId + '" width="100%"><input name="msgContent[Image][MediaId]" value="' + data.msgContent.Image.MediaId + '" type="hidden">');
      }
      if (data.msgContent.Video) {// 视频
        $('#wx-auto-reply #form-message').find('.selMaterial').attr('style', '').html('<div class="cover" style="background-image: url(' + data.msgContent.Cover + ')"></div><input name="msgContent[Video][MediaId]" value="' + data.msgContent.Video.MediaId + '" type="hidden">')
          .find('.selMaterial').parents('.input-group').append('<div style="flex:1 1 auto">\n' +
          '             <input name="msgContent[Video][Title]" class="form-control" placeholder="视频标题" required value="' + data.msgContent.Video.Title + '" style="height: 30px; border-bottom:0;border-radius: 0">' +
          '             <textarea name="msgContent[Video][Description]" class="form-control" placeholder="视频描述" required style="height: 60px;border-radius: 0">' + data.msgContent.Video.Description + '</textarea>' +
          '           </div>');
        // 取视频信息
        const iframe = $('<iframe src="/admin/weixin/material/video/' + data.msgContent.Video.MediaId + '" style="display: none">');
        $('#form-message').append(iframe);
        iframe[0].onload = function () {
          iframe.replaceWith('<video src="' + iframe.contents().find('video').attr('src') + '" controls style="max-width: 100%"></video>');
        };
      }
      if (data.msgContent.Voice) {// 音频
        if ($('#wx-auto-reply #form-message').find('.selMaterial').length) {
          $('#form-message').find('.selMaterial').css({width: 'auto', height: 'auto'})
            .html('<audio src="/admin/weixin/material/voice/' + data.msgContent.Voice.MediaId + '" controls class="pr-5"><input name="msgContent[Voice][MediaId]" required value="' + data.msgContent.Voice.MediaId + '" type="hidden">');
        }
      }
      if (data.msgContent.Music) {// 音频
        if ($('#wx-auto-reply #form-message').find('.selMusicCover').length) {
          $('#wx-auto-reply #form-message').find('.selMusicCover')
            .html('<div class="cover" style="background-image: url(/admin/weixin/material/image/' + data.msgContent.Music.ThumbMediaId + ')"></div><input name="msgContent[Music][ThumbMediaId]" value="' + data.msgContent.Music.ThumbMediaId + '" type="hidden">');
          $('#wx-auto-reply #form-message').find('[name="msgContent[Music][MusicUrl]"]').val(data.msgContent.Music.MusicUrl);
          $('#wx-auto-reply #form-message').find('[name="msgContent[Music][Title]"]').val(data.msgContent.Music.Title);
          $('#wx-auto-reply #form-message').find('[name="msgContent[Music][Description]"]').val(data.msgContent.Music.Description);
        }
      }
      if (data.msgContent.Articles) {// 图文
        var html = '<div class="form-group"><label>回复图文</label>';
        var index = 0;
        for (const article of data.msgContent.Articles) {
          html += '<div class="articleItem"><input type="url" name="msgContent[Articles][' + index + '][Url]" required class="form-control" placeholder="链接地址" value="' + article.Url + '" style="border-radius: 0">' +
            '      <div class="input-group">\n' +
            '        <div class="input-group-append" style="margin-left: 0">\n' +
            '          <div class="uploader_input_box" id="cropCover" style="border-top: 0;border-right: 0">\n' +
            '            <div class="cover" style="background-image: url(' + article.PicUrl + ')"></div>\n' +
            '          </div>\n' +
            '          <input type="hidden" name="msgContent[Articles][' + index + '][PicUrl]" required value="' + article.PicUrl + '">\n' +
            '         </div>' +
            '         <div style="flex:1 1 auto">\n' +
            '           <input name="msgContent[Articles][' + index + '][Title]" value="' + article.Title + '" class="form-control" placeholder="标题" required style="height: 30px; border-top: 0;border-bottom:0;border-radius: 0">' +
            '           <textarea name="msgContent[Articles][' + index + '][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-radius: 0">' + article.Description + '</textarea>' +
            '         </div>';
          if (index > 0) html += '<div class="input-group-append"><i class="fas fa-trash-alt input-group-text" style="height: 90px;border-radius: 0; border-top: 0"></i></div>';
          html += '      </div>' +
            '    </div>';
          index++;
        }
        html += '</div>';
        if (['click', 'view', 'subscribe'].includes($('#dataForm select[name="msgType"]').val()) && data.msgContent.Articles.length < 8) {
          html += '<div class="uploader_input_box" id="addArticle" style="height: 30px;width: 100%;"><i class="fas fa-plus" style="margin-top: 7px"></i></div>';
        }
        $('#wx-auto-reply #form-message').html(html);
      }
      $('#wx-auto-reply #modal-addAutoReply').modal('show');
    }
    if ($(this).hasClass('del')) {
      const row = $(this).closest('tr');
      dialog('删除自动回复', '是否确认删除此回复', function () {
        $.ajax({
          url: basePath + '/admin/weixin/autoReply/' + data.id,
          type: 'POST',
          headers: {"X-HTTP-Method-Override": "DELETE"},
          dataType: 'json',
          success: function () {
            autoReplyDataTables.row(row).remove().draw(false);
            Swal.fire({icon: 'success', title: '删除成功！', showConfirmButton: false, timer: 1500});
          },
          error: errorDialog
        });
      });
    }
  }).on('change', '#wx-auto-reply #dataForm select[name="msgType"]', function () {
    const value = $(this).val();
    $('#wx-auto-reply #message').show();
    $('#wx-auto-reply #modal-addAutoReply .btn-primary').show();
    $('#wx-auto-reply #key div:last-child').remove();
    if (['click', 'view'].includes(value)) {
      $.get(basePath + '/admin/weixin/autoReply/getEvent/' + value, function (res) {
        $('#wx-auto-reply #key label').text('菜单点击事件');
        if (res.length > 0) {
          let html = '<select name="key" class="form-control">';
          for (const re of res) {
            html += '<option value="' + re.key + '">' + re.name + '</option>'
          }
          html += '</select>';
          $('#wx-auto-reply #key').append('<div class="col-sm-9">' + html + '</div>').show();
          console.log(res)
        } else {
          $('#wx-auto-reply #key').append('<div class="alert alert-danger">没有找到自定义菜单事件，请先到添加生成自定义菜单</div>').show();
          $('#wx-auto-reply #message').hide();
          $('#wx-auto-reply #modal-addAutoReply .btn-primary').hide();
        }
      });
    } else if (value === 'text') {
      $('#wx-auto-reply #key label').text('文本关键词');
      $('#wx-auto-reply #key').append('<div class="col-sm-9"><input name="key" type="text" class="form-control" required placeholder="关键词" autocomplete="off"></div>').show();
    } else {
      $('#wx-auto-reply #key label').text('文本关键词');
      if (value === '') $('#wx-auto-reply #key').append('<div class="col-sm-9"><input name="key" type="text" class="form-control" required placeholder="关键词" autocomplete="off"></div>');
      else $('#wx-auto-reply #key').append('<div class="col-sm-9"><input name="key" type="hidden" value="' + value + '" readonly></div>').hide();
    }
  }).on('change', '#wx-auto-reply #dataForm select[name="replyType"]', function () {
    const value = $(this).val();
    let html = '';
    switch (value) {
      case 'text':
        html = '<div class="form-group">\n' +
          '              <label>回复文本</label>\n' +
          '              <textarea name="msgContent[Content]" class="form-control required" placeholder="回复文本,支持超链接"></textarea>' +
          '            </div>';
        break;
      case 'media':
        html = '<div class="form-group">\n' +
          '              <label>回复图片/语音/视频</label>\n' +
          '              <div class="input-group">\n' +
          '                  <div class="input-group-append">\n' +
          '                    <div class="uploader_input_box selMaterial" title="请选择素材">\n' +
          '                      <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
          '                      <p>选择素材</p><input type="text" name="media_id" value="" required style="height: 1px;width: 1px;">\n' +
          '                    </div>\n' +
          '                  </div>\n' +
          '                </div>' +
          '            </div>';
        break;
      case 'music':
        html = '<div class="form-group">\n' +
          '       <label>回复音乐</label><div></div>\n' +
          '       <div class="input-group">\n' +
          '         <div class="input-group-append" style="margin-left: 0">\n' +
          '           <div class="uploader_input_box selMusicCover" style="border-bottom: 0;border-right: 0; margin-bottom: 0">\n' +
          '             <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
          '             <p>选择封面</p>\n' +
          '           </div>\n' +
          '         </div>' +
          '         <div style="flex:1 1 auto">' +
          '           <input name="msgContent[Music][Title]" class="form-control" placeholder="标题" required style="height: 30px;border-bottom:0;border-radius: 0">' +
          '           <textarea name="msgContent[Music][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-bottom: 0;border-radius: 0"></textarea>' +
          '         </div>' +
          '       </div>' +
          '       <div class="input-group">\n' +
          '         <input type="text" name="msgContent[Music][MusicUrl]" required class="form-control" placeholder="音乐地址" style="border-radius: 0">\n' +
          '         <div class="input-group-prepend" style="margin-right: 0">\n' +
          '           <span class="input-group-text" id="uploadMusic">上传</span>\n' +
          '         </div>\n' +
          '       </div>' +
          '     </div>';
        break;
      case 'news':
        html = '<div class="form-group">\n' +
          '       <label>回复图文</label>' +
          '       <div class="articleItem"><input type="url" name="msgContent[Articles][0][Url]" class="form-control" placeholder="链接地址" required style="border-radius: 0">' +
          '         <div class="input-group">\n' +
          '           <div class="input-group-append" style="margin-left: 0">\n' +
          '             <div class="uploader_input_box" style="border-top: 0;border-right: 0">\n' +
          '               <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
          '               <p>选择封面</p>\n' +
          '             </div>\n' +
          '             <input type="hidden" name="msgContent[Articles][0][PicUrl]" value="" required>\n' +
          '           </div>' +
          '           <div style="flex:1 1 auto">\n' +
          '             <input name="msgContent[Articles][0][Title]" class="form-control" placeholder="标题" required style="height: 30px; border-top: 0;border-bottom:0;border-radius: 0">' +
          '             <textarea name="msgContent[Articles][0][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-radius: 0"></textarea>' +
          '           </div>' +
          '         </div>' +
          '       </div>' +
          '     </div>';
        if (['click', 'view', 'subscribe'].includes($('#dataForm select[name="msgType"]').val())) {
          html += '<div class="uploader_input_box" id="addArticle" style="height: 30px;width: 100%;"><i class="fas fa-plus" style="margin-top: 7px"></i></div>';
        }
        break;
    }
    $('#wx-auto-reply #form-message').html(html);
  }).on('click', '#wx-auto-reply #form-message .selMaterial', function (e) {
    $.ajax({
      url: basePath + '/admin/weixin/materialDialog',
      type: 'GET',
      success: function (data) {
        modalDialog('<div class="btn-group">\n' +
          '  <button type="button" class="btn btn-outline-success" data-type="image">图片</button>\n' +
          '  <button type="button" class="btn btn-outline-info" data-type="voice">音频</button>\n' +
          '  <button type="button" class="btn btn-outline-info" data-type="video">视频</button>\n' +
          '</div>', data, 'modal-xl');
      },
      error: errorDialog
    });
  }).on('click', '#wx-auto-reply #form-message .selMusicCover', function (e) {
    $.ajax({
      url: basePath + '/admin/weixin/materialDialog',
      type: 'GET',
      success: function (data) {
        modalDialog('选择封面图片', data, 'modal-xl');
      },
      error: errorDialog
    });
  }).on('click', '#wx-auto-reply #form-message #uploadMusic', function (e) {
    $.uploadFile({
      url: basePath + '/admin/files',
      accept: 'audio/mpeg',
      ext: 'mp3',
      maxSize: 10,
      success: function (res) {
        console.log(res, $(e.target), e);
        $(e.target).closest('.input-group').find('input').val(res.host + res.url);
      },
      error: function (error) {
        Swal.fire({
          icon: 'error',
          title: error.description
        })
      }
    });
  }).on('click', '#wx-auto-reply #form-message #addArticle', function (e) {
    console.log(e);
    const articleLen = $('#wx-auto-reply #form-message').find('.input-group').length;
    if (articleLen < 8) {
      $('#wx-auto-reply #form-message').find('.form-group').append('<div class="articleItem"><input type="url" name="msgContent[Articles][' + articleLen + '][Url]" required class="form-control" placeholder="链接地址" style="border-radius: 0">' +
        '               <div class="input-group">\n' +
        '                  <div class="input-group-append" style="margin-left: 0">\n' +
        '                    <div class="uploader_input_box" id="cropCover" style="border-top: 0;border-right: 0">\n' +
        '                      <i class="fas fa-plus" style="margin-top: 20px"></i>\n' +
        '                      <p>选择封面</p>\n' +
        '                    </div>\n' +
        '                    <input type="hidden" name="msgContent[Articles][' + articleLen + '][PicUrl]" required value="">\n' +
        '                  </div>' +
        '                  <div style="flex:1 1 auto">\n' +
        '                   <input name="msgContent[Articles][' + articleLen + '][Title]" class="form-control" placeholder="标题" required style="height: 30px; border-top: 0;border-bottom:0;border-radius: 0">' +
        '                   <textarea name="msgContent[Articles][' + articleLen + '][Description]" class="form-control" placeholder="描述" required style="height: 60px;border-radius: 0"></textarea>' +
        '                  </div>' +
        '                  <div class="input-group-append"><i class="fas fa-trash-alt input-group-text" style="height: 90px;border-radius: 0; border-top: 0"></i></div>' +
        '               </div>' +
        '             </div>');
    } else {
      $(this).hide();
    }
  }).on('click', '#wx-auto-reply #form-message .fa-trash-alt', function (e) {
    $(this).parents('.articleItem').remove();
    $('#form-message').find('#addArticle').show();
  }).on('click', '#wx-auto-reply #form-message .articleItem .uploader_input_box', function (e) {
    $.uploadFile({
      'url': basePath + '/admin/files',
      'accept': 'image/jpeg,image/jpg,image/png',
      'ext': 'jpg,png',
      'compress': {maxWidth: 200, maxHeight: 200, quality: .75},
      success: function (res) {
        if (res.url) {
          $(e.target).closest('.uploader_input_box').next('input').val(res.host + res.url);
          $(e.target).closest('.uploader_input_box').html('<div class="cover" style="background-image: url(' + res.host + res.url + ')"></div>');
        } else {
          Toast.fire({
            icon: 'error',
            title: res.description
          });
        }
      }
    });
  }).on('submit', '#wx-auto-reply #dataForm', function (e) {
    console.log(e.target.checkValidity());
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
          location.reload();
        },
        error: errorDialog
      });
      return false;
    }
  });

  $('#modal-addAutoReply').on('hidden.bs.modal', function () {
    $('#wx-auto-reply #dataForm').attr('action', '/admin/weixin/autoReply').attr('method', 'POST')[0].reset();
    $("#dataForm .form-group:lt(2)").show();
    $('#dataForm select[name="msgType"]').change();
    $('#dataForm select[name="replyType"]').change();
  });
});

function selMaterial(data) {
  $('#wx-auto-reply #dataForm select[name="replyType"]').change();
  if (data.url) {// 图片
    if ($('#wx-auto-reply #form-message').find('.selMaterial').length) {
      $('#wx-auto-reply #form-message').find('.selMaterial').css({
        width: 'auto',
        height: 'auto'
      }).html('<img src="' + data.url.replace('https://mmbiz.qpic.cn', '') + '" width="100%"><input name="msgContent[Image][MediaId]" value="' + data.media_id + '" type="hidden">');
    }
    // 音乐封面
    if ($('#wx-auto-reply #form-message').find('.selMusicCover').length) {
      $('#wx-auto-reply #form-message').find('.selMusicCover').html('<div class="cover" style="background-image: url(' + data.url.replace('https://mmbiz.qpic.cn', '') + ')"></div><input name="msgContent[Music][ThumbMediaId]" value="' + data.media_id + '" type="hidden">');
    }
  } else if (data.cover_url) {// 视频
    const cover = data.cover_url.replace('https://mmbiz.qpic.cn', '');
    $('#wx-auto-reply #form-message').find('.selMaterial').attr('style', '').html('<div class="cover" style="background-image: url(' + cover + ')"></div><input name="msgContent[Video][MediaId]" value="' + data.media_id + '" type="hidden"><input name="msgContent[Cover]" value="' + cover + '" type="hidden">');
    $('#wx-auto-reply #form-message').find('.selMaterial').parents('.input-group').append('<div style="flex:1 1 auto">\n' +
      '             <input name="msgContent[Video][Title]" class="form-control" placeholder="视频标题" required value="' + data.name + '" style="height: 30px; border-bottom:0;border-radius: 0">' +
      '             <textarea name="msgContent[Video][Description]" class="form-control" placeholder="视频描述" required style="height: 60px;border-radius: 0">' + data.description + '</textarea>' +
      '           </div>');
    // 取视频信息
    const iframe = $('<iframe src="/admin/weixin/material/video/' + data.media_id + '" style="display: none">');
    $('#wx-auto-reply #form-message').append(iframe);
    iframe[0].onload = function () {
      iframe.replaceWith('<video src="' + iframe.contents().find('video').attr('src') + '" controls style="max-width: 100%"></video>');
    };
  } else {// 音频
    if ($('#wx-auto-reply #form-message').find('.selMaterial').length) {
      $('#wx-auto-reply #form-message').find('.selMaterial').css({width: 'auto', height: 'auto'})
        .html('<audio src="/admin/weixin/material/voice/' + data.media_id + '" controls class="pr-5"><input name="msgContent[Voice][MediaId]" required value="' + data.media_id + '" type="hidden">');
    }
  }
  $('#wx-auto-reply #form-message').find('.uploader_input_box').tooltip('dispose');
}