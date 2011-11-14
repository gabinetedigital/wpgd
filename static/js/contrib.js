jQuery(function() {
  var $ = jQuery;

  function inliner(dbfield, accessor, editable) {
    var original_text;
    var td;
    function show_field() {
      td = $(this);
      td.unbind("dblclick", arguments.callee);
      var id = /\[([0-9]+)\]/.exec(td.attr("id"))[1];
      original_text = td.html();

      var ok = $("<input type='submit' value='OK'>").click(function() {
        var data = {id:id,field:dbfield};
        data[dbfield] = accessor.call(editable);
        $(".wpgd-status-bar").slideDown();
        $.ajax({
          url: 'admin-ajax.php',
          type: 'post',
          data: {action:'update_contrib',data:data},
          success: function(data) {
            $(".wpgd-status-bar").slideUp();
            original_text = accessor.call(editable);
            revert_editable();
          }
        });
      });

      var cancel = $("<input type='submit' value='Cancel'>")
        .click(revert_editable);

      accessor.call(editable, td.html());
      td.html('');
      td.append(editable).append(ok).append(cancel);
    }
    function revert_editable() {
      td.bind('dblclick',show_field);
      td.html(original_text);
    }
    return show_field;
  }

  //title and content inline editing

  $(".contribution").bind(
    'dblclick',inliner('content',$().val, $("<textarea/>")));

  $(".contribution-title").bind(
    'dblclick',inliner(
      'title',function(val) {
        if(val)
          return this.attr('value',val);
        else
          return this.attr('value');
      },
      $("<input type='text'/>")));


  // $(".contrib-theme").bind(
  //   'dblclick', inliner('theme',
  //status editing

  $(".contrib-status input").change(function() {
    var td = $(this).parent();
    tr = td.parent();
    var id = /\[([0-9]+)\]/.exec(td.attr("id"))[1];
    var data = {id:id,field:'status'};
    $(".wpgd-status-bar").slideDown();
    $.ajax({
      url: 'admin-ajax.php',
      type: 'post',
      data: {action:'update_contrib',data:data},
      success: function() {
        $(".wpgd-status-bar").slideUp();
        tr.toggleClass("approved disapproved");
      }
    });
  });
});