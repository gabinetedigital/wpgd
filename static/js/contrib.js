jQuery(function() {
  var $ = jQuery;

  $.fn.find_first = function(fn) {
    var self = $(this);
    for(var i = 0; i < self.length; i++) {
      var x = $(self[i])
      if (fn(x)) {
        return x;
      }
    }
    return null;
  }

  function slow_operation(fn) {
    $(".wpgd-status-bar").slideDown();
    fn(function() { $(".wpgd-status-bar").slideUp(); });
  }

  function get_row_id(row) {
    return parseInt(row.attr("id").split("-")[1]);
  }

  function move_parent_row(id) {
    var trs = $("#contrib-rows tr");
    var tr = null;
    for (var i = 0; i < trs.length; i++) {
      var trid = get_row_id($(trs[i]));
      if (trid == id) continue;
      if (trid > id) {
        tr = $(trs[i]);
        break;
      }
    }
    if (tr) {
      //there is a tr bigger than id, insert ourself before it
      tr.before($("#row-"+id).detach());
    } else {
      //we are the bigger id. insert after the last
      //note: the last could also be ourself
      var last_tr = $(trs[trs.length-1]);
      if (get_row_id(last_tr) != id) {
        $(trs[trs.length-1]).after($("#row-"+id).detach());
      } //else, stay were we are, already the last
    }
  }


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

        slow_operation(function(done) {
          $.ajax({
            url: 'admin-ajax.php',
            type: 'post',
            data: {action:'update_contrib',data:data},
            success: function(data) {
              done();
              original_text = accessor.call(editable);
              revert_editable();
            }
          });
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


  $(".contrib-status input").change(function() {
    var td = $(this).parent();
    var tr = td.parent();
    var id = /\[([0-9]+)\]/.exec(td.attr("id"))[1];
    var data = {id:id,field:'status'};
    slow_operation(function(done) {
      $.ajax({
        url: 'admin-ajax.php',
        type: 'post',
        data: {action:'update_contrib',data:data},
        success: function() {
          done();
          tr.toggleClass("wpgd-approved wpgd-disapproved");
        }
      });
    });
  });

  //parenting
  $(".contrib-parents").change(function() {
    var self = $(this);
    var id = /\[([0-9]+)\]/.exec(self.attr("id"))[1];
    var parent = /contrib-parent\[([0-9]+)\]/.exec(self.attr("class"))[1];
    if (parent != self.val())
      if (confirm("Confirm change?")) {
        var new_parent = self.val();
        var data = {id:id,field:'parent', parent:new_parent};
        slow_operation(function(done) {
          $.ajax({
            url: 'admin-ajax.php',
            type: 'post',
            data: {action:'update_contrib',data:data},
            success: function() {
              done();
              var tr = $("#row-"+id);
              if (new_parent == 0) {
                move_parent_row(id);
                //show input checkbox
                tr.find("input[type=checkbox]").show();
                //hide span arrow
                tr.find("span").hide();
                //put the apro/disapr class back
                if($("#row-"+id+" input[type=checkbox]").is(":checked")) {
                  tr.addClass("wpgd-approved");
                } else {
                  tr.addClass("wpgd-disapproved");
                }
              } else {
                //arrange it below the parent
                $("#row-"+new_parent).after(tr.detach());
                //remove the approved/disapproved class
                tr.removeClass("wpgd-approved wpgd-disapproved");
                //hide the checkbox
                tr.find("input[type=checkbox]").hide();
                //show span arrow
                tr.find("span").show();
              }
              //update id info
              self.removeClass("contrib-parent["+parent+"]");
              self.addClass("contrib-parent["+new_parent+"]");
            }
          });
        });
      } else {
        self.val(parent);
      }
  });
});